<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->string('status', 20)->default('saved');
            // saved, redeemed, expired

            $table->timestamp('saved_at');
            $table->timestamp('redeemed_at')->nullable();

            $table->timestamps();

            $table->unique(['coupon_id', 'customer_id']);
            $table->index('customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_customers');
    }
};
