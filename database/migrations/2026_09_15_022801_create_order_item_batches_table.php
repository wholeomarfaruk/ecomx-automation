<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records which inventory batch(es) actually fulfilled an order item at
     * packing time — an item's quantity can span several batches at
     * different costs (e.g. 3 units from an older ৳1000 batch, 2 from a
     * newer ৳1200 one). unit_cost snapshots the batch's purchase_price at
     * the moment it was picked, so a later edit to the batch's price never
     * retroactively rewrites an already-shipped order's cost.
     */
    public function up(): void
    {
        Schema::create('order_item_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->constrained('inventory_batches')->restrictOnDelete();

            $table->decimal('quantity', 20, 3);
            $table->decimal('unit_cost', 20, 4);

            $table->timestamps();

            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_batches');
    }
};
