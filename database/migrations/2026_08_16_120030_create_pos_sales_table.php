<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')->constrained('pos_sessions')->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->index('session_id');
            $table->index('customer_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};
