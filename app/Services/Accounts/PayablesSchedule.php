<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\AccountsSupplierBill;
use App\Models\InstallmentPlan;
use App\Models\JournalEntry;
use App\Models\LoanRepaymentSchedule;
use App\Models\MasterProfile;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\RecurringExpense;
use App\Models\Supplier;
use App\Actions\Profiles\Payroll;
use App\Services\Profiles\ProfileBalances;
use App\Support\AccountsPeriod;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Everything we have to pay, as one dated list (spec §5.7): supplier bills
 * (due = bill date + credit terms, or rescheduled), supplier kisti, loan
 * EMIs, unpaid salary (and this month's payroll when not prepared yet), and
 * recurring bills. Each row knows what drafting a payment for it means.
 */
class PayablesSchedule
{
    public const SOURCES = ['supplier' => 'Supplier', 'loan' => 'Lender', 'salary' => 'Salary', 'bill' => 'Bill'];

    /**
     * @return Collection<int, array{key: string, src: string, due: CarbonImmutable, payee: string, for: string, amount: float,
     *                              profile_id: ?int, ref: array, draft: ?string}>
     */
    public function rows(int $days = 30): Collection
    {
        $until = CarbonImmutable::today()->addDays($days);
        $drafts = $this->pendingDrafts();

        return collect()
            ->merge($this->supplierRows($drafts))
            ->merge($this->loanRows($until, $drafts))
            ->merge($this->salaryRows($drafts))
            ->merge($this->billRows($until, $drafts))
            ->filter(fn ($r) => $r['amount'] > 0.5 && $r['due']->lte($until))
            ->sortBy(fn ($r) => $r['due']->timestamp)
            ->values();
    }

    /** source_type|source_id → ref of a waiting payment draft, so a row isn't paid twice. */
    protected function pendingDrafts(): Collection
    {
        return JournalEntry::whereIn('status', [JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value])
            ->whereNotNull('source_type')->get(['id', 'source_type', 'source_id', 'voucher_ref', 'entry_number'])
            ->mapWithKeys(fn ($e) => [$e->source_type . '|' . $e->source_id => $e->ref()]);
    }

    protected function supplierRows(Collection $drafts): Collection
    {
        $supplierIds = Supplier::whereNotNull('master_profile_id')->pluck('master_profile_id', 'id');
        $profiles = MasterProfile::with('roles')->whereIn('id', $supplierIds->values()->unique())->get()->keyBy('id');
        $kistiProfiles = InstallmentPlan::where('role', 'supplier')->where('status', 'active')->pluck('master_profile_id')->unique()->all();
        $out = collect();

        // Bills (open items) of suppliers without a kisti plan.
        foreach (AccountsSupplierBill::with('supplierInvoice:id,invoice_number,serial_number,notes,invoice_date')
            ->whereIn('status', ['open', 'partial'])->get() as $bill) {
            $pid = $supplierIds[$bill->supplier_id] ?? null;
            if (! $pid || in_array($pid, $kistiProfiles)) {
                continue;
            }
            $p = $profiles[$pid] ?? null;
            $terms = (string) ($p?->roleSettings('supplier')['terms'] ?? 'cash');
            $billDate = CarbonImmutable::parse($bill->supplierInvoice?->invoice_date ?? $bill->created_at)->startOfDay();
            $inv = $bill->supplierInvoice;

            $out->push([
                'key' => "bill-{$bill->id}", 'src' => 'supplier', 'payee' => $p?->display_name ?? 'Supplier', 'profile_id' => $pid,
                'due' => $bill->due_date ? CarbonImmutable::parse($bill->due_date) : $billDate->addDays(is_numeric($terms) ? (int) $terms : 0),
                'for' => trim(($inv ? ($inv->invoice_number ?: 'PUR-' . str_pad((string) $inv->serial_number, 4, '0', STR_PAD_LEFT)) : 'Bill #' . $bill->id) . ($inv?->notes ? ' · ' . \Illuminate\Support\Str::limit($inv->notes, 28) : '')),
                'amount' => $bill->amountDue(), 'ref' => ['type' => 'bill', 'id' => $bill->id], 'draft' => $drafts[AccountsSupplierBill::class . '|' . $bill->id] ?? null,
            ]);
        }

        // Supplier kisti plans.
        foreach (InstallmentPlan::with(['profile', 'installments' => fn ($q) => $q->where('status', '!=', 'paid')->orderBy('no')])
            ->where('role', 'supplier')->where('status', 'active')->get() as $plan) {
            foreach ($plan->installments as $k) {
                $out->push([
                    'key' => "kisti-{$k->id}", 'src' => 'supplier', 'payee' => $plan->profile?->display_name ?? 'Supplier', 'profile_id' => $plan->master_profile_id,
                    'due' => CarbonImmutable::parse($k->due_date), 'for' => "Kisti {$k->no}/{$plan->count}" . ($plan->reference ? " · {$plan->reference}" : ''),
                    'amount' => round((float) $k->adjusted_amount - (float) $k->paid_amount, 2), 'ref' => ['type' => 'kisti', 'id' => $k->id], 'draft' => $drafts[\App\Models\Installment::class . '|' . $k->id] ?? null,
                ]);
            }
        }

        // Ledger payable with no open bill behind it (opening balances…).
        $billed = $out->groupBy('profile_id')->map(fn ($g) => $g->sum('amount'));
        $balances = app(ProfileBalances::class)->forProfiles($profiles->keys()->all());
        foreach ($balances as $pid => $b) {
            $rest = round((float) $b->payable - (float) ($billed[$pid] ?? 0), 2);
            if ($rest > 0.5 && ! in_array($pid, $kistiProfiles)) {
                $out->push([
                    'key' => "bal-{$pid}", 'src' => 'supplier', 'payee' => $profiles[$pid]->display_name, 'profile_id' => $pid,
                    'due' => CarbonImmutable::today(), 'for' => 'Balance without a bill', 'amount' => $rest, 'ref' => ['type' => 'balance', 'id' => $pid], 'draft' => $drafts[MasterProfile::class . '|' . $pid] ?? null,
                ]);
            }
        }

        return $out;
    }

    protected function loanRows(CarbonImmutable $until, Collection $drafts): Collection
    {
        return LoanRepaymentSchedule::with('loan:id,name,master_profile_id,installments')
            ->where('status', '!=', 'paid')->where('due_date', '<=', $until->toDateString())
            ->whereHas('loan', fn ($q) => $q->where('status', 'active'))
            ->get()
            ->map(fn ($s) => [
                'key' => "emi-{$s->id}", 'src' => 'loan', 'payee' => $s->loan->name, 'profile_id' => $s->loan->master_profile_id,
                'due' => CarbonImmutable::parse($s->due_date), 'for' => 'EMI ' . ($s->installment_no ?? '') . ($s->loan->installments ? '/' . $s->loan->installments : ''),
                'amount' => round($s->payable() - (float) $s->paid_amount, 2), 'ref' => ['type' => 'emi', 'id' => $s->id],
                'draft' => $drafts[LoanRepaymentSchedule::class . '|' . $s->id] ?? null,
            ]);
    }

    protected function salaryRows(Collection $drafts): Collection
    {
        $out = PayrollItem::with(['profile:id,display_name', 'run'])->where('status', '!=', 'paid')->where('include', true)
            ->whereHas('run', fn ($q) => $q->where('status', '!=', 'draft'))->get()
            ->groupBy('payroll_run_id')
            ->flatMap(function ($items) use ($drafts) {
                $run = $items->first()->run;
                $names = $items->pluck('profile.display_name')->filter();

                return $items->map(fn ($i) => [
                    'key' => "sal-{$i->id}", 'src' => 'salary', 'payee' => $i->profile?->display_name ?? 'Employee', 'profile_id' => $i->master_profile_id,
                    'due' => CarbonImmutable::parse($run->due_on ?? $run->month->copy()->addMonth()->day(5)),
                    'for' => $run->month->format('F') . ' salary' . ($names->count() > 1 ? ' balance' : ''),
                    'amount' => $i->unpaid(), 'ref' => ['type' => 'salary', 'id' => $i->id],
                    'draft' => $drafts[PayrollItem::class . '|' . $i->id] ?? null,
                ]);
            });

        // This month's payroll, not prepared yet — shown so the cash plan sees it.
        $month = CarbonImmutable::now()->startOfMonth();
        $run = PayrollRun::whereDate('month', $month->toDateString())->first();
        if ($run?->isDraft() && (float) $run->net > 0) {
            // The sheet is being filled in — show its net so the cash plan sees it.
            $out->push([
                'key' => 'payroll-' . $month->format('Y-m'), 'src' => 'salary', 'payee' => $run->items()->where('include', true)->count() . ' employees', 'profile_id' => null,
                'due' => CarbonImmutable::parse($run->due_on ?? $month->addMonth()->day(5)), 'for' => $month->format('F') . ' payroll (net) · draft sheet',
                'amount' => round((float) $run->net, 2), 'ref' => ['type' => 'payroll', 'id' => $run->id], 'draft' => null,
            ]);
        } elseif (! $run) {
            $preview = app(Payroll::class)->preview();
            if ($preview->isNotEmpty()) {
                $out->push([
                    'key' => 'payroll-' . $month->format('Y-m'), 'src' => 'salary', 'payee' => $preview->count() . ' employees', 'profile_id' => null,
                    'due' => $month->addMonth()->day(5), 'for' => $month->format('F') . ' payroll (net) · not prepared',
                    'amount' => round($preview->sum('net'), 2), 'ref' => ['type' => 'payroll', 'id' => 0], 'draft' => null,
                ]);
            }
        }

        return $out;
    }

    protected function billRows(CarbonImmutable $until, Collection $drafts): Collection
    {
        return RecurringExpense::with('account:id,name')->where('is_active', true)->where('next_run_date', '<=', $until->toDateString())->get()
            ->map(fn ($r) => [
                'key' => "rec-{$r->id}", 'src' => 'bill', 'payee' => $r->payee ?: $r->name, 'profile_id' => null,
                'due' => CarbonImmutable::parse($r->next_run_date), 'for' => $r->name . ' · ' . CarbonImmutable::parse($r->next_run_date)->format('F'),
                'amount' => (float) $r->amount, 'ref' => ['type' => 'recurring', 'id' => $r->id], 'draft' => null, 'varies' => $r->varies,
            ]);
    }

    /** A recurring bill's expense draft for its current period; the bill moves on to its next date. */
    public function draftRecurring(RecurringExpense $r, int $accountId, ?float $amount = null): JournalEntry
    {
        $due = $r->next_run_date->toDateString();
        $amount ??= (float) $r->amount;

        $entry = app(Vouchers::class)->draft([
            'type' => 'expense', 'date' => min($due, today()->toDateString()), 'description' => "{$r->name} — " . $r->next_run_date->format('F'),
            'party_label' => $r->payee ?: $r->name, 'money_account_id' => $accountId,
            'transaction_type' => \App\Enums\Accounts\TransactionType::RECURRING_EXPENSE->value,
            'source_type' => RecurringExpense::class, 'source_id' => $r->id, 'purpose' => "recurring_{$due}",
            'lines' => [['account_id' => $r->account_id, 'debit' => $amount], ['account_id' => $accountId, 'credit' => $amount]],
        ]);
        $r->advanceNextRunDate();

        return $entry;
    }

    /** Scheduler: a draft for every recurring bill whose day has come (once per period). */
    public function draftDueRecurring(): int
    {
        $made = 0;
        foreach (RecurringExpense::due()->get() as $r) {
            $exists = JournalEntry::where('source_type', RecurringExpense::class)->where('source_id', $r->id)
                ->where('purpose', 'recurring_' . $r->next_run_date->toDateString())->exists();
            if ($exists) {
                $r->advanceNextRunDate();

                continue;
            }
            $this->draftRecurring($r, (int) ($r->from_account_id ?: app(\App\Services\Profiles\LedgerAccounts::class)->cashAccounts()[0]['id'] ?? 0));
            $made++;
        }

        return $made;
    }

    /** overdue / week (to the end of this Sat–Fri week) / next / later */
    public static function when(CarbonImmutable $due): string
    {
        $today = CarbonImmutable::today();
        $weekEnd = AccountsPeriod::weekStart($today)->addDays(6);

        return match (true) {
            $due->lt($today) => 'overdue',
            $due->lte($weekEnd) => 'week',
            $due->lte($weekEnd->addDays(7)) => 'next',
            default => 'later',
        };
    }

    /** "2 দিন পার", "in 3 days", "today" */
    public static function note(CarbonImmutable $due): string
    {
        $d = (int) CarbonImmutable::today()->diffInDays($due, false);

        return $d < 0 ? abs($d) . ' দিন পার' : ($d === 0 ? 'today' : "in {$d} day" . ($d > 1 ? 's' : ''));
    }

    public static function money(float $v): string
    {
        return Bdt::money($v);
    }
}
