<?php

namespace App\Services\Inventory;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockReceiving;
use App\Models\StockReceivingItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Goods receiving: 1 delivery → 1 receiving (GRN) → many lines → one
 * batch per line. A line either opens a new batch (auto-numbered when no
 * number is given) or tops up an existing batch of the SAME product /
 * variant / warehouse (batches are never shared across items). Lines
 * against a purchase order can't receive more than is left on that PO
 * line; the PO flips to "received" once every line is in.
 *
 * Quantities only — the supplier invoice books Dr Inventory / Cr Payable,
 * so receiving never posts to the ledger (no double count).
 */
class StockReceivingService
{
    public function __construct(protected StockService $stock) {}

    /**
     * @param  array{warehouse_id: int, supplier_id: ?int, purchase_order_id: ?int, received_at: string, reference: ?string, note: ?string}  $header
     * @param  list<array{product_id: int, variant_id: ?int, purchase_order_item_id: ?int, quantity: float, unit_cost: ?float,
     *                    batch_id: ?int, batch_no: ?string, manufacture_date: ?string, expiry_date: ?string}>  $lines
     *
     * @throws ValidationException  keyed lines.{i}.field so the form can show it on the row
     */
    public function receive(array $header, array $lines, ?User $by = null): StockReceiving
    {
        $warehouse = Warehouse::findOrFail($header['warehouse_id']);
        $po = ! empty($header['purchase_order_id']) ? PurchaseOrder::with('items')->findOrFail($header['purchase_order_id']) : null;
        $supplierId = $header['supplier_id'] ?? $po?->supplier_id;

        $this->check($lines, $warehouse, $po);

        return DB::transaction(function () use ($header, $lines, $warehouse, $po, $supplierId, $by) {
            $receivedAt = CarbonImmutable::parse($header['received_at']);
            $grn = StockReceiving::create([
                'grn_number' => $this->nextNumber('GRN', StockReceiving::class, 'grn_number'),
                'warehouse_id' => $warehouse->id, 'supplier_id' => $supplierId, 'purchase_order_id' => $po?->id,
                'received_at' => $receivedAt, 'reference' => $header['reference'] ?: null, 'note' => $header['note'] ?: null,
                'status' => 'received', 'created_by' => ($by ?? auth()->user())?->id,
            ]);

            $qty = 0.0;
            $cost = 0.0;
            foreach ($lines as $l) {
                $product = Product::findOrFail($l['product_id']);
                $variant = ! empty($l['variant_id']) ? ProductVariant::findOrFail($l['variant_id']) : null;
                $poItem = ! empty($l['purchase_order_item_id']) ? PurchaseOrderItem::find($l['purchase_order_item_id']) : null;
                $existing = ! empty($l['batch_id']) ? InventoryBatch::lockForUpdate()->find($l['batch_id']) : null;
                $q = round((float) $l['quantity'], 3);
                $unit = $l['unit_cost'] !== null && $l['unit_cost'] !== '' ? round((float) $l['unit_cost'], 4) : null;
                $batchNo = $existing?->batch_no ?: (trim((string) ($l['batch_no'] ?? '')) ?: $this->nextNumber('BATCH', InventoryBatch::class, 'batch_no'));

                $item = $grn->items()->create([
                    'purchase_order_item_id' => $poItem?->id, 'product_id' => $product->id, 'variant_id' => $variant?->id,
                    'batch_no' => $batchNo, 'quantity' => $q, 'unit_cost' => $unit, 'line_total' => round($q * (float) $unit, 2),
                    'manufacture_date' => $l['manufacture_date'] ?: null, 'expiry_date' => $l['expiry_date'] ?: null,
                ]);

                $oldQty = (float) ($existing?->quantity ?? 0);
                $oldCost = $existing?->purchase_price !== null ? (float) $existing->purchase_price : null;

                $movement = $this->stock->stockInBatch(
                    $product, $variant, $batchNo, $q, $warehouse,
                    expiryDate: $l['expiry_date'] ?: null, manufactureDate: $l['manufacture_date'] ?: null,
                    purchasePrice: $unit, reference: $poItem ?? $item,
                    note: "{$grn->grn_number}" . ($po ? " · PO #{$po->order_number}" : '') . ($header['reference'] ? " · {$header['reference']}" : ''),
                    purchaseOrder: $po,
                );

                $batch = InventoryBatch::find($movement->batch_id);
                $isNew = ! $existing;
                $batch->update([
                    'received_quantity' => $isNew ? $q : (float) ($batch->received_quantity ?? $oldQty) + $q,
                    'stock_receiving_id' => $batch->stock_receiving_id ?? $grn->id,
                    'received_at' => $batch->received_at ?? $receivedAt,
                    'supplier_id' => $batch->supplier_id ?? $supplierId,
                    // Topping up a batch at a different cost: keep its unit cost as the weighted average.
                    'purchase_price' => ! $isNew && $unit !== null && $oldCost !== null && ($oldQty + $q) > 0
                        ? round(($oldQty * $oldCost + $q * $unit) / ($oldQty + $q), 4)
                        : ($batch->purchase_price ?? $unit),
                ]);
                $item->update(['batch_id' => $batch->id]);

                $qty += $q;
                $cost += (float) $item->line_total;
            }

            $grn->update(['total_quantity' => $qty, 'total_cost' => round($cost, 2), 'line_count' => count($lines)]);

            if ($po) {
                $this->stock->markPurchaseOrderReceivedIfComplete($po->fresh('items'));
            }

            return $grn;
        });
    }

