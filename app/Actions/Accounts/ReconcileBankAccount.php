<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationLine;
use Illuminate\Support\Facades\DB;

/**
 * Case 2.2 — starts a reconciliation run by recording the bank statement's
 * balance against the book's current balance; the difference is shown as
 * unmatched. postAdjustment() then records whatever entry (e.g. an unrecorded
 * bank fee) explains the gap, after which the two balances agree.
 *
 * Note: the spec's own worked example for this case (book 48,000 vs
 * statement 50,000, "fixed" by Dr Bank Charge/Cr Bank 2,000) doesn't
 * actually balance — crediting the bank moves it further from the
 * statement figure, not closer. This implementation follows correct
 * double-entry (a debit expense against the bank account decreases the
 * bank balance) rather than the spec's literal numbers; see the test in
 * tests/Feature/Accounts/IntegrationsAndCloseoutTest.php for a corrected,
 * consistent version of the same scenario.
 */
class ReconcileBankAccount
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function start(Account $account, float $statementBalance, string $statementDate): BankReconciliation
    {
        $bookBalance = $account->balance();

        $reconciliation = BankReconciliation::create([
            'account_id'         => $account->id,
            'statement_date'     => $statementDate,
            'statement_balance'  => $statementBalance,
            'book_balance'       => $bookBalance,
            'status'             => 'in_progress',
        ]);

        $difference = round($statementBalance - $bookBalance, 2);

        if (abs($difference) > 0.01) {
            BankReconciliationLine::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'description'            => 'Unexplained difference (statement vs book)',
                'amount'                 => $difference,
                'matched'                => false,
            ]);
        } else {
            $reconciliation->update(['status' => 'completed']);
        }

        return $reconciliation;
    }

    /**
     * Posts the adjusting entry that explains a reconciliation's
     * difference (e.g. an unrecorded bank charge), matches the
     * corresponding unmatched line, and marks the run completed once the
     * book and statement balances agree.
     */
    public function postAdjustment(
        BankReconciliation $reconciliation,
        int $expenseAccountId,
        float $amount,
        string $entryDate,
        ?string $description = null,
    ): BankReconciliation {
        return DB::transaction(function () use ($reconciliation, $expenseAccountId, $amount, $entryDate, $description) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? 'Bank reconciliation adjustment',
                'transaction_type' => TransactionType::BANK_RECONCILIATION->value,
            ], [
                ['account_id' => $expenseAccountId, 'debit' => $amount],
                ['account_id' => $reconciliation->account_id, 'credit' => $amount],
            ]);

            $line = $reconciliation->lines()->where('matched', false)->first();
            if ($line) {
                $matchedLine = $entry->lines()->where('account_id', $reconciliation->account_id)->first();
                $line->update(['matched' => true, 'journal_entry_line_id' => $matchedLine?->id]);
            }

            $newBookBalance = $reconciliation->account->balance();
            $reconciliation->update([
                'book_balance' => $newBookBalance,
                'status'       => abs($reconciliation->statement_balance - $newBookBalance) <= 0.01 ? 'completed' : 'in_progress',
            ]);

            return $reconciliation->fresh('lines');
        });
    }
}
