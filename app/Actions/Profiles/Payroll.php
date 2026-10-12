<?php

namespace App\Actions\Profiles;

use App\Actions\Accounts\PostJournalEntry;
use App\Enums\Accounts\TransactionType;
use App\Models\EmployeeAdvance;
use App\Models\MasterProfile;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\Profiles\LedgerAccounts;
use App\Support\Bdt;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Employees: salary advances, the monthly payroll run, and paying it.
 *
 *   Advance:  Dr Salary Advance (employee) / Cr cash
 *   Prepare:  Dr Salary expense (gross + bonus)
 *             / Cr Salary Payable (net, per employee)
 *             / Cr Salary Advance (deduction, per employee)
 *   Pay:      Dr Salary Payable (employee) / Cr cash
 *
 * Each advance is recovered by its monthly deduction until its balance is 0.
 */
class Payroll
{
    public function __construct(
        protected LedgerAccounts $accounts,
        protected PostJournalEntry $postJournalEntry,
    ) {}

    public function advance(MasterProfile $employee, float $amount, float $monthlyDeduction, int $cashAccountId, string $date): EmployeeAdvance
    {
        return DB::transaction(function () use ($employee, $amount, $monthlyDeduction, $cashAccountId, $date) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $date,
                'description'      => "Salary advance — {$employee->display_name}",
                'transaction_type' => TransactionType::SALARY_ADVANCE->value,
            ], [
                ['account_id' => $this->accounts->require(LedgerAccounts::SALARY_ADVANCE), 'debit' => $amount, 'subledger_type' => MasterProfile::class, 'subledger_id' => $employee->id],
                ['account_id' => $cashAccountId, 'credit' => $amount],
            ]);

            $employee->logActivity('advance', 'Salary advance ' . Bdt::money($amount) . ' · ' . Bdt::money($monthlyDeduction) . ' / month', ['entry_id' => $entry->id]);

