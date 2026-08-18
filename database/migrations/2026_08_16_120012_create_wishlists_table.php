<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
