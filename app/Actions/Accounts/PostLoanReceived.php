<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

/**
 * Case 5.1 — taking out a loan increases both an asset (cash/bank) and a
 * liability (Loan Payable); it is never income. Creates the Loan record
 * and its own non-system child account under the system "Loan Payable"
 * (2200) so each loan's balance is independently trackable, same pattern
 * as a new bank account under "Bank" (1020).
 */
class PostLoanReceived
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        string $name,
        ?string $lender,
        float $principal,
        string $startDate,
        int $cashAccountId,
        int $loanPayableParentAccountId,
        ?float $interestRate = null,
        ?int $termMonths = null,
        ?string $description = null,
    ): Loan {
        return DB::transaction(function () use (
            $name, $lender, $principal, $startDate, $cashAccountId,
            $loanPayableParentAccountId, $interestRate, $termMonths, $description
        ) {
            $parent = \App\Models\Account::findOrFail($loanPayableParentAccountId);

            $loanAccount = \App\Models\Account::create([
                'code'           => (string) ((int) \App\Models\Account::max('code') + 1),
                'name'           => "Loan Payable — {$name}",
                'type'           => $parent->type,
                'subtype'        => $parent->subtype,
                'normal_balance' => $parent->normal_balance,
                'parent_id'      => $parent->id,
                'is_control_account' => true,
                'is_system'      => false,
                'is_active'      => true,
            ]);

            $loan = Loan::create([
                'name'                    => $name,
                'lender'                  => $lender,
                'principal'               => $principal,
                'interest_rate'           => $interestRate,
                'start_date'              => $startDate,
                'term_months'             => $termMonths,
                'loan_payable_account_id' => $loanAccount->id,
                'status'                  => 'active',
            ]);

            $this->postJournalEntry->handle([
                'entry_date'       => $startDate,
                'description'      => $description ?? "Loan received — {$name}",
                'transaction_type' => TransactionType::LOAN_RECEIVED->value,
                'source_type'      => Loan::class,
                'source_id'        => $loan->id,
                'purpose'          => 'loan_received',
            ], [
                ['account_id' => $cashAccountId, 'debit' => $principal],
                ['account_id' => $loanAccount->id, 'credit' => $principal],
            ]);

            return $loan;
        });
    }
}
