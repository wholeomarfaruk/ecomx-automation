<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsCustomerAdvance;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\Customer;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * Draws down an open customer advance (paid before the order was completed)
 * against the invoice created once the order completes. No cash moves — the
 * advance liability is debited and Accounts Receivable is credited, same
 * shape as ApplySupplierAdvance on the payable side. One
 * AccountsPaymentAllocation row is recorded against each side (the advance
 * and the invoice) so both open-item balances stay auditable back to this
 * single journal entry.
 */
class ApplyCustomerAdvance
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        AccountsCustomerAdvance $advance,
        AccountsCustomerInvoice $invoice,
        int $receivableAccountId,
        string $entryDate,
        ?float $amount = null,
        ?string $description = null,
    ): JournalEntry {
        return DB::transaction(function () use ($advance, $invoice, $receivableAccountId, $entryDate, $amount, $description) {
            $customer = $advance->customer;

            $amount = $amount !== null
                ? round($amount, 2)
                : round(min($advance->amountRemaining(), $invoice->amountDue()), 2);

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Nothing to apply — the advance or the invoice has no remaining balance.');
            }

            if ($amount > $advance->amountRemaining() + 0.01) {
                throw new \InvalidArgumentException("Allocation exceeds the advance's remaining balance.");
            }

            $advanceAccountId = $advance->journalEntry->lines()->where('credit', '>', 0)->value('account_id');

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Advance applied — Order #{$invoice->order_id}",
                'transaction_type' => TransactionType::CUSTOMER_ADVANCE->value,
            ], [
                [
                    'account_id'     => $advanceAccountId,
                    'debit'          => $amount,
                    'subledger_type' => Customer::class,
                    'subledger_id'   => $customer->id,
                ],
                [
                    'account_id'     => $receivableAccountId,
                    'credit'         => $amount,
                    'subledger_type' => Customer::class,
                    'subledger_id'   => $customer->id,
                ],
            ]);

            AccountsPaymentAllocation::create([
                'payment_journal_entry_id' => $entry->id,
                'allocatable_type'         => AccountsCustomerAdvance::class,
                'allocatable_id'           => $advance->id,
                'amount'                   => $amount,
            ]);

            $advance->increment('amount_applied', $amount);
            $advance->refresh();
            $advance->update(['status' => $advance->amountRemaining() <= 0.01 ? 'applied' : 'partial']);

            AccountsPaymentAllocation::create([
                'payment_journal_entry_id' => $entry->id,
                'allocatable_type'         => AccountsCustomerInvoice::class,
                'allocatable_id'           => $invoice->id,
                'amount'                   => $amount,
            ]);

            $invoice->increment('amount_allocated', $amount);
            $invoice->increment('advance_applied', $amount);
            $invoice->refresh();
            $invoice->update(['status' => $invoice->amountDue() <= 0.01 ? 'paid' : 'partial']);

            return $entry;
        });
    }
}
