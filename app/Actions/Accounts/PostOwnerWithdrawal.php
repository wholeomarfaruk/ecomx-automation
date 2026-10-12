<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;
use App\Models\MasterProfile;

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
        ?int $ownerProfileId = null,
    ): JournalEntry {
        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? 'Owner withdrawal',
            'transaction_type' => TransactionType::OWNER_WITHDRAWAL->value,
        ], [
            ['account_id' => $ownerDrawingsAccountId, 'debit' => $amount] + $this->owner($ownerProfileId),
            ['account_id' => $cashAccountId, 'credit' => $amount],
        ]);
    }

    /** Per-owner capital / drawings: tagged with the owner's Master Profile. */
    protected function owner(?int $profileId): array
    {
        return $profileId ? ['subledger_type' => MasterProfile::class, 'subledger_id' => $profileId] : [];
    }
}
