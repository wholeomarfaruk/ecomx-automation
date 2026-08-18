<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();

            $table->string('code', 100)->unique();

            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_customer')->nullable();

            $table->decimal('min_order_amount', 20, 2)->nullable();
            $table->decimal('max_discount_amount', 20, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
