<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Move each existing single-line PO's variant/quantity/price into its
        // own purchase_order_items row before the columns disappear from the
        // header, so no historical data is lost in the header+items split.
        DB::table('purchase_orders')
            ->whereNotNull('product_variant_id')
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                $now = now();
                $rows = $orders->map(fn ($order) => [
                    'purchase_order_id' => $order->id,
                    'product_variant_id' => $order->product_variant_id,
                    'quantity' => $order->quantity,
                    'unit_price' => $order->unit_price,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at ?? $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('purchase_order_items')->insert($rows);
            });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });

        DB::statement('ALTER TABLE `purchase_orders` DROP COLUMN `product_variant_id`');
        DB::statement('ALTER TABLE `purchase_orders` DROP COLUMN `quantity`');
        DB::statement('ALTER TABLE `purchase_orders` DROP COLUMN `unit_price`');
        DB::statement('ALTER TABLE `purchase_orders` DROP COLUMN `total_amount`');
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('supplier_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('quantity', 20, 3)->nullable()->after('product_variant_id');
            $table->decimal('unit_price', 20, 2)->nullable()->after('quantity');
            $table->decimal('total_amount', 20, 2)->nullable()->after('unit_price');
        });

        // Best-effort restore: pulls back the first item per PO (the schema
        // this is reverting to only ever supported one line per order).
        DB::table('purchase_order_items')
            ->orderBy('id')
            ->get()
            ->groupBy('purchase_order_id')
            ->each(function ($items, $orderId) {
                $first = $items->first();
                DB::table('purchase_orders')->where('id', $orderId)->update([
                    'product_variant_id' => $first->product_variant_id,
                    'quantity' => $first->quantity,
                    'unit_price' => $first->unit_price,
                    'total_amount' => $first->total_amount,
                ]);
            });

        Schema::dropIfExists('purchase_order_items');
    }
};
