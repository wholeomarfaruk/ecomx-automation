<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketing_source_id')->constrained('marketing_sources')->cascadeOnDelete();

            // Matches marketing_events.utm_campaign / marketing_attributions.*_campaign
            // — the join key back to already-recorded events, since those
            // store the raw utm_campaign string rather than a campaign_id
            // (campaigns can be named/registered after traffic already
            // exists). Nullable because a campaign can be pre-registered
            // with only its external platform IDs, before any UTM traffic
            // has been tagged to it yet.
            $table->string('campaign_key')->nullable();

            // Platform-side identifiers — present only for campaigns that
            // originate from Meta/Google/TikTok's own campaign objects, not
            // for hand-rolled UTM-only campaigns.
            $table->string('external_campaign_id')->nullable();
            $table->string('external_campaign_name')->nullable();

            $table->string('status', 30)->default('active');

            $table->timestamps();

            $table->index(['marketing_source_id', 'campaign_key']);
            $table->unique(['marketing_source_id', 'external_campaign_id'], 'marketing_campaigns_source_external_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
