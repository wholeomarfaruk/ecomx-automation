<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_order', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['product_id', 'product_attribute_id']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_order');
    }
};
