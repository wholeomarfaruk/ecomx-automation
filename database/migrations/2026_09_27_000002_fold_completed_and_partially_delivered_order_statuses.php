<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * OrderStatus no longer has COMPLETED or PARTIALLY_DELIVERED — a
     * completed order is now just DELIVERED (fulfilled), and a partial
     * delivery is DELIVERED with a partial fulfillment.
     */
    public function up(): void
    {
        DB::table('orders')->where('status', 'completed')->update([
            'status'             => 'delivered',
            'fulfillment_status' => 'fulfilled',
        ]);

        DB::table('orders')->where('status', 'partially_delivered')->update([
            'status'             => 'delivered',
            'fulfillment_status' => 'partial',
        ]);
    }

    public function down(): void
    {
        // Not reversible — which delivered orders used to be completed isn't recorded.
    }
};
