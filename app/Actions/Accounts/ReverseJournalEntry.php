<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Exceptions\Accounts\JournalEntryImmutableException;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Case 1.6 (edit a posted entry) — never mutate a posted entry's lines.
 * Instead post an exact debit/credit-swapped copy dated today (the
 * currently open period), linked back via reversed_journal_entry_id, so
 * the full created -> reversed -> reposted history stays visible.
 */
class ReverseJournalEntry
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(JournalEntry $original, ?string $reason = null): JournalEntry
    {
        if ($original->status !== JournalEntryStatus::POSTED) {
            throw JournalEntryImmutableException::notPosted();
        }

        $original->loadMissing('lines');

        return DB::transaction(function () use ($original, $reason) {
            $lines = $original->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'debit'      => (float) $line->credit,
                'credit'     => (float) $line->debit,
                'memo'       => $line->memo,
            ])->all();

            $reversal = $this->postJournalEntry->handle([
                'entry_date'       => now()->toDateString(),
                'description'      => $reason ?? "Reversal of {$original->entry_number}",
                'transaction_type' => TransactionType::REVERSAL->value,
                'source_type'      => $original->source_type,
                'source_id'        => $original->source_id,
                'purpose'          => $original->purpose ? Str::limit("reversal_of_{$original->purpose}_{$original->id}", 190, '') : null,
            ], $lines);

            $reversal->update(['reversed_journal_entry_id' => $original->id]);

            return $reversal;
        });
    }
}
