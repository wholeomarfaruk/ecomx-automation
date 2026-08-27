<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();

            $table->string('batch_no', 100);

            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->decimal('quantity', 20, 3)->default(0);
            $table->decimal('purchase_price', 20, 4)->nullable();

            $table->string('status', 20)->default('active');
            // active, expired, depleted

            $table->timestamps();

            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('variant_id');
            $table->index('expiry_date');
            $table->index('status');
            $table->unique(['warehouse_id', 'product_id', 'variant_id', 'batch_no'], 'inventory_batches_location_batch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
