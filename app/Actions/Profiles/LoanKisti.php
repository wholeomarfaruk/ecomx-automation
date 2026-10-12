<?php

namespace App\Actions\Profiles;

use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Accounts\PostLoanReceived;
use App\Actions\Accounts\PostLoanRepayment;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\MasterProfile;
use App\Services\Profiles\InstallmentScheduler;
use App\Services\Profiles\LedgerAccounts;
use App\Support\Bdt;
use App\Enums\Accounts\TransactionType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loans & EMI from the Lenders page. A new loan posts through
 * PostLoanReceived (Dr cash / Cr the loan's own payable account) and gets
 * its kisti schedule. Paying a kisti posts through PostLoanRepayment
 * (principal and interest kept apart); paying more than the kisti is a
 * support payment — the extra goes to principal and, per the loan's
 * support_rule, either lowers the next kisti (next_kisti) or comes off
 * the end of the schedule (principal).
 */
class LoanKisti
{
    public function __construct(
        protected PostLoanReceived $postLoanReceived,
        protected PostLoanRepayment $postLoanRepayment,
        protected PostJournalEntry $postJournalEntry,
        protected InstallmentScheduler $scheduler,
        protected LedgerAccounts $accounts,
    ) {}

    public function create(MasterProfile $lender, array $data): Loan
    {
        return DB::transaction(function () use ($lender, $data) {
            $rows = $this->scheduler->build(
                $data['frequency'], (int) ($data['installments'] ?? 0), $data['start_date'], (float) $data['principal'],
                $data['rate_type'] ?? 'none', isset($data['rate']) ? (float) $data['rate'] : null,
                isset($data['total_payable']) ? (float) $data['total_payable'] : null, $data['custom_offsets'] ?? [],
            );

            $loan = $this->postLoanReceived->handle(
                $data['name'] ?: $lender->display_name,
                $lender->display_name,
                (float) $data['principal'],
                $data['start_date'],
                (int) $data['cash_account_id'],
                $this->accounts->require(LedgerAccounts::LOAN_PAYABLE),
                $data['rate_type'] === 'reducing' ? (float) ($data['rate'] ?? 0) : null,
                $data['frequency'] === 'monthly' ? count($rows) : null,
            );

            $loan->update([
                'master_profile_id' => $lender->id,
                'lender_type'       => $lender->roleSettings('lender')['lender_type'] ?? null,
                'total_payable'     => $rows ? round(array_sum(array_column($rows, 'amount')), 2) : (float) $data['principal'],
                'rate_type'         => $data['rate_type'] ?? 'none',
                'frequency'         => $data['frequency'],
                'installments'      => count($rows) ?: null,
                'custom_offsets'    => $data['frequency'] === 'custom' ? array_values(array_map('intval', $data['custom_offsets'] ?? [])) : null,
                'support_rule'      => $data['support_rule'] ?? 'next_kisti',
                'note'              => $data['note'] ?? null,
            ]);

            foreach ($rows as $r) {
                $loan->schedules()->create([
                    'installment_no' => $r['no'],
                    'due_date'       => $r['due_date']->toDateString(),
                    'principal_due'  => $r['principal'],
                    'interest_due'   => $r['interest'],
                    'status'         => 'pending',
                ]);
            }

            $lender->addRole('lender');
            $lender->logActivity('loan', "Loan received {$loan->name} · " . Bdt::money($loan->principal));

            return $loan;
        });
    }

    /**
     * Pay against the loan's next open kisti (or a specific one).
     * Returns the repayment and the support amount carried.
     *
     * @return array{repayment: LoanRepayment, support: float}
     */
    public function pay(Loan $loan, float $amount, int $cashAccountId, string $paidAt, ?int $scheduleId = null): array
    {
        return DB::transaction(function () use ($loan, $amount, $cashAccountId, $paidAt, $scheduleId) {
            $schedule = $scheduleId
                ? $loan->schedules()->whereKey($scheduleId)->where('status', '!=', 'paid')->first()
                : $loan->schedules()->where('status', '!=', 'paid')->orderBy('due_date')->first();

            $outstanding = $loan->outstandingPrincipal();

            if (! $schedule) {
                // No fixed schedule ("when possible" loans): all of it is principal.
                if ($amount > $outstanding + 0.01) {
                    throw new RuntimeException('Amount is more than the loan balance (' . Bdt::money($outstanding) . ').');
                }

                $repayment = $this->postLoanRepayment->handle(
                    $loan, $cashAccountId, $this->accounts->require(LedgerAccounts::INTEREST_EXPENSE),
                    $amount, 0, $paidAt, null, "Loan repayment — {$loan->name}",
                );

                return ['repayment' => $repayment, 'support' => 0.0];
            }

            $due = max(0, round($schedule->payable() - (float) $schedule->paid_amount, 2));
            // Interest is taken first, so whatever was already paid on this kisti covered it.
            $interestLeft = max(0, round((float) $schedule->interest_due - (float) $schedule->paid_amount, 2));
            $interest  = round(min($interestLeft, $amount), 2);
            $principal = round($amount - $interest, 2);
            $extra     = max(0, round($amount - $due, 2));
            $fullyPaid = $amount + 0.005 >= $due;

            if ($principal > $outstanding + 0.01) {
                throw new RuntimeException('Amount is more than the loan balance (' . Bdt::money($outstanding + $interest) . ').');
            }

            $memo = 'Kisti ' . ($schedule->installment_no ?? '') . ' — ' . $loan->name . ($extra > 0 ? ' + ' . Bdt::money($extra) . ' support' : '');

            // A partial payment isn't tied to the schedule row, so the row stays
            // open until the rest arrives (PostLoanRepayment marks it paid).
            $repayment = $principal > 0.004
                ? $this->postLoanRepayment->handle(
                    $loan, $cashAccountId, $this->accounts->require(LedgerAccounts::INTEREST_EXPENSE),
                    $principal, $interest, $paidAt, $fullyPaid ? $schedule : null, $memo,
                )
                : $this->interestOnly($loan, $cashAccountId, $interest, $paidAt, $memo);

            $schedule->update([
                'paid_amount'    => round((float) $schedule->paid_amount + $amount, 2),
                'support_amount' => $extra,
                'status'         => $fullyPaid ? 'paid' : $schedule->status,
            ]);

            if ($extra > 0) {
                $this->carrySupport($loan, $schedule, $extra);
            }

            $loan->profile?->logActivity('loan', 'Kisti ' . ($schedule->installment_no ?? '') . ' paid ' . Bdt::money($amount)
                . ($extra > 0 ? ' (' . Bdt::money($extra) . ' support)' : '') . " · {$loan->name}");

            return ['repayment' => $repayment, 'support' => $extra];
        });
    }

    /** PostLoanRepayment always writes a principal line; a payment that only covers interest can't. */
    protected function interestOnly(Loan $loan, int $cashAccountId, float $interest, string $paidAt, string $memo): LoanRepayment
    {
        $entry = $this->postJournalEntry->handle([
            'entry_date'       => $paidAt,
            'description'      => $memo,
            'transaction_type' => TransactionType::LOAN_REPAYMENT->value,
            'source_type'      => Loan::class,
            'source_id'        => $loan->id,
        ], [
            ['account_id' => $this->accounts->require(LedgerAccounts::INTEREST_EXPENSE), 'debit' => $interest],
            ['account_id' => $cashAccountId, 'credit' => $interest],
        ]);

        return LoanRepayment::create([
            'loan_id'          => $loan->id,
            'principal_amount' => 0,
            'interest_amount'  => $interest,
            'journal_entry_id' => $entry->id,
            'paid_at'          => $paidAt,
        ]);
    }

    protected function carrySupport(Loan $loan, LoanRepaymentSchedule $paid, float $extra): void
    {
        $later = $loan->schedules()->where('status', '!=', 'paid')->where('due_date', '>=', $paid->due_date)
            ->whereKeyNot($paid->id)->orderBy('due_date')->get();

        if ($loan->support_rule === 'principal') {
            $later = $later->reverse();
        }

        foreach ($later as $s) {
            if ($extra <= 0) {
                break;
            }

            $cut = min($extra, $s->payable());
            $s->update(['adjusted_amount' => round($s->payable() - $cut, 2)]);
            $extra = round($extra - $cut, 2);

            if ($s->payable() <= 0.005) {
                $s->update(['status' => 'paid']);
            }
        }

        if ($loan->outstandingPrincipal() <= 0.01) {
            $loan->update(['status' => 'closed']);
        }
    }
}
