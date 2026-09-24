<?php

namespace App\Services;

use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\InventoryStockBookingMovement;
use App\Models\InventoryStockMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single write path for physical stock. `inventory_stocks` holds the current
 * balance per (warehouse, product, variant); `inventory_stock_movements` is
 * an immutable audit ledger of every change. Never write to either table
 * directly outside this service — every balance change must be locked
 * (SELECT ... FOR UPDATE) inside a transaction and paired with a movement
 * row, or the balance and the ledger will drift apart.
 */
class StockService
{
    /**
     * Increase stock (purchase, import, admin restock, order cancellation/return).
     */
    public function increase(
        Product $product,
        ?ProductVariant $variant,
        float $quantity,
        string $type,
        ?Warehouse $warehouse = null,
        ?Model $reference = null,
        ?string $note = null,
    ): InventoryStockMovement {
        return $this->applyDelta($product, $variant, abs($quantity), $type, $warehouse, $reference, $note);
    }

    /**
     * Decrease stock (sale, POS sale, damage). Throws if the warehouse
     * doesn't have enough available quantity.
     *
     * @throws InsufficientStockException
     */
    public function decrease(
        Product $product,
        ?ProductVariant $variant,
        float $quantity,
        string $type,
        ?Warehouse $warehouse = null,
        ?Model $reference = null,
        ?string $note = null,
    ): InventoryStockMovement {
        return $this->applyDelta($product, $variant, -abs($quantity), $type, $warehouse, $reference, $note);
    }

