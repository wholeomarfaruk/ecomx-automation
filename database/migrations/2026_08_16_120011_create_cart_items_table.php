<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('combo_id')->nullable()->constrained('combos')->nullOnDelete();

            $table->boolean('is_gift')->default(false);

            $table->decimal('quantity', 20, 3)->default(1);
            $table->decimal('price', 20, 2)->default(0);
            // snapshot unit price at time of adding to cart; 0 for gift rows

            $table->timestamps();

            $table->index('cart_id');
            $table->index('product_id');
            $table->index('combo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
