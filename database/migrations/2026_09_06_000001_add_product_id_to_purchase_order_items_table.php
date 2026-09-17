<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A purchase order line previously always pointed at a ProductVariant —
     * impossible for a "simple" product, which has no variant row at all.
     * Adds product_id (the source of truth for what's being purchased) and
     * makes product_variant_id nullable (set only when the line is for a
     * specific variant of a variable product). Existing rows are backfilled
     * from their variant's product_id before product_id is made required.
     */
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('purchase_order_id')->constrained('products')->cascadeOnDelete();
        });

        DB::statement('
            UPDATE purchase_order_items poi
            INNER JOIN product_variants pv ON pv.id = poi.product_variant_id
            SET poi.product_id = pv.product_id
        ');

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();

            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->change();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id']);
            $table->dropColumn('product_id');

            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable(false)->change();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });
    }
};
