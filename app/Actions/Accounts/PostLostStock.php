<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Writes off units the courier lost (order_items.lost_quantity) at cost —
 * Dr Stock Loss (5910) / Cr Inventory (1200). Only the change since the
 * last call is posted (lost_posted_quantity), so marking more lost later
 * adds to it and un-marking (the parcel turned up) posts the reverse.
 * What the courier pays back for them is a separate entry, made when the
 * Issue is resolved (ResolveIssue).
 */
class PostLostStock
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(Order $order, string $entryDate): void
    {
        $lossId = Account::where('code', '5910')->value('id');
        $inventoryId = Account::where('code', '1200')->value('id');

        if (! $lossId || ! $inventoryId) {
            return;
        }

        $order->loadMissing('items.batchAllocations');

        DB::transaction(function () use ($order, $entryDate, $lossId, $inventoryId) {
            foreach ($order->items as $item) {
                $delta = round((float) $item->lost_quantity - (float) $item->lost_posted_quantity, 3);

                if (abs($delta) < 0.0005) {
                    continue;
                }

                $amount = round($this->unitCost($item) * abs($delta), 2);

                if ($amount > 0) {
                    $this->postJournalEntry->handle([
                        'entry_date'       => $entryDate,
                        'description'      => ($delta > 0 ? 'Lost by courier' : 'Found — lost write-off reversed') . " — {$item->product_name}, Order #{$order->id}",
                        'transaction_type' => TransactionType::SALES_RETURN->value,
                        'source_type'      => Order::class,
                        'source_id'        => $order->id,
                        // One entry per change — marking more lost later posts only the difference.
                        'purpose'          => "lost_stock_item_{$item->id}_" . now()->format('YmdHisu'),
                    ], $delta > 0 ? [
                        ['account_id' => $lossId, 'debit' => $amount],
                        ['account_id' => $inventoryId, 'credit' => $amount],
                    ] : [
                        ['account_id' => $inventoryId, 'debit' => $amount],
                        ['account_id' => $lossId, 'credit' => $amount],
                    ]);
                }

                $item->update(['lost_posted_quantity' => $item->lost_quantity]);
            }
        });
    }

    /** What one shipped unit cost — its packed batches, else the line's purchase price (same as the sale's COGS). */
    protected function unitCost(OrderItem $item): float
    {
        $allocations = $item->batchAllocations;
        $allocatedQty = (float) $allocations->sum('quantity');

        return $allocatedQty > 0
            ? (float) $allocations->sum(fn ($a) => (float) $a->quantity * (float) $a->unit_cost) / $allocatedQty
            : (float) $item->purchase_price;
    }
}
