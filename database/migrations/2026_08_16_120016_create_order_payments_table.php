<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->string('payment_method', 50);
            $table->string('transaction_id')->nullable();

            $table->decimal('amount', 20, 2);

            $table->string('status', 20)->default('pending');
            // pending, paid, failed, refunded

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index('order_id');
            $table->index('transaction_id');
            $table->index('payment_method');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
