<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;
use App\Models\RecurringExpense;
use Illuminate\Support\Facades\DB;

/**
 * Case 6.2 — posts one occurrence of a recurring expense (rent, internet)
 * for its currently due date, then advances next_run_date so the same
 * occurrence is never posted twice. The purpose key includes the due date
 * so PostJournalEntry's duplicate guard also backstops this even if the
 * scheduler somehow runs twice before next_run_date advances.
 */
class PostRecurringExpenseRun
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(RecurringExpense $recurring, ?string $description = null): JournalEntry
    {
        $dueDate = $recurring->next_run_date->toDateString();

        $entry = $this->postJournalEntry->handle([
            'entry_date'       => $dueDate,
            'description'      => $description ?? "Recurring expense — {$recurring->name}",
            'transaction_type' => TransactionType::RECURRING_EXPENSE->value,
            'source_type'      => RecurringExpense::class,
            'source_id'        => $recurring->id,
            'purpose'          => "recurring_{$dueDate}",
        ], [
            ['account_id' => $recurring->account_id, 'debit' => (float) $recurring->amount],
            ['account_id' => $recurring->from_account_id, 'credit' => (float) $recurring->amount],
        ]);

        $recurring->advanceNextRunDate();

        return $entry;
    }

    /**
     * Runs every active recurring expense whose next_run_date has arrived.
     * Called by the scheduler (see app/Console/Kernel or routes/console.php).
     */
    public function runDue(): int
    {
        $count = 0;

        DB::transaction(function () use (&$count) {
            RecurringExpense::query()->due()->lockForUpdate()->each(function (RecurringExpense $recurring) use (&$count) {
                $this->handle($recurring);
                $count++;
            });
        });

        return $count;
    }
}
