<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_events', function (Blueprint $table) {
            $table->id();

            $table->uuid('event_id')->unique();

            $table->string('event_name', 100);
            $table->string('event_version', 20)->nullable();

            $table->timestamp('occurred_at');
            $table->timestamp('received_at')->useCurrent();

            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('marketing_sessions')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->text('page_url')->nullable();
            $table->string('page_path')->nullable();
            $table->string('page_title')->nullable();

            $table->text('referrer_url')->nullable();
            $table->string('referrer_domain')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->string('currency', 3)->nullable();
            $table->decimal('value', 18, 4)->nullable();

            $table->string('content_type')->nullable();
            $table->string('content_id')->nullable();
            $table->string('content_name')->nullable();

            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('campaign')->nullable();
            $table->string('term')->nullable();
            $table->string('content')->nullable();

            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();

            $table->string('fbclid', 500)->nullable();
            $table->string('gclid', 500)->nullable();
            $table->string('ttclid', 500)->nullable();

            // Where the canonical event originated (not where it's delivered to —
            // that's marketing_event_destinations' concern).
            $table->string('event_source')->nullable();
            $table->string('event_channel')->nullable();

            $table->json('identity_data')->nullable();
            $table->json('device_data')->nullable();
            $table->json('page_data')->nullable();
            $table->json('commerce_data')->nullable();
            $table->json('custom_data')->nullable();
            $table->json('context_data')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['event_name', 'occurred_at']);
            $table->index(['device_id', 'occurred_at']);
            $table->index(['customer_id', 'occurred_at']);
            $table->index(['session_id', 'occurred_at']);
            $table->index('order_id');
            $table->index(['ip_address', 'occurred_at']);
            $table->index(['utm_source', 'utm_campaign', 'occurred_at']);
            $table->index(['source', 'medium', 'occurred_at']);
            $table->index(['content_type', 'content_id', 'occurred_at']);
            $table->index(['fbclid', 'occurred_at']);
            $table->index(['gclid', 'occurred_at']);
            $table->index(['ttclid', 'occurred_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_events');
    }
};
