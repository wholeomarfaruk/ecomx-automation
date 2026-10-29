<?php

namespace App\Actions\Sales;

use App\Actions\Accounts\PostLostStock;
use App\Actions\Accounts\PostOrderReturn;
use App\Models\AccountsCustomerInvoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Records how much of each item came back (returned_quantity) — the one
 * path for the order page's Save Returns and Orders → "Return received".
 * Reverses that slice's Sale/COGS via PostOrderReturn when a sale was
 * posted (partial-aware — only the delta since the last save), restocks
 * it, moves the status (all back → Returned; part back on a delivered
 * order → partial fulfillment), takes the returned units off the total
 * and lets a paid order's payment status follow the new due.
 *
 * Lost by courier: lost_quantity is the part of returned_quantity that
 * never came back — off the customer's total like any return, but not
 * restocked; written off at cost (PostLostStock) and claimed from the
 * courier through a "Lost by courier" Issue (ReportCourierLoss).
 */
class RecordOrderReturns
{
    public function __construct(protected StockService $stockService) {}

    /**
     * @param  array<int, float|string>  $returnedQuantities  item id => returned quantity, lost units included (missing items → 0)
     * @param  array<int, float|string>|null  $lostQuantities  item id => how much of that the courier lost; null keeps what's saved
     */
    public function handle(Order $order, array $returnedQuantities, ?array $lostQuantities = null): void
    {
        $order->loadMissing('items');
        $restockOnReturn = (bool) Setting::get('restock_on_cancel_or_return', true, 'inventory');
        $hasBeenCompleted = AccountsCustomerInvoice::where('order_id', $order->id)->exists();
        $lostChanged = false;

        DB::transaction(function () use ($order, $returnedQuantities, $lostQuantities, $restockOnReturn, $hasBeenCompleted, &$lostChanged) {
            foreach ($order->items as $item) {
                $qty = max(0.0, min((float) ($returnedQuantities[$item->id] ?? 0), (float) $item->quantity));
                $lost = max(0.0, min((float) ($lostQuantities === null ? $item->lost_quantity : ($lostQuantities[$item->id] ?? 0)), $qty));

                $lostChanged = $lostChanged || abs($lost - (float) $item->lost_quantity) > 0.0005;

                $item->update(['returned_quantity' => $qty, 'lost_quantity' => $lost]);
            }

            // Reverses only what wasn't reversed yet (order_items.return_posted_quantity).
            if ($hasBeenCompleted) {
                app(PostOrderReturn::class)->handle($order->fresh(['items']), now()->toDateString());
            }

            if ($restockOnReturn) {
                foreach ($order->items as $item) {
                    $fresh = $item->fresh();

                    if ((float) $fresh->returned_quantity > 0) {
                        // Only what physically came back — lost units stay out.
                        $this->stockService->restockReturnedItem($item, (float) $fresh->returned_quantity - (float) $fresh->lost_quantity);
                    }
                }
            }

            if ($lostChanged && (bool) Setting::get('accounts_enabled', false, 'modules')) {
                app(PostLostStock::class)->handle($order->fresh(['items.batchAllocations']), now()->toDateString());
            }
        });

        if ($lostChanged) {
            app(ReportCourierLoss::class)->handle($order->fresh(['items', 'courierShipments.courier']));
        }

        $order->syncReturnStatus();

        // Bookings follow: returned units no longer need reserving (and none
        // at all once the order is Returned). Inventory off: the lines' own
        // stock_quantity re-syncs instead.
        $this->stockService->syncBookings($order->fresh('items'), 'unbooked_returned');

        // Everything back on an order whose sale was never posted: undo its
        // packing too (the returned units are already restocked), so it can't
        // be costed later or re-packed without deducting again.
        if ($order->status->isClosed() && ! $order->salePosted()) {
            $this->stockService->releaseOrder($order->fresh('items'));
        }

        // Returned units come off the total — e.g. 3 sent, 1 kept and paid for → nothing due.
        $order->recalculateTotals();
        $order->refresh()->syncPaymentStatus();
    }

    /** What a return changes, for the order's activity log (before/after). */
    public static function snapshot(Order $order): array
    {
        return [
            'returned' => $order->items->mapWithKeys(fn (OrderItem $i) => [$i->product_name => (float) $i->returned_quantity])->all(),
            'lost'     => $order->items->filter(fn (OrderItem $i) => (float) $i->lost_quantity > 0)->mapWithKeys(fn (OrderItem $i) => [$i->product_name => (float) $i->lost_quantity])->all(),
            'shipping' => (float) $order->shipping_amount,
            'discount' => (float) $order->discount_amount,
            'total'    => (float) $order->total_amount,
            'paid'     => (float) $order->paid_amount,
            'due'      => (float) $order->due_amount,
            'status'   => $order->status->value,
            'payment'  => $order->payment_status->value,
        ];
    }
}
