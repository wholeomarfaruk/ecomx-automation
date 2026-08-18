<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_discount_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();

            $table->string('type', 50);
            // percentage, fixed, fixed_price, buy_x_get_y, free_item, free_shipping

            $table->decimal('value', 20, 4)->nullable();

            $table->integer('buy_quantity')->nullable();
            $table->integer('get_quantity')->nullable();

            $table->decimal('max_discount_amount', 20, 2)->nullable();

            $table->timestamps();

            $table->index('promotion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_discount_rules');
    }
};
