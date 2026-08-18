<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->decimal('discount_amount', 20, 2);

            $table->timestamp('used_at');

            $table->timestamps();

            $table->index('coupon_id');
            $table->index('order_id');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
