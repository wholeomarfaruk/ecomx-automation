<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->timestamps();

            $table->index('promotion_id');
            $table->index('product_id');
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_items');
    }
};
