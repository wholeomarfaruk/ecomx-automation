<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campaign reports filter on last_touch_campaign alone; the existing
        // (last_touch_source, last_touch_campaign) index can't serve that.
        Schema::table('marketing_attributions', function (Blueprint $table) {
            $table->index('last_touch_campaign', 'marketing_attributions_last_touch_campaign_idx');
        });

        // Campaigns now register themselves from traffic (CampaignDiscovery),
        // keyed on campaign_key — unique so concurrent requests can't create
        // the same one twice. Keep the oldest row of any existing duplicates.
        $duplicates = DB::table('marketing_campaigns')
            ->whereNotNull('campaign_key')
            ->select('campaign_key', DB::raw('MIN(id) AS keep_id'))
            ->groupBy('campaign_key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('marketing_campaigns')
                ->where('campaign_key', $duplicate->campaign_key)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->unique('campaign_key', 'marketing_campaigns_campaign_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->dropUnique('marketing_campaigns_campaign_key_unique');
        });

        Schema::table('marketing_attributions', function (Blueprint $table) {
            $table->dropIndex('marketing_attributions_last_touch_campaign_idx');
        });
    }
};