    /**
     * Cancel a receiving: takes its quantities back out of the same batches
     * (only while those batches still hold them — stock already sold can't be
     * un-received) and reopens the PO.
     */
    public function cancel(StockReceiving $grn, string $reason, ?User $by = null): void
    {
        if ($grn->status !== 'received') {
            throw ValidationException::withMessages(['cancel' => 'This receiving is already cancelled.']);
        }

        DB::transaction(function () use ($grn, $reason, $by) {
            $grn = StockReceiving::with('items.batch', 'items.product', 'items.variant', 'items.purchaseOrderItem')->lockForUpdate()->findOrFail($grn->id);

            foreach ($grn->items as $item) {
                $batch = $item->batch_id ? InventoryBatch::lockForUpdate()->find($item->batch_id) : null;
                if (! $batch || (float) $batch->quantity + 0.0005 < (float) $item->quantity) {
                    $left = $batch ? rtrim(rtrim(number_format((float) $batch->quantity, 3), '0'), '.') : 0;
                    throw ValidationException::withMessages(['cancel' => "{$item->product?->name} · batch {$item->batch_no}: only {$left} left in the batch — the rest is already sold or moved, so this receiving can't be cancelled."]);
                }

                $movement = $this->stock->decrease(
                    $item->product, $item->variant, (float) $item->quantity, 'receiving_cancelled',
                    $grn->warehouse, $item->purchaseOrderItem ?? $item, "{$grn->grn_number} cancelled · {$reason}",
                );
                $movement->update(['batch_id' => $batch->id]);

                $remaining = round((float) $batch->quantity - (float) $item->quantity, 3);
                $batch->update([
                    'quantity' => $remaining,
                    'received_quantity' => max(0, (float) $batch->received_quantity - (float) $item->quantity),
                    'status' => $remaining <= 0 ? 'depleted' : $batch->status,
                ]);
            }

            $grn->update(['status' => 'cancelled', 'cancelled_by' => ($by ?? auth()->user())?->id, 'cancelled_at' => now(), 'cancel_reason' => $reason]);

            if ($grn->purchase_order_id) {
                PurchaseOrder::whereKey($grn->purchase_order_id)->where('status', 'received')->update(['status' => 'pending']);
            }
        });
    }

