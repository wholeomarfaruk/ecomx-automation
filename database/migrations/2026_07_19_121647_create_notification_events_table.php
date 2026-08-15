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
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('label');
            $table->boolean('channel_email')->default(false);
            $table->boolean('channel_sms')->default(false);
            $table->boolean('channel_push')->default(false);
            $table->boolean('channel_browser')->default(false);
            $table->boolean('channel_database')->default(false);
            $table->string('email_template_key')->nullable();
            $table->string('sms_template_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
