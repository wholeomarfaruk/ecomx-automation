<?php

namespace App\Actions\Accounts;

use App\Models\InventoryBatch;
use Illuminate\Support\Facades\DB;

/**
 * Total purchase-cost value of everything in stock, priced lot-by-lot
 * from inventory_batches (quantity × that batch's own purchase_price) —
 * the same product bought at ৳1000 one time and ৳1500 another must value
 * each remaining unit at what was actually paid for it, not one
 * overwritten "latest price" field on the product/variant. Any stock
 * quantity not covered by an active batch (e.g. an opening balance
 * entered before batch tracking, or without a batch at all) falls back
 * to the product/variant's own stored purchase_price for just that
 * uncovered remainder, so total valued quantity never exceeds what's
 * actually on hand per inventory_stocks.
 *
 * Both parts are aggregated in SQL (no per-stock-row PHP loop) so the
 * dashboards stay fast as the catalog grows.
 */
class CalculateStockValue
{
    public function handle(): float
    {
        $batchValue = (float) InventoryBatch::query()
            ->active()
            ->selectRaw('COALESCE(SUM(quantity * purchase_price), 0) as total')
            ->value('total');

        $batchedQty = InventoryBatch::query()
            ->active()
            ->selectRaw('warehouse_id, product_id, variant_id, SUM(quantity) as qty')
            ->groupBy('warehouse_id', 'product_id', 'variant_id');

        // Uncovered remainder per stock row × variant price, else product price
        // (soft-deleted variant/product contribute no price, as before).
        $fallbackValue = (float) DB::table('inventory_stocks as s')
            ->leftJoinSub($batchedQty, 'bq', function ($join) {
                $join->on('bq.warehouse_id', '=', 's.warehouse_id')
                    ->on('bq.product_id', '=', 's.product_id')
                    ->where(fn ($q) => $q
                        ->whereColumn('bq.variant_id', 's.variant_id')
                        ->orWhere(fn ($q) => $q->whereNull('bq.variant_id')->whereNull('s.variant_id')));
            })
            ->leftJoin('product_variants as v', fn ($j) => $j->on('v.id', '=', 's.variant_id')->whereNull('v.deleted_at'))
            ->leftJoin('products as p', fn ($j) => $j->on('p.id', '=', 's.product_id')->whereNull('p.deleted_at'))
            ->where('s.quantity', '>', 0)
            ->selectRaw('COALESCE(SUM(
                CASE WHEN s.quantity - COALESCE(bq.qty, 0) > 0 THEN s.quantity - COALESCE(bq.qty, 0) ELSE 0 END
                * COALESCE(v.purchase_price, p.purchase_price, 0)
            ), 0) as total')
            ->value('total');

        return $batchValue + $fallbackValue;
    }
}