    /**
     * Admin correction to an absolute quantity (e.g. variant editor "set stock to X").
     * Computes and logs the delta rather than silently overwriting the balance.
     */
    public function setAbsolute(
        Product $product,
        ?ProductVariant $variant,
        float $newQuantity,
        ?Warehouse $warehouse = null,
        ?Model $reference = null,
        ?string $note = null,
    ): InventoryStockMovement {
        $warehouse ??= Warehouse::default();

        return DB::transaction(function () use ($product, $variant, $newQuantity, $warehouse, $reference, $note) {
            $stock = $this->lockOrCreateStock($product, $variant, $warehouse);
            $before = (float) $stock->quantity;
            $delta = $newQuantity - $before;

            $stock->update(['quantity' => $newQuantity]);
            $this->syncVariantCache($variant, $warehouse, $newQuantity, (float) $stock->booked_quantity);

            return InventoryStockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'type' => 'adjustment',
                'quantity' => $delta,
                'before_quantity' => $before,
                'after_quantity' => $newQuantity,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->id,
                'note' => $note,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Deducts stock for every item on an order the moment it's completed —
     * physically decreasing `quantity` and, in the same transaction,
     * consuming (decreasing) whatever `booked_quantity` this order was
     * holding by the same amount (floored at 0, so an order that skipped
     * booking — e.g. one already committed under the pre-booking model —
     * simply has nothing to consume). Pass `$itemQuantities` (order_item_id
     * => quantity) to deduct only a partial amount per item (partial
     * completion); omitting it deducts each item's full `quantity`.
     * Idempotent per item — if an item's full requested quantity already has
     * a "sale" movement logged, that item is skipped; combo lines (no
     * product_id) are always skipped — combo stock is derived from
     * components, not tracked directly.
     *
     * @param  array<int, float>|null  $itemQuantities
     * @throws InsufficientStockException
     */
    public function commitOrder(Order $order, ?array $itemQuantities = null, ?Warehouse $warehouse = null): void
    {
        $warehouse ??= Warehouse::default();

        DB::transaction(function () use ($order, $itemQuantities, $warehouse) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                // null (the parameter's default) means "no explicit map —
                // deduct each item's full quantity" (legacy/whole-order
                // completion). A non-null array is authoritative even when
                // empty or missing this item's key — 0, not "fall back to
                // full quantity" — since an explicit map means the caller
                // already knows exactly what still needs deducting (e.g.
                // PostOrderCompletion, where a fully-packed item legitimately
                // has nothing left for this method to do).
                $requested = $itemQuantities === null
                    ? (float) $item->quantity
                    : (float) ($itemQuantities[$item->id] ?? 0);

                if ($requested <= 0) {
                    continue;
                }

                $alreadyCommitted = (float) InventoryStockMovement::query()
                    ->where('reference_type', OrderItem::class)
                    ->where('reference_id', $item->id)
                    ->where('type', 'sale')
                    ->sum('quantity');
                $alreadyCommitted = abs($alreadyCommitted);

                $toDeduct = $requested - $alreadyCommitted;

                if ($toDeduct <= 0) {
                    continue;
                }

                $this->decrease(
                    $item->product, $item->variant, $toDeduct, 'sale',
                    warehouse: $warehouse, reference: $item,
                    note: "Order #{$order->id} completed",
                );

                $this->consumeBooking($item, $toDeduct, $warehouse);
            }
        });
    }

    /**
     * Decreases booked_quantity for one item by the given amount (floored at
     * what's actually booked for it), writing a booking-ledger row so the
     * "why did booked_quantity drop" audit trail matches the physical
     * deduction that triggered it.
     */
    protected function consumeBooking(OrderItem $item, float $quantity, Warehouse $warehouse): void
    {
        $stock = $this->lockOrCreateStock($item->product, $item->variant, $warehouse);
        $before = (float) $stock->booked_quantity;
        $toConsume = min($before, $quantity);

        if ($toConsume <= 0) {
            return;
        }

        $after = $before - $toConsume;
        $stock->update(['booked_quantity' => $after]);
        $this->syncVariantCache($item->variant, $warehouse, (float) $stock->quantity, $after);

        InventoryStockBookingMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'type' => 'unbooked_completed',
            'quantity' => -$toConsume,
            'before_quantity' => $before,
            'after_quantity' => $after,
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
            'note' => "Order #{$item->order_id} completed",
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    /**
     * Reserves stock for every item on an order the moment it becomes
     * bookable (confirmed) — increments booked_quantity without touching the
     * physical quantity. Throws if the reservation would exceed what's
     * physically available minus what's already booked by other orders,
     * unless negative stock is allowed. Idempotent via orderAlreadyBooked().
     *
     * @throws InsufficientStockException
     */
    public function bookOrder(Order $order, ?Warehouse $warehouse = null): void
    {
        if ($this->orderAlreadyBooked($order)) {
            return;
        }

        $warehouse ??= Warehouse::default();

        DB::transaction(function () use ($order, $warehouse) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $this->adjustBooking($item, (float) $item->quantity, 'booked', $warehouse, "Order #{$order->id} confirmed");
            }
        });
    }

