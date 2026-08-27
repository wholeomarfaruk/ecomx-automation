<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_attributions', function (Blueprint $table) {
            $table->string('first_touch_landing_path')->nullable()->after('first_touch_landing_url');
            $table->string('last_touch_landing_path')->nullable()->after('last_touch_url');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_attributions', function (Blueprint $table) {
            $table->dropColumn(['first_touch_landing_path', 'last_touch_landing_path']);
        });
    }
};
