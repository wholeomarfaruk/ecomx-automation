<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Enums\Sales\OrderPaymentType;
use App\Enums\Sales\PaymentMethod;
use App\Models\Account;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\Customer;
use App\Models\InventoryStockMovement;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Reverses the sale/COGS for whatever quantity of a completed order's items
 * has newly been marked returned — item-level partial-aware, same "only
 * post the delta since last time" idiom PostOrderCompletion uses. Only
 * applies to orders that have actually been completed (a real sale exists
 * to reverse); a pre-completion cancel never gets here since nothing was
 * ever posted for it.
 */
class PostOrderReturn
{
    public function __construct(
        protected PostSalesReturn $postSalesReturn,
        protected PostJournalEntry $postJournalEntry,
    ) {}

    public function handle(Order $order, string $entryDate, ?string $description = null): ?array
    {
        $order->loadMissing('items', 'customer');

        [$saleAmount, $costAmount] = $this->newlyReturnedAmounts($order);

        $isFullReturn = $order->items->every(
            fn (OrderItem $item) => $item->is_gift || (float) $item->returned_quantity >= (float) $item->quantity
        );

        $shippingAmount = $isFullReturn ? $this->unreversedShippingAmount($order) : 0.0;
        $reverseAmount = $saleAmount + $shippingAmount;

        if ($reverseAmount <= 0 && $costAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($order, $entryDate, $description, $reverseAmount, $costAmount) {
            $invoice = AccountsCustomerInvoice::where('order_id', $order->id)->first();

            // Split the reversed amount between what's still open on the
            // invoice (credit Receivable — nothing was ever collected for
            // it) and what's already been paid/allocated (credit Customer
            // Credit instead — crediting Receivable for an amount that's
            // already been paid down would push it negative for the wrong
            // reason; the customer is actually owed a refund, which is what
            // Customer Credit represents).
            $alreadyPaid = $invoice ? min($reverseAmount, (float) $invoice->amount_allocated) : 0.0;
            $stillOpen = $reverseAmount - $alreadyPaid;

            $result = $this->postSalesReturnSplit($order, $stillOpen, $alreadyPaid, $costAmount, $entryDate, $description);

            if ($invoice) {
                $this->drawDownInvoice($invoice, $reverseAmount, $alreadyPaid, $result['return']->id);
            }

            if ($alreadyPaid > 0) {
                // Record the store-credit "refund" on the order itself too,
                // same as RefundOrder/ReverseOrder do, so paid_amount/
                // due_amount (which recalculateTotals() derives purely from
                // OrderPayment rows) reflect that this much of what was
                // collected is no longer money the business is holding
                // against a live sale.
                $order->payments()->create([
                    'type'           => OrderPaymentType::REFUND,
                    'payment_method' => PaymentMethod::STORE_CREDIT,
                    'amount'         => $alreadyPaid,
                    'status'         => 'refunded',
                    'paid_at'        => now(),
                ]);

                $order->recalculateTotals();
            }

            return $result;
        });
    }

