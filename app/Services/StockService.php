<?php

namespace App\Services;

use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
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
            $this->syncVariantCache($variant, $warehouse, $newQuantity);

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
     * Deducts stock for every item on an order the moment it becomes
     * confirmed. Idempotent — if this order's items already have a "sale"
     * movement logged (e.g. updateStatus() saved twice, or the order was
     * already confirmed at creation), this is a no-op. Combo lines (no
     * product_id) are skipped — combo stock is derived from components,
     * not tracked directly, per the inventory plan.
     *
     * @throws InsufficientStockException
     */
    public function commitOrder(Order $order, ?Warehouse $warehouse = null): void
    {
        if ($this->orderAlreadyCommitted($order)) {
            return;
        }

        $warehouse ??= Warehouse::default();

        DB::transaction(function () use ($order, $warehouse) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $this->decrease(
                    $item->product, $item->variant, (float) $item->quantity, 'sale',
                    warehouse: $warehouse, reference: $item,
                    note: "Order #{$order->id} confirmed",
                );
            }
        });
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
        $item->loadMissing('purchaseOrder', 'variant.product');

        $alreadyReceived = $this->receivedQuantityForPurchaseOrderItem($item);
        $remaining = (float) $item->quantity - $alreadyReceived;

        if ($quantity > $remaining) {
            throw InsufficientStockException::forProduct(
                $item->variant->product->name ?? 'this item',
                $quantity,
                $remaining,
            );
        }

        $movement = $this->increase(
            $item->variant->product,
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
     * Current available quantity across a warehouse (defaults to the default
     * warehouse) for a product/variant. Returns 0 if no stock row exists yet.
     */
    public function available(Product $product, ?ProductVariant $variant, ?Warehouse $warehouse = null): float
    {
        $warehouse ??= Warehouse::default();

        return (float) InventoryStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->value('quantity') ?? 0.0;
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
            $this->syncVariantCache($variant, $warehouse, $after);

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
     * product_variants.stock_quantity is a denormalized read cache for the
     * default warehouse's balance — every cart/PDP/checkout read site in
     * the app queries it directly rather than joining inventory_stocks.
     * Keep it in sync here so those call sites don't need to change; if
     * multi-warehouse selection is added later, this cache should represent
     * a sum across warehouses instead of just the default one.
     */
    protected function syncVariantCache(?ProductVariant $variant, Warehouse $warehouse, float $newQuantity): void
    {
        if ($variant && $warehouse->is_default) {
            $variant->update(['stock_quantity' => $newQuantity]);
        }
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
