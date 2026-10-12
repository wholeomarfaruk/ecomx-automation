<?php

namespace App\Actions\Profiles;

use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Accounts\ReverseJournalEntry;
use App\Enums\Accounts\TransactionType;
use App\Models\EmployeeAdvance;
use App\Models\JournalEntry;
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
 * Employees: salary advances, the monthly salary sheet, and paying it.
 *
 *   Advance:  Dr Salary Advance (employee) / Cr cash
 *   Sheet:    a Draft run, one editable line per employee — nothing in the books
 *   Post:     Dr Salary expense (gross − absent deduction)
 *             / Cr Salary Payable (net, per employee)
 *             / Cr Salary Advance (recovery, per employee)
 *             / Cr TDS payable (tax) / Cr Other income (fines & other deductions)
 *   Revert:   reverses that journal and gives the advance recoveries back,
 *             while nothing has been paid yet
 *   Pay:      Dr Salary Payable (employee) / Cr cash
 */
class Payroll
{
    public const TDS_PAYABLE = '2410';
    public const OTHER_INCOME = '4100';

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
     * Gross / deduction / net for every active employee — used for the
     * "next payroll" projections before a sheet exists.
     *
     * @return Collection<int, array{profile: MasterProfile, salary: float, bonus: float, deduction: float, net: float}>
     */
    public function preview(float $bonusPercent = 0): Collection
    {
        return $this->activeEmployees()->map(function (MasterProfile $e) use ($bonusPercent) {
            $salary = round((float) ($e->roleSettings('employee')['salary'] ?? 0), 2);
            $bonus = round($salary * $bonusPercent / 100, 2);
            $deduction = round(min($salary + $bonus, $this->suggestedRecovery($e)), 2);

            return ['profile' => $e, 'salary' => $salary, 'bonus' => $bonus, 'deduction' => $deduction, 'net' => round($salary + $bonus - $deduction, 2)];
        })->filter(fn ($r) => $r['salary'] > 0)->values();
    }

    protected function activeEmployees(): Collection
    {
        return MasterProfile::withRole('employee')->where('status', 'active')
            ->with(['roles', 'advances' => fn ($q) => $q->where('balance', '>', 0)])
            ->orderBy('display_name')->get();
    }

    /** This month's advance recovery: each open advance's monthly cut, never more than its balance. */
    protected function suggestedRecovery(MasterProfile $e): float
    {
        return round((float) $e->advances->sum(fn ($a) => min((float) $a->monthly_deduction, (float) $a->balance)), 2);
    }

    /** Overtime per hour, Labour Act style: salary ÷ 208 hours × 2. */
    public static function defaultOtRate(float $salary): float
    {
        return round($salary / 208 * 2, 2);
    }

    /* ------------------------------------------------------------ sheet */

    /**
     * Start the month's salary sheet as a Draft — one line per active
     * employee with their salary, last month's allowances, the suggested
     * advance recovery and the overtime rate. Nothing is posted yet.
     */
    public function createDraft(CarbonInterface $month, ?string $dueOn = null, float $bonusPercent = 0, ?string $bonusNote = null): PayrollRun
    {
        $month = $month->copy()->startOfMonth();
        if (PayrollRun::whereDate('month', $month->toDateString())->exists()) {
            throw new RuntimeException($month->format('F Y') . ' payroll already exists.');
        }

        $employees = $this->activeEmployees()->filter(fn ($e) => (float) ($e->roleSettings('employee')['salary'] ?? 0) > 0);
        if ($employees->isEmpty()) {
            throw new RuntimeException('No active employee has a monthly salary set.');
        }

        return DB::transaction(function () use ($month, $dueOn, $employees, $bonusPercent, $bonusNote) {
            $days = $month->daysInMonth;
            $run = PayrollRun::create([
                'month' => $month->toDateString(), 'status' => 'draft', 'working_days' => $days,
                'bonus_note' => $bonusNote, 'due_on' => $dueOn ?: $month->copy()->addMonth()->day(5)->toDateString(), 'created_by' => auth()->id(),
            ]);

            $last = PayrollItem::whereHas('run', fn ($q) => $q->where('month', '<', $month->toDateString()))
                ->whereIn('master_profile_id', $employees->pluck('id'))->orderByDesc('payroll_run_id')->get()->unique('master_profile_id')->keyBy('master_profile_id');

            foreach ($employees as $e) {
                $this->addLine($run, $e, $last->get($e->id), $bonusPercent);
            }

            return $this->refreshTotals($run);
        });
    }

