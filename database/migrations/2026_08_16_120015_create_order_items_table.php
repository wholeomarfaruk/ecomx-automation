<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('combo_id')->nullable()->constrained('combos')->nullOnDelete();

            $table->boolean('is_gift')->default(false);

            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku')->nullable();

            $table->decimal('quantity', 20, 3)->default(1);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('purchase_price', 20, 4)->nullable();

            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);

            $table->decimal('returned_quantity', 20, 3)->default(0);

            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('combo_id');
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
