<?php

namespace App\Actions\Accounts;

use App\Exceptions\Accounts\DuplicateJournalEntryException;
use App\Models\Account;
use App\Models\AccountsCustomerAdvance;
use App\Models\InventoryStockMovement;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBatch;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Everything that happens the moment an order is marked "completed" —
 * physical stock deduction, Sale+COGS+shipping recognition, and converting
 * any pre-completion customer advance into a payment against the invoice
 * that posting creates. Partial-completion aware: only the delivered
 * quantity that hasn't already been posted for gets deducted/recognized, so
 * calling this again after more items are delivered posts just the new
 * delta (same "post only what's new" idiom StockService::
 * restockReturnedItem() already uses for returns). Skipped for guest orders
 * — AccountsCustomerInvoice requires a customer.
 */
class PostOrderCompletion
{
    public function __construct(
        protected PostSaleWithCogs $postSaleWithCogs,
        protected ApplyCustomerAdvance $applyCustomerAdvance,
        protected StockService $stockService,
    ) {}

    /**
     * @throws \RuntimeException if a non-combo item has delivered quantity
     *   that hasn't been packed from a specific batch yet — batch selection
     *   is mandatory before that quantity's cost can be recognized (no
     *   silent fallback to a flat purchase_price guess).
     */
    public function handle(Order $order, string $entryDate): void
    {
        if (! $order->customer_id) {
            return;
        }

        $order->loadMissing('items', 'customer');

        $this->assertFullyPacked($order);

        [$itemAmounts, $itemCogsAmounts, $itemQuantities, $postedAllocationIds] = $this->newlyDeliveredAmounts($order);

        if (empty($itemAmounts) && empty($itemCogsAmounts)) {
            return;
        }

        DB::transaction(function () use ($order, $entryDate, $itemAmounts, $itemCogsAmounts, $itemQuantities, $postedAllocationIds) {
            // Physical stock for packed quantity was already deducted at
            // pack time (PackOrderItem -> StockService::decreaseFromBatch()),
            // batch-and-all. commitOrder() here only covers items with no
            // product_id (combo lines — stock derived from components, per
            // its own existing skip logic) — everything else's physical
            // deduction already happened at pack time, so their quantity is
            // deliberately absent from $itemQuantities here.
            $this->stockService->commitOrder($order, $itemQuantities);

            $alreadyShipped = JournalEntry::query()
                ->where('source_type', Order::class)
                ->where('source_id', $order->id)
                ->where('purpose', 'like', 'sale%')
                ->exists();

            $purposeSuffix = $alreadyShipped ? '_' . (now()->timestamp) : null;

            $shippingAmount = $alreadyShipped
                ? null
                : max(0.0, (float) $order->shipping_amount - (float) $order->shipping_discount);

            // Order-level discount / tax / extra charges are recognized once,
            // with the first sale entry (same as shipping), so the customer
            // invoice equals the order total.
            $discountAmount = $alreadyShipped ? 0.0 : (float) $order->discount_amount;
            $taxAmount = $alreadyShipped ? 0.0 : (float) $order->tax_amount;
            $chargesAmount = $alreadyShipped ? 0.0 : (float) $order->charges_amount;

            try {
                $result = $this->postSaleWithCogs->handle(
                    order: $order,
                    receivableOrCashAccountId: $this->accountId('1100'),
                    salesAccountId: $this->accountId('4000'),
                    inventoryAccountId: $this->accountId('1200'),
                    cogsAccountId: $this->accountId('5000'),
                    entryDate: $entryDate,
                    shippingAmount: $shippingAmount,
                    shippingIncomeAccountId: $shippingAmount ? $this->accountId('4050') : null,
                    purposeSuffix: $purposeSuffix,
                    itemAmounts: $itemAmounts,
                    itemCogsAmounts: $itemCogsAmounts,
                    discountAmount: $discountAmount,
                    discountAccountId: $discountAmount > 0 ? $this->accountId('4910') : null,
                    taxAmount: $taxAmount,
                    taxAccountId: $taxAmount > 0 ? $this->accountId('2300') : null,
                    chargesAmount: $chargesAmount,
                    chargesAccountId: $chargesAmount > 0 ? $this->accountId('4100') : null,
                );
            } catch (DuplicateJournalEntryException) {
                return;
            }

            if (! empty($postedAllocationIds)) {
                OrderItemBatch::whereIn('id', $postedAllocationIds)->update(['posted_at' => now()]);
            }

            $invoice = $result['invoice'];

            if ($invoice) {
                $this->applyAdvances($order, $invoice, $entryDate);
            }

            $this->stockService->releaseBooking($order, 'unbooked_completed');
        });
    }

