<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_combo_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('combo_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->decimal('quantity', 20, 3)->default(1);

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index('combo_product_id');
            $table->index('product_id');
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combo_items');
    }
};
