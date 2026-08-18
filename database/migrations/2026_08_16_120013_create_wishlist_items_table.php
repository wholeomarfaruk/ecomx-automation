<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wishlist_id')->constrained('wishlists')->cascadeOnDelete();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->timestamps();

            $table->index('wishlist_id');
            $table->index('product_id');
            $table->index('variant_id');
            $table->unique(['wishlist_id', 'product_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
