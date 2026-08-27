<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_attributions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketing_event_id')->unique()->constrained('marketing_events')->cascadeOnDelete();

            $table->string('first_touch_source')->nullable();
            $table->string('first_touch_medium')->nullable();
            $table->string('first_touch_campaign')->nullable();
            $table->string('first_touch_term')->nullable();
            $table->string('first_touch_content')->nullable();
            $table->text('first_touch_landing_url')->nullable();
            $table->timestamp('first_touch_at')->nullable();
            $table->string('first_touch_fbclid', 500)->nullable();
            $table->string('first_touch_gclid', 500)->nullable();
            $table->string('first_touch_ttclid', 500)->nullable();

            $table->string('last_touch_source')->nullable();
            $table->string('last_touch_medium')->nullable();
            $table->string('last_touch_campaign')->nullable();
            $table->string('last_touch_term')->nullable();
            $table->string('last_touch_content')->nullable();
            $table->text('last_touch_url')->nullable();
            $table->timestamp('last_touch_at')->nullable();
            $table->string('last_touch_fbclid', 500)->nullable();
            $table->string('last_touch_gclid', 500)->nullable();
            $table->string('last_touch_ttclid', 500)->nullable();

            $table->string('session_source')->nullable();
            $table->string('session_medium')->nullable();
            $table->string('session_campaign')->nullable();
            $table->string('session_term')->nullable();
            $table->string('session_content')->nullable();
            $table->string('session_fbclid', 500)->nullable();
            $table->string('session_gclid', 500)->nullable();
            $table->string('session_ttclid', 500)->nullable();

            $table->string('attribution_model')->nullable();

            $table->json('attribution_data')->nullable();

            $table->timestamps();

            $table->index(['first_touch_source', 'first_touch_campaign'], 'marketing_attributions_first_touch_idx');
            $table->index(['last_touch_source', 'last_touch_campaign'], 'marketing_attributions_last_touch_idx');
            $table->index(['session_source', 'session_campaign'], 'marketing_attributions_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_attributions');
    }
};
