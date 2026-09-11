<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;

/**
 * Cases 1.1 (manual income), 1.2 (manual expense), 6.1 (general expense) —
 * one Dr line and one Cr line, no fee, no allocation. The simplest possible
 * shape a journal entry can take.
 */
class PostManualTransaction
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        string $entryDate,
        TransactionType $transactionType,
        ?string $description = null,
    ): JournalEntry {
        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description,
            'transaction_type' => $transactionType->value,
        ], [
            ['account_id' => $debitAccountId, 'debit' => $amount],
            ['account_id' => $creditAccountId, 'credit' => $amount],
        ]);
    }
}
