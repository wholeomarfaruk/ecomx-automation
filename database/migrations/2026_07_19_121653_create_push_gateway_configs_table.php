<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('push_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('driver_key')->unique();
            $table->text('credentials')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_gateway_configs');
    }
};
