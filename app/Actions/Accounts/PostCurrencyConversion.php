<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;

/**
 * Case 1.5 — buying foreign currency (e.g. a Dollar Card) with local cash.
 * $amountPaid leaves the source account; $amountReceived (already converted
 * to base-currency value at the transaction-time rate) lands in the
 * destination account; any $fee is a separate expense line. FX
 * revaluation/gain-loss on rate movement after the fact is explicitly out
 * of scope here (see spec note + plan risk (d)) — this only records the
 * conversion itself.
 */
class PostCurrencyConversion
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        int $fromAccountId,
        int $toAccountId,
        float $amountPaid,
        float $amountReceivedValue,
        string $entryDate,
        ?int $feeAccountId = null,
        float $fee = 0,
        ?string $description = null,
    ): JournalEntry {
        $lines = [
            ['account_id' => $toAccountId, 'debit' => $amountReceivedValue],
        ];

        if ($fee > 0 && $feeAccountId) {
            $lines[] = ['account_id' => $feeAccountId, 'debit' => $fee];
        }

        $lines[] = ['account_id' => $fromAccountId, 'credit' => $amountPaid];

        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description,
            'transaction_type' => TransactionType::CURRENCY_CONVERSION->value,
        ], $lines);
    }
}
