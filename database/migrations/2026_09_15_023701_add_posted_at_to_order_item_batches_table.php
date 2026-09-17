<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks when this batch allocation's cost was actually included in a
     * posted COGS journal entry at order completion — distinct from
     * physical stock deduction, which happens earlier at pack time. Lets
     * PostOrderCompletion tell "packed but not yet cost-recognized" apart
     * from "already recognized," instead of (incorrectly) inferring it from
     * the physical stock movement, which packing already wrote before
     * completion ever runs.
     */
    public function up(): void
    {
        Schema::table('order_item_batches', function (Blueprint $table) {
            $table->timestamp('posted_at')->nullable()->after('unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('order_item_batches', function (Blueprint $table) {
            $table->dropColumn('posted_at');
        });
    }
};