    /** Line checks before anything is written; errors are keyed to the row. */
    protected function check(array $lines, Warehouse $warehouse, ?PurchaseOrder $po): void
    {
        $errors = [];
        if (! $lines) {
            $errors['lines'] = 'Add at least one product.';
        }

        $seen = [];
        $poQty = [];
        foreach ($lines as $i => $l) {
            $key = "lines.{$i}";
            $q = (float) ($l['quantity'] ?? 0);
            if ($q <= 0) {
                $errors["{$key}.quantity"] = 'Quantity must be more than 0.';
            }

            $variantId = ! empty($l['variant_id']) ? (int) $l['variant_id'] : null;
            $hasVariants = ProductVariant::where('product_id', $l['product_id'])->exists();
            if ($hasVariants && ! $variantId) {
                $errors["{$key}.variant_id"] = 'Choose a variant.';
            }
            if ($variantId && ! ProductVariant::whereKey($variantId)->where('product_id', $l['product_id'])->exists()) {
                $errors["{$key}.variant_id"] = 'This variant is not of this product.';
            }

            if (! empty($l['batch_id'])) {
                $ok = InventoryBatch::whereKey($l['batch_id'])->where('warehouse_id', $warehouse->id)
                    ->where('product_id', $l['product_id'])->where('variant_id', $variantId)->exists();
                if (! $ok) {
                    $errors["{$key}.batch_id"] = 'That batch belongs to another item or warehouse.';
                }
            }
            $typed = trim((string) ($l['batch_no'] ?? ''));
            if (empty($l['batch_id']) && $typed !== '' && InventoryBatch::where('warehouse_id', $warehouse->id)->where('product_id', $l['product_id'])
                ->where('variant_id', $variantId)->where('batch_no', $typed)->exists()) {
                $errors["{$key}.batch_no"] = "Batch {$typed} already exists for this item — pick it from the list to add to it.";
            }
            if (! empty($l['expiry_date']) && ! empty($l['manufacture_date']) && $l['expiry_date'] < $l['manufacture_date']) {
                $errors["{$key}.expiry_date"] = 'Expiry is before manufacture date.';
            }

            $dup = $l['product_id'] . '|' . $variantId . '|' . ($l['batch_id'] ?: 'new:' . trim((string) ($l['batch_no'] ?? '')));
            if (isset($seen[$dup]) && (! empty($l['batch_id']) || trim((string) ($l['batch_no'] ?? '')) !== '')) {
                $errors["{$key}.product_id"] = 'Same item and batch is already on line ' . ($seen[$dup] + 1) . ' — add the quantity there.';
            }
            $seen[$dup] ??= $i;

            if (! empty($l['purchase_order_item_id'])) {
                $item = $po?->items->firstWhere('id', (int) $l['purchase_order_item_id']);
                if (! $item) {
                    $errors["{$key}.product_id"] = 'This line is not on the selected purchase order.';
                } elseif ((int) $item->product_id !== (int) $l['product_id'] || ($item->product_variant_id ? (int) $item->product_variant_id : null) !== $variantId) {
                    $errors["{$key}.variant_id"] = 'Product / variant must match the PO line.';
                } else {
                    $poQty[$item->id] = ($poQty[$item->id] ?? 0) + $q;
                    $left = max(0, (float) $item->quantity - $this->stock->receivedQuantityForPurchaseOrderItem($item));
                    if ($poQty[$item->id] > $left + 0.0005) {
                        $errors["{$key}.quantity"] = 'Only ' . rtrim(rtrim(number_format($left, 3), '0'), '.') . ' left to receive on this PO line.';
                    }
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** PREFIX-YYYYMMDD-NNN, the next free number for today. */
    public function nextNumber(string $prefix, string $model, string $column): string
    {
        $p = $prefix . '-' . now()->format('Ymd') . '-';
        $max = $model::where($column, 'like', $p . '%')->lockForUpdate()->pluck($column)
            ->map(fn ($no) => (int) substr($no, strlen($p)))->max();

        return $p . str_pad((string) (($max ?? 0) + 1), 3, '0', STR_PAD_LEFT);
    }
}