            return EmployeeAdvance::create([
                'master_profile_id' => $employee->id,
                'amount'            => $amount,
                'monthly_deduction' => min($monthlyDeduction ?: $amount, $amount),
                'balance'           => $amount,
                'given_on'          => $date,
                'journal_entry_id'  => $entry->id,
            ]);
        });
    }

    /**
     * Gross / deduction / net for every active employee — the figures the
     * payroll banner previews and prepare() posts.
     *
     * @return Collection<int, array{profile: MasterProfile, salary: float, bonus: float, deduction: float, net: float}>
     */
    public function preview(float $bonusPercent = 0): Collection
    {
        $employees = MasterProfile::withRole('employee')->where('status', 'active')
            ->with(['roles', 'advances' => fn ($q) => $q->where('balance', '>', 0)])
            ->get();

        return $employees->map(function (MasterProfile $e) use ($bonusPercent) {
            $salary = round((float) ($e->roleSettings('employee')['salary'] ?? 0), 2);
            $bonus = round($salary * $bonusPercent / 100, 2);
            $deduction = round(min(
                $salary + $bonus,
                $e->advances->sum(fn ($a) => min((float) $a->monthly_deduction, (float) $a->balance)),
            ), 2);

            return ['profile' => $e, 'salary' => $salary, 'bonus' => $bonus, 'deduction' => $deduction, 'net' => round($salary + $bonus - $deduction, 2)];
        })->filter(fn ($r) => $r['salary'] > 0)->values();
    }

    public function prepare(CarbonInterface $month, float $bonusPercent = 0, ?string $bonusNote = null, ?string $dueOn = null): PayrollRun
    {
        $month = $month->copy()->startOfMonth();

        if (PayrollRun::whereDate('month', $month->toDateString())->exists()) {
            throw new RuntimeException($month->format('F Y') . ' payroll is already prepared.');
        }

        $rows = $this->preview($bonusPercent);

        if ($rows->isEmpty()) {
            throw new RuntimeException('No active employee has a monthly salary set.');
        }

        return DB::transaction(function () use ($month, $rows, $bonusNote, $dueOn) {
            $gross = round($rows->sum('salary'), 2);
            $bonus = round($rows->sum('bonus'), 2);
            $deducted = round($rows->sum('deduction'), 2);

            $run = PayrollRun::create([
                'month'            => $month->toDateString(),
                'gross'            => $gross,
                'bonus'            => $bonus,
                'bonus_note'       => $bonus > 0 ? $bonusNote : null,
                'advance_deducted' => $deducted,
                'net'              => round($gross + $bonus - $deducted, 2),
                'status'           => $gross + $bonus - $deducted > 0.005 ? 'prepared' : 'paid',
                'due_on'           => $dueOn ?: $month->copy()->addMonth()->day(5)->toDateString(),
                'created_by'       => auth()->id(),
            ]);

            $lines = [['account_id' => $this->accounts->require(LedgerAccounts::SALARY_EXPENSE), 'debit' => round($gross + $bonus, 2)]];

            foreach ($rows as $r) {
                $sub = ['subledger_type' => MasterProfile::class, 'subledger_id' => $r['profile']->id];

                if ($r['net'] > 0) {
                    $lines[] = ['account_id' => $this->accounts->require(LedgerAccounts::SALARY_PAYABLE), 'credit' => $r['net'], 'memo' => $r['profile']->display_name] + $sub;
                }
                if ($r['deduction'] > 0) {
                    $lines[] = ['account_id' => $this->accounts->require(LedgerAccounts::SALARY_ADVANCE), 'credit' => $r['deduction'], 'memo' => 'Advance deduction'] + $sub;
                    $this->recoverAdvances($r['profile'], $r['deduction']);
                }

                $run->items()->create([
                    'master_profile_id' => $r['profile']->id,
                    'salary'            => $r['salary'],
                    'bonus'             => $r['bonus'],
                    'advance_deduction' => $r['deduction'],
                    'net'               => $r['net'],
                    'status'            => $r['net'] > 0 ? 'unpaid' : 'paid',
                ]);
            }

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $month->copy()->endOfMonth()->toDateString(),
                'description'      => $month->format('F Y') . ' payroll',
                'transaction_type' => TransactionType::SALARY->value,
                'source_type'      => PayrollRun::class,
                'source_id'        => $run->id,
                'purpose'          => 'payroll',
            ], $lines);

            $run->update(['journal_entry_id' => $entry->id]);

            return $run;
        });
    }

    /**
     * Pay an employee: clears their oldest unpaid payroll items first.
     * With nothing outstanding the payment is booked straight to salary
     * expense.
     */
    public function pay(MasterProfile $employee, float $amount, int $cashAccountId, string $date): void
    {
        DB::transaction(function () use ($employee, $amount, $cashAccountId, $date) {
            $items = PayrollItem::where('master_profile_id', $employee->id)->where('status', '!=', 'paid')
                ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_items.payroll_run_id')
                ->orderBy('payroll_runs.month')->select('payroll_items.*')->get();

            $owed = round($items->sum(fn (PayrollItem $i) => $i->unpaid()), 2);
            $toPayable = min($amount, $owed);
            $toExpense = round($amount - $toPayable, 2);

            $lines = [];
            if ($toPayable > 0) {
                $lines[] = ['account_id' => $this->accounts->require(LedgerAccounts::SALARY_PAYABLE), 'debit' => $toPayable, 'subledger_type' => MasterProfile::class, 'subledger_id' => $employee->id];
            }
            if ($toExpense > 0) {
                $lines[] = ['account_id' => $this->accounts->require(LedgerAccounts::SALARY_EXPENSE), 'debit' => $toExpense, 'memo' => $employee->display_name];
            }
            $lines[] = ['account_id' => $cashAccountId, 'credit' => $amount];

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $date,
                'description'      => "Salary paid — {$employee->display_name}",
                'transaction_type' => TransactionType::SALARY_PAYMENT->value,
            ], $lines);

            $remaining = $toPayable;
            foreach ($items as $item) {
                if ($remaining <= 0) {
                    break;
                }
                $apply = min($remaining, $item->unpaid());
                $item->update([
                    'paid_amount' => round((float) $item->paid_amount + $apply, 2),
                    'status'      => $item->unpaid() - $apply <= 0.005 ? 'paid' : 'partial',
                ]);
                $remaining = round($remaining - $apply, 2);

                $run = $item->run;
                $paid = round((float) $run->items()->sum('paid_amount'), 2);
                $run->update(['paid' => $paid, 'status' => $paid + 0.005 >= (float) $run->net ? 'paid' : 'partial']);
            }

            $employee->logActivity('salary', 'Salary paid ' . Bdt::money($amount), ['entry_id' => $entry->id]);
        });
    }

    protected function recoverAdvances(MasterProfile $employee, float $deduction): void
    {
        foreach ($employee->advances()->where('balance', '>', 0)->orderBy('given_on')->get() as $advance) {
            if ($deduction <= 0) {
                break;
            }

            $cut = min($deduction, (float) $advance->monthly_deduction, (float) $advance->balance);
            $advance->update(['balance' => round((float) $advance->balance - $cut, 2)]);
            $deduction = round($deduction - $cut, 2);
        }
    }
}
