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
 * Re-packing applies only the difference per batch: an unchanged batch
 * moves no stock, a raised one takes just the extra, a lowered or dropped
 * one gives just the surplus back. (Un-packing everything and packing it
 * again logged a stock-in + stock-out pair on every re-pack, which the
 * Accounts dashboard counted as real movement, and re-created posted
 * allocations as unposted so their sale/COGS would post twice.)
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
            $existing = $item->batchAllocations()->with('batch')->get()->groupBy('inventory_batch_id');
            $batchIds = collect(array_keys($allocations))->merge($existing->keys())->unique();

            foreach ($batchIds as $batchId) {
                $rows = $existing->get($batchId, collect());
                $diff = round((float) ($allocations[$batchId] ?? 0) - (float) $rows->sum('quantity'), 3);

                if ($diff > 0) {
                    $this->take($item, InventoryBatch::findOrFail($batchId), $diff);
                } elseif ($diff < 0) {
                    $this->putBack($item, $rows, -$diff);
                }
            }

            $totalPacked = $item->batchAllocations()->sum('quantity');
            // Packing only records quantities — the order's status moves with
            // its courier shipment (ApplyCourierStatus), not with packing.
            $item->update(['delivered_quantity' => $totalPacked]);
        });
    }

    /** Deducts $qty more from $batch for this item and records it as a new (unposted) allocation. */
    protected function take(OrderItem $item, InventoryBatch $batch, float $qty): void
    {
        $this->stockService->decreaseFromBatch(
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
    }

    /**
     * Returns $qty of this item's allocation from one batch back to that
     * batch (and the aggregate balance). Unposted rows are reduced first;
     * quantity already costed into a posted sale can't be taken back here.
     *
     * @param  \Illuminate\Support\Collection<int, OrderItemBatch>  $rows  this item's allocations from one batch
     */
    protected function putBack(OrderItem $item, $rows, float $qty): void
    {
        $unposted = $rows->whereNull('posted_at');

        if ($qty > (float) $unposted->sum('quantity') + 0.0005) {
            throw new \RuntimeException("Order #{$item->order_id}: {$item->product_name} is already posted to accounts — its packed batch can't be reduced.");
        }

        $left = $qty;
        foreach ($unposted as $row) {
            $cut = min($left, (float) $row->quantity);
            (float) $row->quantity - $cut <= 0.0005 ? $row->delete() : $row->update(['quantity' => (float) $row->quantity - $cut]);
            $left -= $cut;
            if ($left <= 0.0005) {
                break;
            }
        }

        $batch = $rows->first()->batch;
        if (! $batch) {
            return;
        }

        $this->stockService->increase(
            $batch->product,
            $batch->variant,
            $qty,
            'sale_cancelled',
            $batch->warehouse,
            $item,
            "Order #{$item->order_id} re-packed",
        );

        $batch->increment('quantity', $qty);
        if ($batch->status === 'depleted' && (float) $batch->quantity > 0) {
            $batch->update(['status' => 'active']);
        }
    }
}
