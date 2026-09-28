<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            // The area pre-selected at checkout. At most one zone has it;
            // with none (or it disabled) checkout falls back to the first zone.
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        // Keep today's behaviour: the first zone was the implicit default.
        $firstId = DB::table('shipping_zones')->orderBy('sort_order')->orderBy('id')->value('id');

        if ($firstId) {
            DB::table('shipping_zones')->where('id', $firstId)->update(['is_default' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
