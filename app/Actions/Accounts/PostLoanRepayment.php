<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use Illuminate\Support\Facades\DB;

/**
 * Case 5.2 — a single installment payment split into two distinct lines:
 * principal (reduces the Loan Payable liability) and interest (an
 * expense). Golden Rule #4: these are never merged into one figure.
 */
class PostLoanRepayment
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Loan $loan,
        int $cashAccountId,
        int $interestExpenseAccountId,
        float $principalAmount,
        float $interestAmount,
        string $paidAt,
        ?LoanRepaymentSchedule $schedule = null,
        ?string $description = null,
    ): LoanRepayment {
        return DB::transaction(function () use (
            $loan, $cashAccountId, $interestExpenseAccountId,
            $principalAmount, $interestAmount, $paidAt, $schedule, $description
        ) {
            $lines = [
                ['account_id' => $loan->loan_payable_account_id, 'debit' => $principalAmount],
            ];

            if ($interestAmount > 0) {
                $lines[] = ['account_id' => $interestExpenseAccountId, 'debit' => $interestAmount];
            }

            $lines[] = ['account_id' => $cashAccountId, 'credit' => $principalAmount + $interestAmount];

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $paidAt,
                'description'      => $description ?? "Loan repayment — {$loan->name}",
                'transaction_type' => TransactionType::LOAN_REPAYMENT->value,
                'source_type'      => Loan::class,
                'source_id'        => $loan->id,
                'purpose'          => $schedule ? "repayment_schedule_{$schedule->id}" : null,
            ], $lines);

            $repayment = LoanRepayment::create([
                'loan_id'          => $loan->id,
                'schedule_id'      => $schedule?->id,
                'principal_amount' => $principalAmount,
                'interest_amount'  => $interestAmount,
                'journal_entry_id' => $entry->id,
                'paid_at'          => $paidAt,
            ]);

            $schedule?->update(['status' => 'paid']);

            if ($loan->outstandingPrincipal() <= 0.01) {
                $loan->update(['status' => 'closed']);
            }

            return $repayment;
        });
    }
}