    /**
     * @return array{return: JournalEntry, stock: ?JournalEntry}
     */
    protected function postSalesReturnSplit(Order $order, float $stillOpen, float $alreadyPaid, float $costAmount, string $entryDate, ?string $description): array
    {
        $purposeSuffix = $this->nextPurposeSuffix($order);

        if ($alreadyPaid <= 0) {
            // Nothing was collected yet for the returned portion — plain
            // reversal against Receivable, same as PostSalesReturn always did.
            return $this->postSalesReturn->handle(
                order: $order,
                salesReturnAccountId: $this->accountId('4900'),
                receivableOrCashAccountId: $this->accountId('1100'),
                inventoryAccountId: $this->accountId('1200'),
                cogsAccountId: $this->accountId('5000'),
                saleAmount: $stillOpen,
                costAmount: $costAmount,
                entryDate: $entryDate,
                description: $description,
                purposeSuffix: $purposeSuffix,
            );
        }

        $customer = $order->customer;
        $lines = [
            ['account_id' => $this->accountId('4900'), 'debit' => $stillOpen + $alreadyPaid],
        ];

        if ($stillOpen > 0) {
            $lines[] = [
                'account_id'     => $this->accountId('1100'),
                'credit'         => $stillOpen,
                'subledger_type' => $customer ? Customer::class : null,
                'subledger_id'   => $customer?->id,
            ];
        }

        $lines[] = [
            'account_id'     => $this->accountId('2150'),
            'credit'         => $alreadyPaid,
            'subledger_type' => $customer ? Customer::class : null,
            'subledger_id'   => $customer?->id,
        ];

        $returnEntry = $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? "Sales return — Order #{$order->id}",
            'transaction_type' => TransactionType::SALES_RETURN->value,
            'source_type'      => Order::class,
            'source_id'        => $order->id,
            'purpose'          => 'sales_return' . $purposeSuffix,
        ], $lines);

        $stockEntry = null;
        if ($costAmount > 0) {
            $stockEntry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Stock returned — Order #{$order->id}",
                'transaction_type' => TransactionType::SALES_RETURN->value,
                'source_type'      => Order::class,
                'source_id'        => $order->id,
                'purpose'          => 'sales_return_stock' . $purposeSuffix,
            ], [
                ['account_id' => $this->accountId('1200'), 'debit' => $costAmount],
                ['account_id' => $this->accountId('5000'), 'credit' => $costAmount],
            ]);
        }

        return ['return' => $returnEntry, 'stock' => $stockEntry];
    }

    /**
     * On a full return, also reverse whatever shipping income was posted at
     * completion (not just item amounts) — the whole order is coming back,
     * so the delivery charge is reversed with it. A partial return leaves
     * shipping alone since delivery genuinely happened. Nets to 0 if
     * shipping was never posted, or has already been reversed once.
     */
    protected function unreversedShippingAmount(Order $order): float
    {
        $shippingId = Account::where('code', '4050')->value('id');

        if (! $shippingId) {
            return 0.0;
        }

        $entryIds = JournalEntry::query()
            ->where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->where('purpose', 'like', 'sale%')
            ->pluck('id');

        $posted = (float) JournalEntryLine::query()
            ->whereIn('journal_entry_id', $entryIds)
            ->where('account_id', $shippingId)
            ->sum('credit');

        $reversedEntryIds = JournalEntry::query()
            ->where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->where('purpose', 'like', 'sales_return%')
            ->pluck('id');

        $alreadyReversed = (float) JournalEntryLine::query()
            ->whereIn('journal_entry_id', $reversedEntryIds)
            ->where('account_id', $shippingId)
            ->sum('debit');

        return max(0.0, round($posted - $alreadyReversed, 2));
    }

    protected function drawDownInvoice(AccountsCustomerInvoice $invoice, float $reverseAmount, float $alreadyPaid, int $returnEntryId): void
    {
        if ($alreadyPaid > 0) {
            AccountsPaymentAllocation::create([
                'payment_journal_entry_id' => $returnEntryId,
                'allocatable_type'         => AccountsCustomerInvoice::class,
                'allocatable_id'           => $invoice->id,
                'amount'                   => -$alreadyPaid,
            ]);

            $invoice->decrement('amount_allocated', $alreadyPaid);
        }

        $invoice->decrement('amount', $reverseAmount);
        $invoice->refresh();

        $status = match (true) {
            (float) $invoice->amount <= 0.01 => 'written_off', // fully returned — nothing left to invoice for
            $invoice->amountDue() <= 0.01 => 'paid',
            $invoice->amount_allocated > 0 => 'partial',
            default => 'open',
        };

        $invoice->update(['status' => $status]);
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function newlyReturnedAmounts(Order $order): array
    {
        $saleAmount = 0.0;
        $costAmount = 0.0;

        foreach ($order->items as $item) {
            if ($item->is_gift) {
                continue;
            }

            $returnedQty = (float) $item->returned_quantity;

            if ($returnedQty <= 0) {
                continue;
            }

            $alreadyReturned = $item->product_id
                ? abs((float) InventoryStockMovement::query()
                    ->where('reference_type', OrderItem::class)
                    ->where('reference_id', $item->id)
                    ->where('type', 'return')
                    ->sum('quantity'))
                : 0.0;

            $newlyReturned = $returnedQty - $alreadyReturned;

            if ($newlyReturned <= 0) {
                continue;
            }

            $orderedQty = (float) $item->quantity;
            $unitPrice = $orderedQty > 0 ? (float) $item->total_amount / $orderedQty : 0.0;
            $unitCost = (float) $item->purchase_price;

            $saleAmount += round($unitPrice * $newlyReturned, 2);
            $costAmount += round($unitCost * $newlyReturned, 2);
        }

        return [$saleAmount, $costAmount];
    }

    protected function nextPurposeSuffix(Order $order): ?string
    {
        $count = JournalEntry::query()
            ->where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->where('purpose', 'like', 'sales_return%')
            ->count();

        return $count > 0 ? '_' . now()->timestamp : null;
    }

    protected function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Chart of accounts is missing account code {$code}.");
    }
}
