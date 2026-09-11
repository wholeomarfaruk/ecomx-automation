<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsCustomerInvoice;
use App\Models\Customer;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * Case 3.10 — a customer's outstanding balance will never be collected.
 * Moves the amount out of Accounts Receivable into Bad Debt Expense and
 * closes the underlying open invoice (if one is given) so it stops
 * appearing as collectible.
 */
class WriteOffBadDebt
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Customer $customer,
        int $badDebtExpenseAccountId,
        int $receivableAccountId,
        float $amount,
        string $entryDate,
        ?AccountsCustomerInvoice $invoice = null,
        ?string $description = null,
    ): JournalEntry {
        return DB::transaction(function () use ($customer, $badDebtExpenseAccountId, $receivableAccountId, $amount, $entryDate, $invoice, $description) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Bad debt written off — {$customer->full_name}",
                'transaction_type' => TransactionType::BAD_DEBT->value,
            ], [
                ['account_id' => $badDebtExpenseAccountId, 'debit' => $amount],
                [
                    'account_id'     => $receivableAccountId,
                    'credit'         => $amount,
                    'subledger_type' => Customer::class,
                    'subledger_id'   => $customer->id,
                ],
            ]);

            if ($invoice) {
                $invoice->increment('amount_allocated', $amount);
                $invoice->refresh();
                $invoice->update(['status' => 'written_off']);
            }

            return $entry;
        });
    }
}
