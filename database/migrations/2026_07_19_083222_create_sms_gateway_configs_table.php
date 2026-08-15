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
        Schema::create('sms_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('driver_key')->unique();
            $table->text('credentials')->nullable();
            $table->string('sender_id')->nullable();
            $table->string('default_country_code')->nullable();
            $table->unsignedInteger('timeout')->default(30);
            $table->unsignedInteger('retry_attempts')->default(2);
            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_balance_check_at')->nullable();
            $table->decimal('last_balance', 12, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_gateway_configs');
    }
};
