<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_sessions', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('ended_at')->nullable();

            $table->text('landing_url')->nullable();
            $table->string('landing_path')->nullable();
            $table->string('landing_title')->nullable();

            $table->text('exit_url')->nullable();
            $table->string('exit_path')->nullable();

            $table->text('referrer_url')->nullable();
            $table->string('referrer_domain')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->string('device_type')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('operating_system')->nullable();

            $table->string('language', 20)->nullable();
            $table->string('timezone', 64)->nullable();

            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();

            $table->string('fbclid', 500)->nullable();
            $table->string('gclid', 500)->nullable();
            $table->string('ttclid', 500)->nullable();

            $table->json('session_data')->nullable();

            $table->timestamps();

            $table->index(['device_id', 'started_at']);
            $table->index(['customer_id', 'started_at']);
            $table->index(['utm_source', 'utm_campaign', 'started_at']);
            $table->index(['fbclid', 'started_at']);
            $table->index(['gclid', 'started_at']);
            $table->index(['ttclid', 'started_at']);
            $table->index('started_at');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_sessions');
    }
};
