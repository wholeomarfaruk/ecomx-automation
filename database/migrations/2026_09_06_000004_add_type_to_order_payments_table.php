<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * order_payments has never distinguished money coming in (a payment)
     * from money going back out (a refund) — Order::recalculateTotals()
     * simply sums every 'paid' status row into paid_amount, so a refund
     * could only ever be modeled as another positive payment, which is
     * wrong. Existing rows are all real payments received, so they
     * backfill to 'payment'.
     */
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->string('type', 20)->default('payment')->after('order_id');
            // payment, refund
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
