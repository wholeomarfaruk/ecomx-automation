<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Customer;
use App\Models\JournalEntry;

/**
 * Cases 3.7 (refund against a sales return) and 3.9 (refund of unused
 * customer credit) — cash leaves a cash/bank/mobile-banking account and
 * either a Sales Return/Refund account or the Customer Credit liability is
 * debited, depending on what's being refunded. When the debit side is a
 * per-customer control account (e.g. Customer Credit), pass $customerId so
 * that line carries the subledger pointer used for per-customer balances.
 */
class PostRefund
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        int $debitAccountId,
        int $cashAccountId,
        float $amount,
        string $entryDate,
        TransactionType $transactionType = TransactionType::REFUND,
        ?string $description = null,
        ?int $customerId = null,
    ): JournalEntry {
        $debitLine = ['account_id' => $debitAccountId, 'debit' => $amount];

        if ($customerId) {
            $debitLine['subledger_type'] = Customer::class;
            $debitLine['subledger_id'] = $customerId;
        }

        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description,
            'transaction_type' => $transactionType->value,
        ], [
            $debitLine,
            ['account_id' => $cashAccountId, 'credit' => $amount],
        ]);
    }
}