    /**
     * Releases whatever this order still has booked — for a given release
     * $type (unbooked_completed/unbooked_cancelled/unbooked_returned).
     * Consuming via commitOrder() already releases the completed portion
     * directly, so this is mainly for cancel/return paths where nothing was
     * ever physically deducted. Safe to call on an order with nothing left
     * booked (no-op per item).
     */
    public function releaseBooking(Order $order, string $type, ?Warehouse $warehouse = null): void
    {
        $warehouse ??= Warehouse::default();

        DB::transaction(function () use ($order, $type, $warehouse) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $stock = $this->lockOrCreateStock($item->product, $item->variant, $warehouse);
                $booked = (float) $stock->booked_quantity;

                if ($booked <= 0) {
                    continue;
                }

                $this->adjustBooking($item, -$booked, $type, $warehouse, "Order #{$order->id} {$type}");
            }
        });
    }

    /**
     * Signed delta to one item's booked_quantity ($signedQuantity > 0 to
     * book more, < 0 to release), locked/audited the same way applyDelta()
     * handles the physical balance.
     */
    protected function adjustBooking(OrderItem $item, float $signedQuantity, string $type, Warehouse $warehouse, ?string $note = null): void
    {
        $stock = $this->lockOrCreateStock($item->product, $item->variant, $warehouse);
        $before = (float) $stock->booked_quantity;
        $after = $before + $signedQuantity;

        if ($signedQuantity > 0) {
            $availableToBook = (float) $stock->quantity - $before;
            $allowNegative = (bool) Setting::get('allow_negative_stock', false, 'inventory');

            if ($signedQuantity > $availableToBook && ! $allowNegative) {
                throw InsufficientStockException::forProduct($item->product->name, $signedQuantity, max(0, $availableToBook));
            }
        }

        $after = max(0.0, $after);

        $stock->update(['booked_quantity' => $after]);
        $this->syncVariantCache($item->variant, $warehouse, (float) $stock->quantity, $after);

        InventoryStockBookingMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'type' => $type,
            'quantity' => $signedQuantity,
            'before_quantity' => $before,
            'after_quantity' => $after,
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
            'note' => $note,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    /** Whether this order's stock is currently booked (confirmed and not yet released/completed). */
    public function isOrderBooked(Order $order): bool
    {
        return $this->orderAlreadyBooked($order);
    }

    /**
     * Books one order line's quantity — for a line added to, or re-added
     * after editing on, an already-booked order (admin order editing). No-op
     * for lines without a product (combos).
     *
     * @throws InsufficientStockException
     */
    public function bookItem(OrderItem $item, ?string $note = null, ?Warehouse $warehouse = null): void
    {
        if (! $item->product_id || (float) $item->quantity <= 0) {
            return;
        }

        $this->adjustBooking($item, (float) $item->quantity, 'booked', $warehouse ?? Warehouse::default(), $note);
    }

    /**
     * Releases exactly this line's booked quantity (not the whole stock
     * row's booking, unlike releaseBooking()) — call with the line's
     * pre-edit state before changing/removing it on a booked order.
     */
    public function releaseItemBooking(OrderItem $item, ?string $note = null, ?Warehouse $warehouse = null): void
    {
        if (! $item->product_id || (float) $item->quantity <= 0) {
            return;
        }

        $this->adjustBooking($item, -(float) $item->quantity, 'unbooked_cancelled', $warehouse ?? Warehouse::default(), $note);
    }

    protected function orderAlreadyBooked(Order $order): bool
    {
        return InventoryStockBookingMovement::query()
            ->where('reference_type', OrderItem::class)
            ->whereIn('reference_id', $order->items->pluck('id'))
            ->where('type', 'booked')
            ->exists();
    }

    /**
     * Restocks every item on an order that was previously committed
     * (cancellation, return of a confirmed order). Idempotent — a no-op if
     * the order was never committed, or has already been released.
     */
    public function releaseOrder(Order $order, ?Warehouse $warehouse = null): void
    {
        if (! $this->orderAlreadyCommitted($order) || $this->orderAlreadyReleased($order)) {
            return;
        }

        $warehouse ??= Warehouse::default();

        DB::transaction(function () use ($order, $warehouse) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $this->increase(
                    $item->product, $item->variant, (float) $item->quantity, 'sale_cancelled',
                    warehouse: $warehouse, reference: $item,
                    note: "Order #{$order->id} cancelled/returned",
                );
            }
        });
    }

    /**
     * Restocks the newly-returned portion of one order item — the delta
     * between what was already restocked for it (previous returns) and the
     * quantity being marked as returned now. Safe to call repeatedly with a
     * growing `returnedQuantity` (partial return today, more of it returned
     * next week): each call only restocks the increase since the last call.
     * A no-op if this order was never committed in the first place (nothing
     * was deducted for it, so nothing should be added back), or if the new
     * value isn't actually larger than what's already been restocked.
     */
    public function restockReturnedItem(OrderItem $item, float $returnedQuantity, ?Warehouse $warehouse = null): void
    {
        if (! $item->product_id || ! $this->orderAlreadyCommitted($item->order)) {
            return;
        }

        $alreadyRestocked = (float) InventoryStockMovement::query()
            ->where('reference_type', OrderItem::class)
            ->where('reference_id', $item->id)
            ->where('type', 'return')
            ->sum('quantity');

        $delta = $returnedQuantity - $alreadyRestocked;

        if ($delta <= 0) {
            return;
        }

        $this->increase(
            $item->product, $item->variant, $delta, 'return',
            warehouse: $warehouse, reference: $item,
            note: "Order #{$item->order_id} — item returned",
        );
    }

    /**
     * How much of one PO line item has already been stocked in, across all
     * previous deliveries — a purchase order can arrive in several partial
     * shipments (e.g. 100 ordered, delivered as 20 + 50 + 30), each its own
     * Stock In against the same item.
     */
    public function receivedQuantityForPurchaseOrderItem(PurchaseOrderItem $item): float
    {
        return (float) InventoryStockMovement::query()
            ->where('reference_type', PurchaseOrderItem::class)
            ->where('reference_id', $item->id)
            ->where('quantity', '>', 0)
            ->sum('quantity');
    }

    /**
     * Stocks in the given quantity against one PO line item and, if every
     * item on the PO is now fully received, flips the header's status to
     * "received". Throws if the quantity would exceed what's left on this
     * line (callers should validate against receivedQuantityForPurchaseOrderItem()
     * first for a friendlier error, but this is the hard backstop).
     */
    public function receivePurchaseOrderItem(
        PurchaseOrderItem $item,
        float $quantity,
        ?Warehouse $warehouse = null,
        ?string $note = null,
    ): InventoryStockMovement {
        $item->loadMissing('purchaseOrder', 'product', 'variant');

        $alreadyReceived = $this->receivedQuantityForPurchaseOrderItem($item);
        $remaining = (float) $item->quantity - $alreadyReceived;

        if ($quantity > $remaining) {
            throw InsufficientStockException::forProduct(
                $item->product->name ?? 'this item',
                $quantity,
                $remaining,
            );
        }

        $movement = $this->increase(
            $item->product,
            $item->variant,
            $quantity,
            'purchase',
            warehouse: $warehouse,
            reference: $item,
            note: $note ?: "PO #{$item->purchaseOrder->order_number} — item received",
        );

        $this->markPurchaseOrderReceivedIfComplete($item->purchaseOrder);

        return $movement;
    }

    /**
     * Flips a PO header to "received" once every one of its line items has
     * been fully received. A no-op if the PO is already received/cancelled,
     * or if any item still has quantity outstanding.
     */
    public function markPurchaseOrderReceivedIfComplete(PurchaseOrder $order): void
    {
        if ($order->status !== 'pending') {
            return;
        }

        $items = $order->items;

        if ($items->isEmpty()) {
            return;
        }

        $fullyReceived = $items->every(
            fn (PurchaseOrderItem $item) => $this->receivedQuantityForPurchaseOrderItem($item) >= (float) $item->quantity
        );

        if ($fullyReceived) {
            $order->update(['status' => 'received']);
        }
    }

    protected function orderAlreadyCommitted(Order $order): bool
    {
        return InventoryStockMovement::query()
            ->where('reference_type', OrderItem::class)
            ->whereIn('reference_id', $order->items->pluck('id'))
            ->where('type', 'sale')
            ->exists();
    }

    protected function orderAlreadyReleased(Order $order): bool
    {
        return InventoryStockMovement::query()
            ->where('reference_type', OrderItem::class)
            ->whereIn('reference_id', $order->items->pluck('id'))
            ->where('type', 'sale_cancelled')
            ->exists();
    }

    /**
     * Stock-in a specific batch (purchase receiving, opening stock) — creates
     * or tops up an inventory_batches row for this batch number and mirrors
     * the same amount into the aggregate inventory_stocks balance via the
     * normal increase() path, so inventory_stocks stays the single source of
     * truth for "how much do we have" while inventory_batches tracks "which
     * lot is it in and when does it expire". If a batch with this batch_no
     * already exists at this location, its expiry/manufacture/cost are left
     * untouched — only quantity is topped up (each real batch keeps its own
     * receiving date; a genuinely different batch should get its own number).
     */
    public function stockInBatch(
        Product $product,
        ?ProductVariant $variant,
        string $batchNo,
        float $quantity,
        ?Warehouse $warehouse = null,
        string|\DateTimeInterface|null $expiryDate = null,
        string|\DateTimeInterface|null $manufactureDate = null,
        ?float $purchasePrice = null,
        ?Model $reference = null,
        ?string $note = null,
        ?PurchaseOrder $purchaseOrder = null,
    ): InventoryStockMovement {
        $warehouse ??= Warehouse::default();
        $quantity = abs($quantity);
        $expiryDate = $expiryDate ? Carbon::parse($expiryDate) : null;
        $manufactureDate = $manufactureDate ? Carbon::parse($manufactureDate) : null;
        $supplierId = $purchaseOrder?->supplier_id;

        return DB::transaction(function () use (
            $product, $variant, $batchNo, $quantity, $warehouse,
            $expiryDate, $manufactureDate, $purchasePrice, $reference, $note,
            $supplierId, $purchaseOrder
        ) {
            $batch = InventoryBatch::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->where('variant_id', $variant?->id)
                ->where('batch_no', $batchNo)
                ->lockForUpdate()
                ->first();

            if ($batch) {
                $batch->increment('quantity', $quantity);
                $batch->update([
                    'status' => 'active',
                    'supplier_id' => $batch->supplier_id ?? $supplierId,
                    'purchase_order_id' => $batch->purchase_order_id ?? $purchaseOrder?->id,
                ]);
            } else {
                $batch = InventoryBatch::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'supplier_id' => $supplierId,
                    'purchase_order_id' => $purchaseOrder?->id,
                    'batch_no' => $batchNo,
                    'manufacture_date' => $manufactureDate,
                    'expiry_date' => $expiryDate,
                    'quantity' => $quantity,
                    'purchase_price' => $purchasePrice,
                    'status' => 'active',
                ]);
            }

            $movement = $this->applyDelta($product, $variant, $quantity, 'purchase', $warehouse, $reference ?? $purchaseOrder, $note);
            $movement->update(['batch_id' => $batch->id]);

            return $movement;
        });
    }

    /**
     * Decrease stock, consuming from batches oldest-expiry-first (FEFO) when
     * this product/variant has any active batches at the warehouse. Any
     * quantity left over after the batches are exhausted (or if there are no
     * batches at all — most products won't use batch tracking) falls through
     * to a plain, batch-less decrease. Each batch consumed gets its own
     * movement row so the ledger shows exactly which lot(s) a sale drew from.
     *
     * @throws InsufficientStockException
     */
    public function decreaseFefo(
        Product $product,
        ?ProductVariant $variant,
        float $quantity,
        string $type,
        ?Warehouse $warehouse = null,
        ?Model $reference = null,
        ?string $note = null,
    ): array {
        $warehouse ??= Warehouse::default();
        $remaining = abs($quantity);
        $movements = [];

        DB::transaction(function () use (
            $product, $variant, $type, $warehouse, $reference, $note,
            &$remaining, &$movements
        ) {
            $batches = InventoryBatch::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->where('variant_id', $variant?->id)
                ->active()
                ->fefoOrder()
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, (float) $batch->quantity);

                if ($take <= 0) {
                    continue;
                }

                $batch->decrement('quantity', $take);
                if ((float) $batch->quantity <= 0) {
                    $batch->update(['status' => 'depleted']);
                }

                $movement = $this->applyDelta($product, $variant, -$take, $type, $warehouse, $reference, $note);
                $movement->update(['batch_id' => $batch->id]);
                $movements[] = $movement;

                $remaining -= $take;
            }

            if ($remaining > 0) {
                $movements[] = $this->applyDelta($product, $variant, -$remaining, $type, $warehouse, $reference, $note);
            }
        });

        return $movements;
    }

    /**
     * Decrease stock from ONE caller-specified batch — the explicit-pick
     * sibling to decreaseFefo()'s auto-pick-oldest-expiry-first walk. Used
     * when the admin (not the system) chooses which batch a shipment came
     * from, e.g. packing an order item. Locks the batch row, decrements it,
     * flips it to depleted at 0, then goes through the same applyDelta()
     * aggregate-balance path every other decrease uses and stamps batch_id
     * on the resulting movement — mirrors one iteration of decreaseFefo()'s
     * loop, just for a single named batch instead of an auto-walked list.
     *
     * @throws InsufficientStockException if $quantity exceeds the batch's own remaining quantity
     */
    public function decreaseFromBatch(
        InventoryBatch $batch,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $note = null,
    ): InventoryStockMovement {
        $quantity = abs($quantity);

        return DB::transaction(function () use ($batch, $quantity, $type, $reference, $note) {
            $locked = InventoryBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();

            if ($quantity > (float) $locked->quantity) {
                throw InsufficientStockException::forProduct(
                    $locked->product?->name ?? "batch {$locked->batch_no}",
                    $quantity,
                    (float) $locked->quantity,
                );
            }

            $locked->decrement('quantity', $quantity);
            if ((float) $locked->quantity <= 0) {
                $locked->update(['status' => 'depleted']);
            }

            $warehouse = $locked->warehouse ?? Warehouse::default();

            $movement = $this->applyDelta($locked->product, $locked->variant, -$quantity, $type, $warehouse, $reference, $note);
            $movement->update(['batch_id' => $locked->id]);

            return $movement;
        });
    }

    /**
     * Purchasable quantity across a warehouse (defaults to the default
     * warehouse) for a product/variant — physical quantity minus whatever is
     * currently booked (soft-reserved) against other orders, clamped at 0.
     * Returns 0 if no stock row exists yet.
     */
    public function available(Product $product, ?ProductVariant $variant, ?Warehouse $warehouse = null): float
    {
        $warehouse ??= Warehouse::default();

        $stock = InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->first();

        return $stock ? $stock->availableQuantity() : 0.0;
    }

    protected function applyDelta(
        Product $product,
        ?ProductVariant $variant,
        float $signedQuantity,
        string $type,
        ?Warehouse $warehouse,
        ?Model $reference,
        ?string $note,
    ): InventoryStockMovement {
        $warehouse ??= Warehouse::default();

        return DB::transaction(function () use ($product, $variant, $signedQuantity, $type, $warehouse, $reference, $note) {
            $stock = $this->lockOrCreateStock($product, $variant, $warehouse);
            $before = (float) $stock->quantity;
            $after = $before + $signedQuantity;

            $allowNegative = (bool) Setting::get('allow_negative_stock', false, 'inventory');

            if ($after < 0 && ! $allowNegative) {
                throw InsufficientStockException::forProduct($product->name, abs($signedQuantity), $before);
            }

            $stock->update(['quantity' => $after]);
            $this->syncVariantCache($variant, $warehouse, $after, (float) $stock->booked_quantity);

            return InventoryStockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'type' => $type,
                'quantity' => $signedQuantity,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->id,
                'note' => $note,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * product_variants.stock_quantity is admin-managed only (set via the
     * variant editor) — Stock In and every other StockService write path
     * must not touch it.
     */
    protected function syncVariantCache(?ProductVariant $variant, Warehouse $warehouse, float $newQuantity, float $newBookedQuantity = 0.0): void
    {
        // Intentionally a no-op.
    }

    protected function lockOrCreateStock(Product $product, ?ProductVariant $variant, Warehouse $warehouse): InventoryStock
    {
        $attributes = [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
        ];

        // firstOrCreate isn't lock-safe under concurrency by itself (two
        // transactions can both miss the SELECT and both attempt the
        // INSERT), so create defensively and fall through to the locked
        // SELECT either way — the unique index guarantees only one row
        // ever exists, and the lockForUpdate() below is what actually
        // serializes concurrent balance changes against it.
        try {
            InventoryStock::query()->where($attributes)->lockForUpdate()->first()
                ?? InventoryStock::create([...$attributes, 'quantity' => 0]);
        } catch (QueryException) {
            // Lost the create race to a concurrent transaction — the row
            // now exists, fall through to re-select it under lock.
        }

        return InventoryStock::query()->where($attributes)->lockForUpdate()->firstOrFail();
    }
}
