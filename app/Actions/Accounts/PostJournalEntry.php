<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Exceptions\Accounts\DuplicateJournalEntryException;
use App\Exceptions\Accounts\LockedFiscalPeriodException;
use App\Exceptions\Accounts\UnbalancedJournalEntryException;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * The single write path for posting a balanced journal entry. Every
 * Accounts Action (PostManualTransaction, PostTransferWithFee, ...) must
 * build its Dr/Cr lines and hand them to this class rather than writing to
 * journal_entries/journal_entry_lines directly — this is what guarantees
 * debit always equals credit (Golden Rule #1), blocks posting into a locked
 * period (Golden Rule #7), and prevents the same business event from being
 * posted twice (Golden Rule #8).
 */
class PostJournalEntry
{
    /**
     * @param  array{entry_date: string, description?: ?string, transaction_type: string, source_type?: ?string, source_id?: ?int, purpose?: ?string, status?: string}  $header
     * @param  list<array{account_id: int, debit?: float|string, credit?: float|string, memo?: ?string, subledger_type?: ?string, subledger_id?: ?int}>  $lines
     */
    public function handle(array $header, array $lines): JournalEntry
    {
        $this->assertLinesBalance($lines);

        $sourceType = $header['source_type'] ?? null;
        $sourceId   = $header['source_id'] ?? null;
        $purpose    = $header['purpose'] ?? null;

        if ($sourceType && $sourceId && $purpose) {
            $this->assertNotDuplicate($sourceType, $sourceId, $purpose);
        }

        $status = JournalEntryStatus::from($header['status'] ?? JournalEntryStatus::POSTED->value);
        $fiscalPeriod = $this->resolveFiscalPeriod($header['entry_date']);

        if ($status === JournalEntryStatus::POSTED && $fiscalPeriod?->is_locked) {
            throw LockedFiscalPeriodException::forDate($header['entry_date']);
        }

        return DB::transaction(function () use ($header, $lines, $status, $fiscalPeriod, $sourceType, $sourceId, $purpose) {
            $entry = JournalEntry::create([
                'entry_number'      => $this->nextEntryNumber(),
                'entry_date'        => $header['entry_date'],
                'description'       => $header['description'] ?? null,
                'status'            => $status,
                'transaction_type'  => $header['transaction_type'],
                'source_type'       => $sourceType,
                'source_id'         => $sourceId,
                'purpose'           => $purpose,
                'fiscal_period_id'  => $fiscalPeriod?->id,
                'created_by'        => auth()->id(),
                'posted_by'         => $status === JournalEntryStatus::POSTED ? auth()->id() : null,
                'posted_at'         => $status === JournalEntryStatus::POSTED ? now() : null,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id'      => $line['account_id'],
                    'debit'           => $line['debit'] ?? 0,
                    'credit'          => $line['credit'] ?? 0,
                    'memo'            => $line['memo'] ?? null,
                    'subledger_type'  => $line['subledger_type'] ?? null,
                    'subledger_id'    => $line['subledger_id'] ?? null,
                ]);
            }

            activity('accounts')
                ->causedBy(auth()->user())
                ->performedOn($entry)
                ->event('posted')
                ->log("Journal entry {$entry->entry_number} ({$header['transaction_type']}) was posted");

            return $entry->load('lines');
        });
    }

    /**
     * @param  list<array{account_id: int, debit?: float|string, credit?: float|string}>  $lines
     */
    protected function assertLinesBalance(array $lines): void
    {
        if (count($lines) < 2) {
            throw UnbalancedJournalEntryException::noLines();
        }

        $totalDebit  = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $index => $line) {
            $debit  = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            $hasDebit  = $debit > 0;
            $hasCredit = $credit > 0;

            if ($hasDebit === $hasCredit) {
                throw UnbalancedJournalEntryException::invalidLine($index);
            }

            $totalDebit  += $debit;
            $totalCredit += $credit;
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw UnbalancedJournalEntryException::make($totalDebit, $totalCredit);
        }
    }

    protected function assertNotDuplicate(string $sourceType, int $sourceId, string $purpose): void
    {
        $exists = JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('purpose', $purpose)
            ->exists();

        if ($exists) {
            throw DuplicateJournalEntryException::forSource($sourceType, $sourceId, $purpose);
        }
    }

    protected function resolveFiscalPeriod(string $entryDate): ?FiscalPeriod
    {
        return FiscalPeriod::query()->containingDate($entryDate)->first();
    }

    protected function nextEntryNumber(): string
    {
        $next = (int) (JournalEntry::query()->max('id') ?? 0) + 1;

        return 'JE-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
