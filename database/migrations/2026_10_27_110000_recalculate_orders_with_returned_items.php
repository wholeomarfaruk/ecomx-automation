<?php

use App\Enums\Sales\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Returned units now come off the order total (Order::returnedAmount()).
     * Orders that already had returns keep their old total/due until
     * recalculated — do that once, and bring a paid order's payment status
     * in line (e.g. 3 sent, 1 kept and paid for → Paid, nothing due).
     */
    public function up(): void
    {
        Order::query()
            ->whereHas('items', fn ($q) => $q->where('returned_quantity', '>', 0))
            ->each(function (Order $order) {
                $order->recalculateTotals();

                if ((float) $order->paid_amount > 0) {
                    $order->update(['payment_status' => $order->due_amount > 0 ? PaymentStatus::PARTIAL : PaymentStatus::PAID]);
                }
            });
    }

    public function down(): void
    {
        // Not reversible — the old totals aren't recorded.
    }
};
