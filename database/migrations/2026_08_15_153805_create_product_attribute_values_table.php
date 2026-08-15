<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['product_attribute_id', 'attribute_value_id'], 'pav_attribute_value_unique');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
