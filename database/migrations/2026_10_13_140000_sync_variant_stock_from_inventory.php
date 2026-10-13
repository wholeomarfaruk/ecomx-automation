<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * With the Inventory module on, a variant's stock_quantity mirrors its
 * default-warehouse inventory_stocks balance — but for a while nothing kept
 * that mirror up to date, so variants stocked through Stock In / batches
 * showed 0 on the Inventory list, product list and storefront. Copy the
 * balance across once. Only when the module is on; data-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        $on = DB::table('settings')->where('group', 'modules')->where('key', 'inventory_enabled')->value('value');

        if (! in_array(trim((string) $on, '"'), ['1', 'true'], true)) {
            return;
        }

        $warehouseId = DB::table('warehouses')->where('is_default', true)->value('id');

        if (! $warehouseId) {
            return;
        }

        DB::table('product_variants')->orderBy('id')->chunkById(500, function ($variants) use ($warehouseId) {
            $ledger = DB::table('inventory_stocks')
                ->where('warehouse_id', $warehouseId)
                ->whereIn('variant_id', $variants->pluck('id'))
                ->pluck('quantity', 'variant_id');

            foreach ($variants as $v) {
                $qty = (float) ($ledger[$v->id] ?? 0);
                if (abs((float) $v->stock_quantity - $qty) >= 0.0005) {
                    DB::table('product_variants')->where('id', $v->id)->update(['stock_quantity' => $qty]);
                }
            }
        });
    }

    public function down(): void
    {
        //
    }
};
