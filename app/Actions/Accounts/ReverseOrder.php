<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Enums\Sales\OrderPaymentType;
use App\Enums\Sales\PaymentMethod;
use App\Models\Account;
use App\Models\AccountsCustomerAdvance;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Full accounting reversal for an order moving to cancelled/returned:
 * reverses the recognized Sale and COGS (mirroring PostSalesReturn, so a
 * cancelled/returned order has zero net profit impact regardless of how
 * much of it was actually paid), and turns any amount the customer already
 * paid into Customer Credit rather than a cash refund — cash was possibly
 * already reconciled/settled, so this path never touches a cash account.
 * A real cash refund, if wanted, stays a separate manual step via
 * RefundOrder.
 *
 * Idempotent per order via purpose='order_reversal' — safe to call again
 * (e.g. re-saving the same status) without double-posting.
 */
class ReverseOrder
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(Order $order, string $entryDate, ?string $description = null): ?JournalEntry
    {
        return DB::transaction(function () use ($order, $entryDate, $description) {
            if (JournalEntry::query()
                ->where('source_type', Order::class)
                ->where('source_id', $order->id)
                ->whereIn('purpose', ['order_reversal', 'order_reversal_credit'])
                ->exists()) {
                return null;
            }

            $order->loadMissing('items', 'customer');

            // If PostOrderReturn (or an earlier call to this method) already
            // reversed the sale/COGS for this order — a completed order
            // being fully returned goes through PostOrderReturn first, since
            // that's the partial-delivery-aware path — don't reverse it
            // again here; only the paid-amount-to-Customer-Credit
            // conversion below still needs to run.
            $alreadyReversedBySaleReturn = JournalEntry::query()
                ->where('source_type', Order::class)
                ->where('source_id', $order->id)
                ->where('purpose', 'like', 'sales_return%')
                ->exists();

            $saleAmount = $alreadyReversedBySaleReturn ? 0.0
                : (float) $order->items->sum(fn ($item) => $item->is_gift ? 0 : (float) $item->total_amount);
            $cogsAmount = $alreadyReversedBySaleReturn ? 0.0
                : (float) $order->items->sum(fn ($item) => $item->is_gift ? 0 : (float) $item->quantity * (float) $item->purchase_price);

            $salesReturnAccountId = $this->accountId('4900');
            $receivableAccountId  = $this->accountId('1100');
            $inventoryAccountId   = $this->accountId('1200');
            $cogsAccountId        = $this->accountId('5000');
            $customerCreditAccountId = $this->accountId('2150');
            $customerAdvanceAccountId = $this->accountId('2160');

            $customer = $order->customer;
            $returnEntry = null;

            $invoice = AccountsCustomerInvoice::where('order_id', $order->id)->first();

            // A completed order has a real Sale/COGS to reverse. A
            // pre-completion cancel (the common case under the book-at-
            // confirm/post-at-completed model) never posted either, so
            // there's nothing here — only the paid-amount conversion below
            // applies.
            if ($saleAmount > 0 || $cogsAmount > 0) {
                $returnEntry = $this->postJournalEntry->handle([
                    'entry_date'       => $entryDate,
                    'description'      => $description ?? "Order #{$order->id} cancelled/returned — sale reversed",
                    'transaction_type' => TransactionType::SALES_RETURN->value,
                    'source_type'      => Order::class,
                    'source_id'        => $order->id,
                    'purpose'          => 'order_reversal',
                ], [
                    ['account_id' => $salesReturnAccountId, 'debit' => $saleAmount],
                    [
                        'account_id'     => $receivableAccountId,
                        'credit'         => $saleAmount,
                        'subledger_type' => $customer ? Customer::class : null,
                        'subledger_id'   => $customer?->id,
                    ],
                ]);

                if ($cogsAmount > 0) {
                    $this->postJournalEntry->handle([
                        'entry_date'       => $entryDate,
                        'description'      => $description ?? "Order #{$order->id} cancelled/returned — stock restored",
                        'transaction_type' => TransactionType::SALES_RETURN->value,
                        'source_type'      => Order::class,
                        'source_id'        => $order->id,
                        'purpose'          => 'order_reversal_stock',
                    ], [
                        ['account_id' => $inventoryAccountId, 'debit' => $cogsAmount],
                        ['account_id' => $cogsAccountId, 'credit' => $cogsAmount],
                    ]);
                }

                if ($invoice && $invoice->amount_allocated > 0) {
                    $applied = (float) $invoice->amount_allocated;

                    AccountsPaymentAllocation::create([
                        'payment_journal_entry_id' => $returnEntry->id,
                        'allocatable_type'         => AccountsCustomerInvoice::class,
                        'allocatable_id'           => $invoice->id,
                        'amount'                   => -$applied,
                    ]);

                    $invoice->decrement('amount_allocated', $applied);
                }

                $invoice?->refresh();
                $invoice?->update(['status' => 'written_off']);
            }

            $paidAmount = (float) $order->paid_amount;

            // If PostOrderReturn already ran (completed-order path), it
            // already split the reversed amount between Receivable (unpaid
            // portion) and Customer Credit (paid portion) itself — nothing
            // left to convert here. This block only applies to a
            // pre-completion cancel, where the paid amount is sitting in
            // Customer Advance and has never touched Receivable or Customer
            // Credit at all yet.
            if ($paidAmount > 0 && ! $alreadyReversedBySaleReturn) {
                $debitAccountId = $invoice ? $receivableAccountId : $customerAdvanceAccountId;

                $this->postJournalEntry->handle([
                    'entry_date'       => $entryDate,
                    'description'      => "Order #{$order->id} cancelled/returned — paid amount credited to customer account",
                    'transaction_type' => TransactionType::CREDIT_NOTE->value,
                    'source_type'      => Order::class,
                    'source_id'        => $order->id,
                    'purpose'          => 'order_reversal_credit',
                ], [
                    [
                        'account_id'     => $debitAccountId,
                        'debit'          => $paidAmount,
                        'subledger_type' => $customer ? Customer::class : null,
                        'subledger_id'   => $customer?->id,
                    ],
                    [
                        'account_id'     => $customerCreditAccountId,
                        'credit'         => $paidAmount,
                        'subledger_type' => $customer ? Customer::class : null,
                        'subledger_id'   => $customer?->id,
                    ],
                ]);

                if (! $invoice) {
                    AccountsCustomerAdvance::where('order_id', $order->id)
                        ->whereIn('status', ['held', 'partial'])
                        ->each(function (AccountsCustomerAdvance $advance) {
                            $advance->update([
                                'amount_applied' => $advance->amount,
                                'status'         => 'refunded',
                            ]);
                        });
                }

                $order->payments()->create([
                    'type'           => OrderPaymentType::REFUND,
                    'payment_method' => PaymentMethod::STORE_CREDIT,
                    'amount'         => $paidAmount,
                    'status'         => 'refunded',
                    'paid_at'        => now(),
                ]);

                $order->recalculateTotals();
            }

            return $returnEntry;
        });
    }

    protected function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Chart of accounts is missing account code {$code}.");
    }
}
