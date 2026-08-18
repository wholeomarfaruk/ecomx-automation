<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();

            $table->string('status', 20)->default('active');
            // active, converted, abandoned

            $table->decimal('subtotal', 20, 2)->default(0);

            $table->timestamps();

            $table->index('customer_id');
            $table->index('device_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
