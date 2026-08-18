<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_conditions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();

            $table->string('type', 50);
            // cart_amount, quantity, product, variant, category, brand,
            // customer, customer_group, payment_method, shipping_method

            $table->string('operator', 20);
            // =, !=, >, >=, <, <=, in, not_in

            $table->text('value');
            // scalar (e.g. "3000") or JSON-encoded array for in/not_in (e.g. "[5,8]")

            $table->timestamps();

            $table->index('promotion_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_conditions');
    }
};
