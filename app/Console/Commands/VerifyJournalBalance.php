<?php

namespace App\Console\Commands;

use App\Models\JournalEntry;
use Illuminate\Console\Command;

/**
 * Data-integrity backstop for Golden Rule #1 (debit always equals credit).
 * PostJournalEntry already refuses to write an unbalanced entry, so this
 * should never find anything in normal operation — it exists to catch
 * drift from a bug, a manual DB edit, or a migration mistake.
 */
class VerifyJournalBalance extends Command
{
    protected $signature = 'accounts:verify-balance';

    protected $description = 'Check every journal entry for debit/credit imbalance';

    public function handle(): int
    {
        $unbalanced = JournalEntry::query()
            ->withSum('lines as total_debit', 'debit')
            ->withSum('lines as total_credit', 'credit')
            ->get()
            ->filter(fn (JournalEntry $entry) => abs((float) $entry->total_debit - (float) $entry->total_credit) > 0.01);

        if ($unbalanced->isEmpty()) {
            $this->info('All journal entries are balanced.');
            return self::SUCCESS;
        }

        $this->error("{$unbalanced->count()} unbalanced journal entries found:");

        foreach ($unbalanced as $entry) {
            $this->line("  {$entry->entry_number}: debit {$entry->total_debit}, credit {$entry->total_credit}");
        }

        return self::FAILURE;
    }
}
