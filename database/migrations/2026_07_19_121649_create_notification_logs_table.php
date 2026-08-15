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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->index();
            $table->string('channel')->index();
            $table->string('recipient');
            $table->string('status')->index();
            $table->string('provider')->nullable();
            $table->string('error_message')->nullable();
            $table->json('provider_response')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
