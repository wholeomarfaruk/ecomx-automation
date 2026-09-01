<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A customer's preferred courier — pre-selected when staff book a
     * shipment for one of their orders, and used automatically (per
     * customer) when booking shipments in bulk across many orders.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('default_courier_id')->nullable()->after('customer_group_id')->constrained('couriers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_courier_id');
        });
    }
};
