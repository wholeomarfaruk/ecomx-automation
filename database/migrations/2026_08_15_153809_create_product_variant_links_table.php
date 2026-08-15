<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();

            $table->foreignId('linked_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('linked_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->string('link_type', 50)->default('color');

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index('link_type');
            $table->index('sort_order');
            $table->unique(['product_variant_id', 'linked_product_id', 'link_type'], 'pvl_variant_product_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_links');
    }
};
