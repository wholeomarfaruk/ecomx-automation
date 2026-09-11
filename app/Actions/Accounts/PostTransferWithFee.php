<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;

/**
 * Cases 1.3 (plain transfer) and 1.4 (transfer with fee) — money moving
 * between the business's own cash/bank/mobile-banking accounts. A plain
 * transfer is Dr destination / Cr source; a fee adds a second Dr line
 * against the fee expense account so the source account is drawn down by
 * amount+fee while only `amount` lands in the destination.
 */
class PostTransferWithFee
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        int $fromAccountId,
        int $toAccountId,
        float $amount,
        string $entryDate,
        ?int $feeAccountId = null,
        float $fee = 0,
        ?string $description = null,
    ): JournalEntry {
        $lines = [
            ['account_id' => $toAccountId, 'debit' => $amount],
        ];

        if ($fee > 0 && $feeAccountId) {
            $lines[] = ['account_id' => $feeAccountId, 'debit' => $fee];
        }

        $lines[] = ['account_id' => $fromAccountId, 'credit' => $amount + $fee];

        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description,
            'transaction_type' => TransactionType::TRANSFER->value,
        ], $lines);
    }
}
