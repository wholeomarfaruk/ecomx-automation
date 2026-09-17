<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Cases 3.7 (cash refund against a return) and 3.9 (refund settled as store
 * credit instead of cash) for a specific order. Debits Sales Return (income
 * contra-account) either way; credits cash for a cash refund, or the
 * Customer Credit liability for a store-credit refund — no cash moves in
 * that case, the customer simply now has a balance to apply to a future
 * order via ApplyCustomerCredit.
 *
 * If this order has a posted AccountsCustomerInvoice (Case: order was
 * confirmed and a real receivable/paid invoice exists), the refunded amount
 * also draws down its amount_allocated the same way PostCustomerPayment
 * draws it up — otherwise a refund would leave that invoice's open-item
 * ledger permanently out of step with what the customer actually still owes
 * or has been repaid, the same class of bug PostSupplierPayment fixed on
 * the payable side.
 */
class RefundOrder
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Order $order,
        float $amount,
        string $entryDate,
        bool $asStoreCredit,
        ?int $cashAccountId = null,
        ?string $description = null,
    ): JournalEntry {
        return DB::transaction(function () use ($order, $amount, $entryDate, $asStoreCredit, $cashAccountId, $description) {
            $customer = $order->customer;
            $salesReturnAccountId = $this->accountId('4900');
            $creditOrCashAccountId = $asStoreCredit ? $this->accountId('2150') : $cashAccountId;

            if (! $creditOrCashAccountId) {
                throw new \InvalidArgumentException('A cash/bank account is required for a cash refund.');
            }

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Refund for Order #{$order->id}" . ($asStoreCredit ? ' (store credit)' : ''),
                'transaction_type' => ($asStoreCredit ? TransactionType::CREDIT_NOTE : TransactionType::REFUND)->value,
            ], [
                [
                    'account_id'     => $salesReturnAccountId,
                    'debit'          => $amount,
                    'subledger_type' => $customer ? Customer::class : null,
                    'subledger_id'   => $customer?->id,
                ],
                [
                    'account_id'     => $creditOrCashAccountId,
                    'credit'         => $amount,
                    'subledger_type' => $asStoreCredit && $customer ? Customer::class : null,
                    'subledger_id'   => $asStoreCredit ? $customer?->id : null,
                ],
            ]);

            $invoice = AccountsCustomerInvoice::where('order_id', $order->id)->first();

            if ($invoice) {
                $applied = min($amount, (float) $invoice->amount_allocated);

                if ($applied > 0) {
                    AccountsPaymentAllocation::create([
                        'payment_journal_entry_id' => $entry->id,
                        'allocatable_type'         => AccountsCustomerInvoice::class,
                        'allocatable_id'           => $invoice->id,
                        'amount'                   => -$applied,
                    ]);

                    $invoice->decrement('amount_allocated', $applied);
                    $invoice->refresh();
                    $invoice->update(['status' => $invoice->amountDue() <= 0.01 ? 'paid' : ($invoice->amount_allocated > 0 ? 'partial' : 'open')]);
                }
            }

            return $entry;
        });
    }

    protected function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Chart of accounts is missing account code {$code}.");
    }
}
