<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;

/**
 * Case 8.3 — the owner takes money out for personal use. This reduces
 * equity via the Owner Drawings contra-equity account, never a business
 * expense (Golden Rule #5) — it must never appear on the P&L.
 */
class PostOwnerWithdrawal
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        int $ownerDrawingsAccountId,
        int $cashAccountId,
        float $amount,
        string $entryDate,
        ?string $description = null,
    ): JournalEntry {
        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? 'Owner withdrawal',
            'transaction_type' => TransactionType::OWNER_WITHDRAWAL->value,
        ], [
            ['account_id' => $ownerDrawingsAccountId, 'debit' => $amount],
            ['account_id' => $cashAccountId, 'credit' => $amount],
        ]);
    }
}
