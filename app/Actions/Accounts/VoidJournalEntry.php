<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Exceptions\Accounts\JournalEntryImmutableException;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * Case 1.7 — cancel a posted entry entirely. Posts an offsetting entry
 * (no replacement entry, unlike ReverseJournalEntry's edit workflow) and
 * flips both the original and the offsetting entry to status=void, so
 * Account::balance() excludes both — leaving the net effect at zero
 * instead of double-subtracting — while both remain permanently visible
 * in the Transactions history for audit (Golden Rule #2).
 */
class VoidJournalEntry
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(JournalEntry $original, ?string $reason = null): JournalEntry
    {
        if ($original->status === JournalEntryStatus::VOID) {
            throw JournalEntryImmutableException::alreadyVoided();
        }

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

            $voidingEntry = $this->postJournalEntry->handle([
                'entry_date'       => now()->toDateString(),
                'description'      => $reason ?? "Void of {$original->entry_number}",
                'transaction_type' => TransactionType::VOID->value,
                'status'           => JournalEntryStatus::VOID->value,
            ], $lines);

            $voidingEntry->update([
                'reversed_journal_entry_id' => $original->id,
                'voided_by'                 => auth()->id(),
                'voided_at'                 => now(),
            ]);

            $original->update([
                'status'    => JournalEntryStatus::VOID,
                'voided_by' => auth()->id(),
                'voided_at' => now(),
            ]);

            activity('accounts')
                ->causedBy(auth()->user())
                ->performedOn($original)
                ->event('voided')
                ->log("Journal entry {$original->entry_number} was voided");

            return $original->fresh('lines');
        });
    }
}
