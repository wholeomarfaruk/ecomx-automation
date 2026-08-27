<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_sources', function (Blueprint $table) {
            $table->id();

            // 'meta', 'google', 'tiktok', 'organic', 'direct', 'referral', ...
            // — not constrained to App\Marketing\Enums\MarketingDestination
            // since a source can be a platform with no adapter (e.g. organic
            // search, direct, referral) unlike marketing_event_destinations.
            $table->string('platform', 50);

            $table->string('source')->nullable();
            $table->string('medium')->nullable();

            $table->string('name')->nullable();

            $table->timestamps();

            $table->unique(['platform', 'source', 'medium']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_sources');
    }
};
