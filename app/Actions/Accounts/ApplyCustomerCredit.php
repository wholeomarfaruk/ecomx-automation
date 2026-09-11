<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Customer;
use App\Models\JournalEntry;

/**
 * Case 3.8 (redemption branch) — applying a customer's existing credit
 * balance against a new sale. No cash moves; the liability is drawn down
 * and revenue is recognized, same as any other sale.
 */
class ApplyCustomerCredit
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Customer $customer,
        int $customerCreditAccountId,
        int $salesAccountId,
        float $amount,
        string $entryDate,
        ?string $description = null,
    ): JournalEntry {
        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? "Customer credit applied — {$customer->full_name}",
            'transaction_type' => TransactionType::CREDIT_NOTE->value,
        ], [
            [
                'account_id'     => $customerCreditAccountId,
                'debit'          => $amount,
                'subledger_type' => Customer::class,
                'subledger_id'   => $customer->id,
            ],
            ['account_id' => $salesAccountId, 'credit' => $amount],
        ]);
    }
}
