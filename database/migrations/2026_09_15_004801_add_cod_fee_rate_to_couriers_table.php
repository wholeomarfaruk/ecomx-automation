<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Percentage of the COD amount this courier charges as their collection
     * fee (e.g. 1.00 for 1%) — used to compute an order's total courier
     * cost (delivery fee + COD fee) when a shipment is booked.
     */
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->decimal('cod_fee_rate', 5, 2)->default(0)->after('webhook_secret');
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn('cod_fee_rate');
        });
    }
};
