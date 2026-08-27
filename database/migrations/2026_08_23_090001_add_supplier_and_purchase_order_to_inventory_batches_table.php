<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('variant_id')->constrained('suppliers')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->after('supplier_id')->constrained('purchase_orders')->nullOnDelete();

            $table->index('supplier_id');
            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('purchase_order_id');
        });
    }
};
