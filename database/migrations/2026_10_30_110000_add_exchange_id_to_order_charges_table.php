<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Charges an exchange adds (its delivery charge and any extra charges)
     * belong to that exchange: dropped with it if it fails / is cancelled,
     * and left alone by the order's Charges & Discount editor. Existing
     * exchange delivery charges (order_exchanges.order_charge_id) are
     * linked back. Additive — every existing charge stays an order charge.
     */
    public function up(): void
    {
        Schema::table('order_charges', function (Blueprint $table) {
            $table->foreignId('exchange_id')->nullable()->after('order_id')->constrained('order_exchanges')->nullOnDelete();
        });

        DB::table('order_exchanges')->whereNotNull('order_charge_id')->orderBy('id')
            ->each(fn ($exchange) => DB::table('order_charges')->where('id', $exchange->order_charge_id)->update(['exchange_id' => $exchange->id]));
    }

    public function down(): void
    {
        Schema::table('order_charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exchange_id');
        });
    }
};
