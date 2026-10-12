<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;
use App\Models\MasterProfile;

/**
 * Cases 8.1 (cash investment) and 8.2 (asset contribution) — money or
 * property the owner puts into the business. This increases equity, never
 * income (Golden Rule #5) — it must never appear on the P&L. The caller
 * passes whichever asset account received the contribution (cash/bank for
 * 8.1, a Fixed Assets account for 8.2).
 */
class PostOwnerInvestment
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        int $assetAccountId,
        int $ownerCapitalAccountId,
        float $amount,
        string $entryDate,
        ?string $description = null,
        ?int $ownerProfileId = null,
    ): JournalEntry {
        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? 'Owner investment',
            'transaction_type' => TransactionType::OWNER_INVESTMENT->value,
        ], [
            ['account_id' => $assetAccountId, 'debit' => $amount],
            ['account_id' => $ownerCapitalAccountId, 'credit' => $amount] + $this->owner($ownerProfileId),
        ]);
    }

    /** Per-owner capital / drawings: tagged with the owner's Master Profile. */
    protected function owner(?int $profileId): array
    {
        return $profileId ? ['subledger_type' => MasterProfile::class, 'subledger_id' => $profileId] : [];
    }
}
