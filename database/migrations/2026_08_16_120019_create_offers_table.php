<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();

            $table->string('offer_type', 50);
            // percentage, fixed, buy_x_get_y, fixed_price, free_item

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
