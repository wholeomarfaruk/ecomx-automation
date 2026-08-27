<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_event_destinations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketing_event_id')->constrained('marketing_events')->cascadeOnDelete();

            $table->string('destination', 50);
            $table->string('channel', 20);

            $table->string('status', 30)->default('pending');

            $table->string('external_event_id', 191)->nullable();
            $table->string('event_name')->nullable();

            $table->unsignedInteger('attempts')->default(0);

            $table->timestamp('first_attempted_at')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();

            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('response_code')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('request_id')->nullable();
            $table->string('deduplication_key')->nullable();
            $table->string('payload_hash', 64)->nullable();

            // Deliberately kept minimal — full raw payload logging (if ever
            // needed) belongs in a separate marketing_destination_logs table.
            $table->json('response_data')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['marketing_event_id', 'destination', 'channel'], 'marketing_event_destinations_unique');
            $table->index(['status', 'next_retry_at']);
            $table->index(['destination', 'status', 'created_at'], 'marketing_event_destinations_dest_status_idx');
            $table->index('external_event_id');
            $table->index('deduplication_key');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_event_destinations');
    }
};