    /**
     * Applies any held/partial customer advances for this order against the
     * invoice, oldest first, up to the invoice's remaining balance.
     */
    protected function applyAdvances(Order $order, \App\Models\AccountsCustomerInvoice $invoice, string $entryDate): void
    {
        $advances = AccountsCustomerAdvance::where('order_id', $order->id)
            ->whereIn('status', ['held', 'partial'])
            ->oldest()
            ->get();

        foreach ($advances as $advance) {
            if ($invoice->amountDue() <= 0.01) {
                break;
            }

            $this->applyCustomerAdvance->handle(
                advance: $advance,
                invoice: $invoice,
                receivableAccountId: $this->accountId('1100'),
                entryDate: $entryDate,
            );

            $invoice->refresh();
        }
    }

    /**
     * Every non-gift item with a product_id (combo lines are exempt — their
     * stock is derived from components, never batch-tracked) must have its
     * full quantity covered by OrderItemBatch allocations before this order
     * can complete. Prevents the exact silent gap this feature exists to
     * close: an item shipping with no batch picked would otherwise either
     * post zero COGS (if purchase_price was also never set) or a guessed
     * flat cost unrelated to what was actually shipped.
     */
    protected function assertFullyPacked(Order $order): void
    {
        $unpacked = [];

        foreach ($order->items as $item) {
            if ($item->is_gift || ! $item->product_id) {
                continue;
            }

            $packedQty = (float) $item->batchAllocations()->sum('quantity');
            $orderedQty = (float) $item->quantity;

            if ($packedQty + 0.001 < $orderedQty) {
                $unpacked[] = "{$item->product_name} ({$packedQty}/{$orderedQty} packed)";
            }
        }

        if (! empty($unpacked)) {
            throw new \RuntimeException(
                "Order #{$order->id} has unpacked items — pick a batch for each before completing: " . implode(', ', $unpacked)
            );
        }
    }

    /**
     * Computes each item's newly-recognizable sale/cogs amount. The two
     * signals this reads are deliberately different things:
     *  - Physical stock deduction happens at PACK time (PackOrderItem), so
     *    for a product-tracked item "what's newly delivered" is irrelevant
     *    here — what matters is which OrderItemBatch allocations haven't
     *    had their cost RECOGNIZED yet (posted_at IS NULL). This is what
     *    lets calling completion again after more items are packed post
     *    only the new batches' cost, not re-post or skip everything.
     *  - Combo lines (no product_id, never go through packing) keep the
     *    original delivered_quantity-vs-physical-movement delta logic,
     *    priced off the item's own flat purchase_price snapshot.
     *
     * @return array{0: array<int, float>, 1: array<int, float>, 2: array<int, float>, 3: array<int, int>}
     */
    protected function newlyDeliveredAmounts(Order $order): array
    {
        $itemAmounts = [];
        $itemCogsAmounts = [];
        $itemQuantities = [];
        $postedAllocationIds = [];

        foreach ($order->items as $item) {
            if ($item->is_gift) {
                continue;
            }

            $orderedQty = (float) $item->quantity;
            $unitPrice = $orderedQty > 0 ? (float) $item->total_amount / $orderedQty : 0.0;

            if ($item->product_id) {
                $unposted = $item->batchAllocations()->whereNull('posted_at')->get();

                if ($unposted->isEmpty()) {
                    continue;
                }

                $newlyDelivered = (float) $unposted->sum('quantity');
                $cogsAmount = round((float) $unposted->sum(fn ($a) => $a->quantity * $a->unit_cost), 2);

                $postedAllocationIds = array_merge($postedAllocationIds, $unposted->pluck('id')->all());
            } else {
                $deliveredQty = (float) $item->delivered_quantity > 0
                    ? min((float) $item->delivered_quantity, $orderedQty)
                    : $orderedQty;

                $alreadyCommitted = abs((float) InventoryStockMovement::query()
                    ->where('reference_type', OrderItem::class)
                    ->where('reference_id', $item->id)
                    ->where('type', 'sale')
                    ->sum('quantity'));

                $newlyDelivered = $deliveredQty - $alreadyCommitted;

                if ($newlyDelivered <= 0) {
                    continue;
                }

                $cogsAmount = round((float) $item->purchase_price * $newlyDelivered, 2);
            }

            $itemAmounts[$item->id] = round($unitPrice * $newlyDelivered, 2);
            $itemCogsAmounts[$item->id] = $cogsAmount;

            if (! $item->product_id) {
                $itemQuantities[$item->id] = $newlyDelivered;
            }
        }

        return [$itemAmounts, $itemCogsAmounts, $itemQuantities, $postedAllocationIds];
    }

    protected function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Chart of accounts is missing account code {$code}.");
    }
}
