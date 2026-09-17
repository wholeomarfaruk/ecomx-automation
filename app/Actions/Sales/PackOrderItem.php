<?php

namespace App\Actions\Sales;

use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\InventoryBatch;
use App\Models\OrderItem;
use App\Models\OrderItemBatch;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Packs an order item from one or more specific inventory batches — the
 * admin picks which batch(es) a shipment's units actually came from,
 * rather than the system guessing. This both physically deducts stock
 * (batch + aggregate balance, via StockService::decreaseFromBatch()) and
 * records the audit trail (OrderItemBatch rows, cost snapshotted per
 * batch) that PostOrderCompletion later reads to compute real per-batch
 * COGS instead of one flat purchase_price guess.
 *
 * Re-packing an already-partially-packed item is supported by first
 * removing its existing OrderItemBatch allocation (restocking those
 * batches) and then applying the new one — simplest way to keep "what's
 * currently packed" exactly matching the caller's latest allocation map
 * without diffing row-by-row.
 */
class PackOrderItem
{
    public function __construct(protected StockService $stockService) {}

    /**
     * @param  array<int, float>  $allocations  inventory_batch_id => quantity to take from it
     *
     * @throws InsufficientStockException
     */
    public function handle(OrderItem $item, array $allocations): void
    {
        $allocations = array_filter($allocations, fn (float $qty) => $qty > 0);

        if (empty($allocations)) {
            return;
        }

        $requested = array_sum($allocations);

        if ($requested > (float) $item->quantity + 0.001) {
            throw new \InvalidArgumentException('Allocated quantity exceeds the item\'s ordered quantity.');
        }

        DB::transaction(function () use ($item, $allocations) {
            $this->unpack($item);

            foreach ($allocations as $batchId => $qty) {
                $batch = InventoryBatch::findOrFail($batchId);

                $movement = $this->stockService->decreaseFromBatch(
                    $batch,
                    $qty,
                    'sale',
                    reference: $item,
                    note: "Order #{$item->order_id} packed",
                );

                OrderItemBatch::create([
                    'order_item_id'      => $item->id,
                    'inventory_batch_id' => $batch->id,
                    'quantity'           => $qty,
                    'unit_cost'          => $batch->purchase_price ?? 0,
                ]);

                unset($movement);
            }

            $totalPacked = $item->batchAllocations()->sum('quantity');
            $item->update(['delivered_quantity' => $totalPacked]);

            $item->order?->syncDeliveryStatus();
        });
    }

    /**
     * Reverses this item's existing batch allocation (restocks each batch
     * and the aggregate balance, deletes the OrderItemBatch rows) so
     * handle() can cleanly re-apply a fresh allocation map on re-pack,
     * rather than trying to diff old vs. new row-by-row.
     */
    protected function unpack(OrderItem $item): void
    {
        $existing = $item->batchAllocations()->with('batch')->get();

        foreach ($existing as $allocation) {
            $batch = $allocation->batch;

            if ($batch) {
                $this->stockService->increase(
                    $batch->product,
                    $batch->variant,
                    (float) $allocation->quantity,
                    'sale_cancelled',
                    reference: $item,
                    note: "Order #{$item->order_id} re-packed",
                );

                $batch->increment('quantity', (float) $allocation->quantity);
                if ($batch->status === 'depleted' && (float) $batch->quantity > 0) {
                    $batch->update(['status' => 'active']);
                }
            }

            $allocation->delete();
        }
    }
}
