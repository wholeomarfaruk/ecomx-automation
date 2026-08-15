<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('fingerprint')->unique();

            $table->text('user_agent')->nullable();

            $table->string('device_type');
            $table->string('platform');

            $table->string('device_brand')->nullable();
            $table->string('device_model')->nullable();
            $table->string('manufacturer')->nullable();

            $table->string('operating_system')->nullable();
            $table->string('os_version')->nullable();

            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();

            $table->string('app_version')->nullable();
            $table->string('build_number')->nullable();

            $table->string('screen_resolution')->nullable();
            $table->string('screen_density')->nullable();

            $table->string('language')->nullable();
            $table->string('timezone')->nullable();

            $table->text('fcm_token')->nullable();

            $table->string('ip_address')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_active_at')->nullable();

            $table->boolean('is_trusted')->default(false);
            $table->boolean('is_allowed')->default(true);

            $table->timestamps();



            $table->index('is_trusted');
            $table->index('is_allowed');
            $table->index('device_type');
            $table->index('platform');
            $table->index('last_active_at');
            $table->index('last_login_at');
            $table->index('fingerprint');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
