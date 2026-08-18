<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_gifts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('gift_product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('quantity', 20, 3)->default(1);

            $table->timestamps();

            $table->index('product_id');
            $table->index('gift_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_gifts');
    }
};