    /** One sheet line for an employee (also used for "Add employee" on a draft). */
    public function addLine(PayrollRun $run, MasterProfile $e, ?PayrollItem $last = null, float $bonusPercent = 0): PayrollItem
    {
        $salary = round((float) ($e->roleSettings('employee')['salary'] ?? 0), 2);
        $row = [
            'salary' => $salary,
            // Allowances are usually the same every month — carry last month's.
            'house_rent' => (float) ($last?->house_rent ?? 0), 'medical' => (float) ($last?->medical ?? 0),
            'conveyance' => (float) ($last?->conveyance ?? 0), 'other_allowance' => (float) ($last?->other_allowance ?? 0),
            'ot_hours' => 0, 'ot_rate' => self::defaultOtRate($salary),
            'bonus' => round($salary * $bonusPercent / 100, 2), 'incentive' => 0, 'arrears' => 0,
            'working_days' => $run->working_days ?: $run->month->daysInMonth, 'absent_days' => 0,
            'advance_deduction' => $this->suggestedRecovery($e->loadMissing(['advances' => fn ($q) => $q->where('balance', '>', 0)])), 'tax' => 0, 'other_deduction' => 0,
        ];
        $row['advance_deduction'] = min($row['advance_deduction'], max(0, PayrollItem::compute(array_merge($row, ['advance_deduction' => 0]))['net']));

        return $run->items()->create($row + PayrollItem::compute($row) + ['master_profile_id' => $e->id, 'include' => true, 'status' => 'unpaid']);
    }

    /**
     * Save the edited sheet (draft only). $rows: item id → inputs + include + note.
     *
     * @return array<string, string> errors keyed "sheet.{id}.field"
     */
    public function saveSheet(PayrollRun $run, array $rows, array $header = []): array
    {
        if (! $run->isDraft()) {
            throw new RuntimeException('This payroll is already posted — revert it to draft to edit the sheet.');
        }

        $items = $run->items()->with(['profile.advances' => fn ($q) => $q->where('balance', '>', 0)])->get()->keyBy('id');
        $errors = [];

        DB::transaction(function () use ($run, $rows, $header, $items, &$errors) {
            foreach ($rows as $id => $in) {
                $item = $items->get((int) $id);
                if (! $item) {
                    continue;
                }
                $data = collect(PayrollItem::INPUTS)->mapWithKeys(fn ($k) => [$k => max(0, round((float) ($in[$k] ?? 0), 2))])->all();
                $data['working_days'] = min(31, max(1, $data['working_days'] ?: 30));
                $data['absent_days'] = min($data['working_days'], $data['absent_days']);

                $maxAdvance = round((float) ($item->profile?->advances->sum('balance') ?? 0), 2);
                if ($data['advance_deduction'] > $maxAdvance + 0.005) {
                    $errors["sheet.{$id}.advance_deduction"] = 'Advance balance is only ' . Bdt::money($maxAdvance) . '.';
                }
                $calc = PayrollItem::compute($data);
                if ($calc['net'] < -0.005) {
                    $errors["sheet.{$id}.net"] = 'Deductions are more than the earnings.';
                }

                $item->update($data + $calc + ['include' => (bool) ($in['include'] ?? true), 'note' => trim((string) ($in['note'] ?? '')) ?: null]);
            }

            $run->update(array_filter([
                'bonus_note' => array_key_exists('bonus_note', $header) ? ($header['bonus_note'] ?: null) : false,
                'note' => array_key_exists('note', $header) ? ($header['note'] ?: null) : false,
                'due_on' => $header['due_on'] ?? false,
            ], fn ($v) => $v !== false));

            $this->refreshTotals($run);
        });

        return $errors;
    }

