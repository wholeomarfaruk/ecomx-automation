<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            $table->text('description')->nullable();

            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 10, 2)->default(0.00);

            $table->decimal('minimum_order_amount', 15, 2)->default(0.00);
            $table->integer('minimum_order_qty')->default(0);

            $table->boolean('allow_credit')->default(false);
            $table->boolean('reward_points_enabled')->default(true);

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
