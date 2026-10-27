<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Closed orders (cancelled/returned/refunded) now always have due_amount 0
     * — Order's saving hook keeps it that way going forward. A paid return
     * (customer paid only the delivery charge) was left Partial; it's Paid now.
     */
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'returned')
            ->where('payment_status', 'partial')
            ->where('paid_amount', '>', 0)
            ->update(['payment_status' => 'paid']);

        DB::table('orders')
            ->whereIn('status', ['cancelled', 'returned', 'refunded'])
            ->update(['due_amount' => 0]);
    }

    public function down(): void
    {
        // Not reversible — the old due amounts aren't recorded (reopening an order recomputes its due).
    }
};
