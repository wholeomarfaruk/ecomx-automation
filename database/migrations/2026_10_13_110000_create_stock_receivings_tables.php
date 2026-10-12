<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Goods receiving (GRN): one receiving per delivery, one line per product /
 * variant, one batch per line. Batches remember what they started with
 * (received_quantity) and which receiving made them; their `quantity`
 * stays "quantity remaining".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 40)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->dateTime('received_at');
            $table->string('reference', 100)->nullable();          // supplier challan / invoice no
            $table->text('note')->nullable();
            $table->string('status', 20)->default('received');     // received | cancelled
            $table->decimal('total_quantity', 20, 3)->default(0);
            $table->decimal('total_cost', 20, 2)->default(0);
            $table->unsignedInteger('line_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'received_at']);
        });

        Schema::create('stock_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_receiving_id')->constrained('stock_receivings')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->string('batch_no', 100);
            $table->decimal('quantity', 20, 3);
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->decimal('line_total', 20, 2)->default(0);
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'variant_id']);
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->decimal('received_quantity', 20, 3)->nullable()->after('quantity');
            $table->foreignId('stock_receiving_id')->nullable()->after('purchase_order_id')->constrained('stock_receivings')->nullOnDelete();
            $table->dateTime('received_at')->nullable()->after('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_receiving_id');
            $table->dropColumn(['received_quantity', 'received_at']);
        });
        Schema::dropIfExists('stock_receiving_items');
        Schema::dropIfExists('stock_receivings');
    }
};