    /** Run totals from its included lines. Run gross keeps the old meaning: earnings without bonus. */
    public function refreshTotals(PayrollRun $run): PayrollRun
    {
        $items = $run->items()->where('include', true)->get();
        $net = round((float) $items->sum('net'), 2);
        $paid = round((float) $run->items()->sum('paid_amount'), 2);

        $run->update([
            'gross' => round((float) $items->sum(fn ($i) => (float) $i->gross - (float) $i->bonus), 2),
            'bonus' => round((float) $items->sum('bonus'), 2),
            'advance_deducted' => round((float) $items->sum('advance_deduction'), 2),
            'other_deducted' => round((float) $items->sum(fn ($i) => (float) $i->absent_deduction + (float) $i->tax + (float) $i->other_deduction), 2),
            'net' => $net, 'paid' => $paid,
            'status' => $run->isDraft() ? 'draft' : ($paid + 0.005 >= $net ? 'paid' : ($paid > 0 ? 'partial' : 'prepared')),
        ]);

        return $run->fresh();
    }

    /** Approve the sheet: post one journal for the month and recover the advances. */
    public function post(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run) {
            $run = PayrollRun::lockForUpdate()->findOrFail($run->id);
            if (! $run->isDraft()) {
                throw new RuntimeException('This payroll is already posted.');
            }

            $items = $run->items()->with(['profile.advances' => fn ($q) => $q->where('balance', '>', 0)->orderBy('given_on')])->where('include', true)->get();
            if ($items->isEmpty()) {
                throw new RuntimeException('No employee is included in this payroll.');
            }
            foreach ($items as $i) {
                if ((float) $i->net < -0.005) {
                    throw new RuntimeException(($i->profile?->display_name ?? 'An employee') . ': deductions are more than the earnings.');
                }
                if ((float) $i->advance_deduction > (float) $i->profile?->advances->sum('balance') + 0.005) {
                    throw new RuntimeException(($i->profile?->display_name ?? 'An employee') . ': advance recovery is more than the advance balance.');
                }
            }

            $expense = round((float) $items->sum(fn ($i) => (float) $i->gross - (float) $i->absent_deduction), 2);
            $lines = [['account_id' => $this->accounts->require(LedgerAccounts::SALARY_EXPENSE), 'debit' => $expense, 'memo' => $run->month->format('F Y') . ' salary']];
            $tax = 0.0;
            $other = 0.0;

            foreach ($items as $i) {
                $sub = ['subledger_type' => MasterProfile::class, 'subledger_id' => $i->master_profile_id];
                $name = $i->profile?->display_name;
                if ((float) $i->net > 0) {
                    $lines[] = ['account_id' => $this->accounts->require(LedgerAccounts::SALARY_PAYABLE), 'credit' => (float) $i->net, 'memo' => $name] + $sub;
                }
                if ((float) $i->advance_deduction > 0) {
                    $lines[] = ['account_id' => $this->accounts->require(LedgerAccounts::SALARY_ADVANCE), 'credit' => (float) $i->advance_deduction, 'memo' => 'Advance recovery'] + $sub;
                    $i->update(['advance_cuts' => $this->recoverAdvances($i->profile, (float) $i->advance_deduction)]);
                }
                $tax += (float) $i->tax;
                $other += (float) $i->other_deduction;
                $i->update(['status' => (float) $i->net > 0.005 ? 'unpaid' : 'paid']);
            }
            if ($tax > 0) {
                $lines[] = ['account_id' => $this->ledger(self::TDS_PAYABLE, 'Tax Deducted (TDS) Payable', 'liability', 'tax', 'credit'), 'credit' => round($tax, 2), 'memo' => 'Salary tax deducted'];
            }
            if ($other > 0) {
                $lines[] = ['account_id' => $this->accounts->require(self::OTHER_INCOME), 'credit' => round($other, 2), 'memo' => 'Salary fines & other deductions'];
            }

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $run->month->copy()->endOfMonth()->toDateString(),
                'description'      => $run->month->format('F Y') . ' payroll',
                'transaction_type' => TransactionType::SALARY->value,
                'source_type'      => PayrollRun::class,
                'source_id'        => $run->id,
                'purpose'          => 'payroll_' . now()->format('YmdHisv'),   // a reverted run can be posted again
            ], $lines);

            $run->update(['status' => 'prepared', 'journal_entry_id' => $entry->id, 'posted_by' => auth()->id(), 'posted_at' => now()]);

            return $this->refreshTotals($run);
        });
    }

    /** Back to an editable sheet: reverse the journal, give the advance recoveries back. Only while nothing is paid. */
    public function revertToDraft(PayrollRun $run, ?string $reason = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $reason) {
            $run = PayrollRun::lockForUpdate()->findOrFail($run->id);
            if ($run->isDraft()) {
                return $run;
            }
            if ((float) $run->items()->sum('paid_amount') > 0.004) {
                throw new RuntimeException('Some salary is already paid for this month — it can\'t go back to draft. Use Adjust on the employee instead.');
            }
            $itemIds = $run->items()->pluck('id');
            $waiting = JournalEntry::where('source_type', PayrollItem::class)->whereIn('source_id', $itemIds)->whereIn('status', ['draft', 'scheduled'])->first();
            if ($waiting) {
                throw new RuntimeException("Payment {$waiting->ref()} is waiting in Posting Pending for this payroll — delete or reject it first.");
            }

            // The month journal and any adjustments made after posting.
            $entries = JournalEntry::where('source_type', PayrollRun::class)->where('source_id', $run->id)->where('status', 'posted')
                ->whereNull('reversed_journal_entry_id')        // not the reversals themselves
                ->whereDoesntHave('reversingEntry')->get();
            foreach ($entries as $e) {
                app(ReverseJournalEntry::class)->handle($e, 'Payroll reverted to draft' . ($reason ? " — {$reason}" : ''));
            }

            foreach ($run->items as $i) {
                foreach ((array) $i->advance_cuts as $cut) {
                    EmployeeAdvance::whereKey($cut['id'] ?? 0)->increment('balance', (float) ($cut['amount'] ?? 0));
                }
                // Payrolls posted before cuts were recorded: put the recovery back
                // on the employee's advances, newest first, up to each one's amount.
                if (! $i->advance_cuts && (float) $i->advance_deduction > 0) {
                    $left = (float) $i->advance_deduction;
                    foreach (EmployeeAdvance::where('master_profile_id', $i->master_profile_id)->orderByDesc('given_on')->orderByDesc('id')->get() as $adv) {
                        $room = round((float) $adv->amount - (float) $adv->balance, 2);
                        $back = round(min($left, $room), 2);
                        if ($back > 0) {
                            $adv->increment('balance', $back);
                            $left = round($left - $back, 2);
                        }
                        if ($left <= 0.004) {
                            break;
                        }
                    }
                }
                $i->update(['advance_cuts' => null, 'status' => 'unpaid']);
            }

            $run->update(['status' => 'draft', 'journal_entry_id' => null, 'posted_by' => null, 'posted_at' => null]);

            return $this->refreshTotals($run);
        });
    }

    /**
     * Pay an employee: clears their oldest unpaid posted payroll lines first.
     * With nothing outstanding the payment is booked straight to salary
     * expense.
     */
    public function pay(MasterProfile $employee, float $amount, int $cashAccountId, string $date): void
    {
        DB::transaction(function () use ($employee, $amount, $cashAccountId, $date) {
            // Both tables have status / ids — qualify every column of the join. Draft sheets aren't payable.
            $items = PayrollItem::where('payroll_items.master_profile_id', $employee->id)->where('payroll_items.status', '!=', 'paid')
                ->where('payroll_items.include', true)
                ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_items.payroll_run_id')
                ->where('payroll_runs.status', '!=', 'draft')
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

                $this->refreshTotals($item->run);
            }

            $employee->logActivity('salary', 'Salary paid ' . Bdt::money($amount), ['entry_id' => $entry->id]);
        });
    }

    /**
     * After posting: change one employee's salary / bonus (e.g. part already
     * paid, so the run can't go back to draft). The difference in net is
     * posted as an adjustment (Dr Salary expense / Cr Salary payable, or the
     * reverse); the net can't go below what's already been paid.
     */
    public function updateItem(PayrollItem $item, float $salary, float $bonus, ?string $note = null): ?JournalEntry
    {
        return DB::transaction(function () use ($item, $salary, $bonus, $note) {
            $item = PayrollItem::with(['run', 'profile'])->lockForUpdate()->findOrFail($item->id);
            if ($item->run->isDraft()) {
                throw new RuntimeException('This payroll is still a draft — edit it on the sheet.');
            }

            $data = collect(PayrollItem::INPUTS)->mapWithKeys(fn ($k) => [$k => (float) $item->{$k}])->all();
            $data['salary'] = round(max(0, $salary), 2);
            $data['bonus'] = round(max(0, $bonus), 2);
            $calc = PayrollItem::compute($data);
            $net = $calc['net'];

            if ($net + 0.005 < (float) $item->paid_amount) {
                throw new RuntimeException('Net ' . Bdt::money($net) . ' would be less than the ' . Bdt::money($item->paid_amount) . ' already paid.');
            }
            if ($net < -0.005) {
                throw new RuntimeException('Deductions would be more than the earnings.');
            }

            $delta = round($net - (float) $item->net, 2);
            $entry = null;
            if (abs($delta) >= 0.01) {
                $emp = $item->profile;
                $sub = ['subledger_type' => MasterProfile::class, 'subledger_id' => $item->master_profile_id];
                $expense = $this->accounts->require(LedgerAccounts::SALARY_EXPENSE);
                $payable = $this->accounts->require(LedgerAccounts::SALARY_PAYABLE);
                $entry = $this->postJournalEntry->handle([
                    'entry_date'       => today()->toDateString(),
                    'description'      => $item->run->month->format('F Y') . ' payroll adjustment — ' . ($emp?->display_name ?? 'Employee') . ($note ? " · {$note}" : ''),
                    'transaction_type' => TransactionType::SALARY->value,
                    'source_type'      => PayrollRun::class,
                    'source_id'        => $item->payroll_run_id,
                    'purpose'          => 'payroll_adjust_' . $item->id . '_' . now()->format('YmdHisv'),
                ], $delta > 0
                    ? [['account_id' => $expense, 'debit' => $delta, 'memo' => $emp?->display_name], ['account_id' => $payable, 'credit' => $delta] + $sub]
                    : [['account_id' => $payable, 'debit' => abs($delta)] + $sub, ['account_id' => $expense, 'credit' => abs($delta), 'memo' => $emp?->display_name]]);
            }

            $item->update($data + $calc + [
                'status' => (float) $item->paid_amount + 0.005 >= $net ? 'paid' : ((float) $item->paid_amount > 0 ? 'partial' : 'unpaid'),
            ]);
            $run = $this->refreshTotals($item->run);

            $item->profile?->logActivity('salary', $run->month->format('F Y') . ' payroll adjusted: salary ' . Bdt::money($data['salary']) . ', bonus ' . Bdt::money($data['bonus'])
                . ($entry ? " · adjustment {$entry->entry_number}" : ''));

            return $entry;
        });
    }

    /** @return list<array{id: int, amount: float}> what was cut from each advance (so a revert can give it back) */
    protected function recoverAdvances(MasterProfile $employee, float $deduction): array
    {
        $cuts = [];
        $advances = $employee->advances()->where('balance', '>', 0)->orderBy('given_on')->get();

        // First each advance's own monthly cut, then whatever is left from the oldest.
        foreach ([true, false] as $monthlyOnly) {
            foreach ($advances as $advance) {
                if ($deduction <= 0.004) {
                    break 2;
                }
                $cap = $monthlyOnly ? min((float) $advance->monthly_deduction, (float) $advance->balance) : (float) $advance->balance;
                $cut = round(min($deduction, $cap), 2);
                if ($cut <= 0) {
                    continue;
                }
                $advance->update(['balance' => round((float) $advance->balance - $cut, 2)]);
                $advance->refresh();
                $deduction = round($deduction - $cut, 2);
                $cuts[] = ['id' => $advance->id, 'amount' => $cut];
            }
        }

        return $cuts;
    }

    /** Ledger by code, created on first use (older installs may not have it yet). */
    protected function ledger(string $code, string $name, string $type, ?string $subtype, string $normal): int
    {
        return $this->accounts->id($code) ?: \App\Models\Account::create([
            'code' => $code, 'name' => $name, 'type' => $type, 'subtype' => $subtype, 'normal_balance' => $normal,
            'is_system' => true, 'is_active' => true,
        ])->id;
    }
}
