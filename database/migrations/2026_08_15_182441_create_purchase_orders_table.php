<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();

            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();

            $table->decimal('quantity', 20, 3);

            $table->decimal('unit_price', 20, 2)->nullable();
            $table->decimal('total_amount', 20, 2)->nullable();

            $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete();

            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');

            $table->date('order_date')->nullable();
            $table->date('deadline')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('order_date');
            $table->index('deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
