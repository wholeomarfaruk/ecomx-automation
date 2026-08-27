<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_utm_rules', function (Blueprint $table) {
            $table->id();

            // Which UTM field this rule normalizes: utm_source, utm_medium,
            // utm_campaign, utm_term, utm_content.
            $table->string('field', 30);

            // Case-insensitive value to match (e.g. "fb", "ig", "Facebook Ads").
            $table->string('match_value');

            // Canonical value to store instead (e.g. "facebook").
            $table->string('normalized_value');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);

            $table->timestamps();

            $table->index(['field', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_utm_rules');
    }
};
