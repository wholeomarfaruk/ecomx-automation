<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable audit ledger for booked_quantity changes — mirrors
     * inventory_stock_movements, but for the soft-reservation balance
     * (booked_quantity) rather than the physical one (quantity). Kept
     * separate because booking a unit and physically deducting it are
     * different events that can happen at different times (confirm vs
     * completed) and must be independently auditable/idempotent.
     */
    public function up(): void
    {
        Schema::create('inventory_stock_booking_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();

            $table->string('type', 30); // booked | unbooked_completed | unbooked_cancelled | unbooked_returned

            $table->decimal('quantity', 20, 3);
            $table->decimal('before_quantity', 20, 3);
            $table->decimal('after_quantity', 20, 3);

            $table->nullableMorphs('reference', 'isbm_reference_index');
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['warehouse_id', 'product_id', 'variant_id'], 'isbm_location_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_booking_movements');
    }
};
