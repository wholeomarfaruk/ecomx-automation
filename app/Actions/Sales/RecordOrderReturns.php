<?php

namespace App\Actions\Sales;

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
 */
class RecordOrderReturns
{
    public function __construct(protected StockService $stockService) {}

    /** @param  array<int, float|string>  $returnedQuantities  item id => returned quantity (missing items → 0) */
    public function handle(Order $order, array $returnedQuantities): void
    {
        $order->loadMissing('items');
        $restockOnReturn = (bool) Setting::get('restock_on_cancel_or_return', true, 'inventory');
        $hasBeenCompleted = AccountsCustomerInvoice::where('order_id', $order->id)->exists();

        DB::transaction(function () use ($order, $returnedQuantities, $restockOnReturn, $hasBeenCompleted) {
            foreach ($order->items as $item) {
                $qty = min((float) ($returnedQuantities[$item->id] ?? 0), (float) $item->quantity);

                $item->update(['returned_quantity' => max(0, $qty)]);
            }

            // Reverses only what wasn't reversed yet (order_items.return_posted_quantity).
            if ($hasBeenCompleted) {
                app(PostOrderReturn::class)->handle($order->fresh(['items']), now()->toDateString());
            }

            if ($restockOnReturn) {
                foreach ($order->items as $item) {
                    $qty = (float) $item->fresh()->returned_quantity;

                    if ($qty > 0) {
                        $this->stockService->restockReturnedItem($item, $qty);
                    }
                }
            }
        });

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
