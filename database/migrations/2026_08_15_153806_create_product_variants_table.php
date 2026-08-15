<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->string('combination_key');

            $table->decimal('price', 20, 2)->nullable();
            $table->decimal('sale_price', 20, 2)->nullable();
            $table->decimal('purchase_price', 20, 2)->nullable();

            $table->string('status', 50)->default('active');

            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('sort_order');
            $table->unique(['product_id', 'combination_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
