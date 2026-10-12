<?php

namespace App\Livewire\Accounts;

use App\Models\EmployeeAdvance;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\MasterProfile;
use App\Services\Accounts\KistiBuilder;
use App\Services\Accounts\KistiPlans;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsAccess;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Accounts Panel › Loans & EMI — design: LoansEmi.dc.html.
 * Every kisti plan in one table (loans taken, kisti sales, supplier kisti,
 * staff advances) and the new plan builder with a live schedule preview.
 */
#[Title('Loans & EMI')]
#[Layout('layouts.accounts.app')]
class LoansEmi extends Component
{
    public const DIRS = ['all' => 'All', 'pay' => 'আমি দিবো', 'recv' => 'আমি পাবো'];

    public const FQ = ['all' => 'Any frequency', 'monthly' => 'Monthly', 'weekly' => 'Weekly', 'daily' => 'Daily', 'yearly' => 'Yearly', 'custom' => 'Custom'];

    public const TYPES = [
        'loan'     => ['label' => 'ঋণ নিলাম', 'sub' => 'আমি দিবো · lender', 'party' => 'Lender', 'amount' => 'ঋণের পরিমাণ', 'word' => 'ফেরত দিতে হবে', 'role' => 'lender'],
        'sale'     => ['label' => 'কিস্তিতে বিক্রি', 'sub' => 'আমি পাবো · customer', 'party' => 'Customer', 'amount' => 'বিক্রির বাকি অংশ', 'word' => 'পাবো', 'role' => 'customer'],
        'supplier' => ['label' => 'Supplier-কে কিস্তি', 'sub' => 'আমি দিবো · supplier', 'party' => 'Supplier', 'amount' => 'মোট দেনা', 'word' => 'দিতে হবে', 'role' => 'supplier'],
    ];

    public const SUPPORT = [
        'next_kisti' => ['পরের কিস্তি থেকে কাটবে', 'যেমন ৳21,000 কিস্তিতে ৳22,500 দিলে পরের কিস্তি ৳19,500'],
        'principal'  => ['আসল থেকে কমবে', 'বাকি টাকা কমবে, কিস্তি সংখ্যা একই থাকবে, শেষ কিস্তি ছোট হবে'],
        'advance'    => ['Advance হিসেবে জমা থাকবে', 'যেকোনো সময় adjust করা যাবে'],
    ];

    #[Url(except: 'all')]
    public string $dir = 'all';

    #[Url(except: 'all')]
    public string $fq = 'all';

    public array $plan = [];

    public function mount(): void
    {
        AccountsAccess::authorize('view');
        $this->resetPlan();
    }

    public function resetPlan(?string $type = null): void
    {
        $this->resetErrorBag();
        $this->plan = [
            'type' => $type ?? 'loan', 'profile_id' => null, 'amount' => 200000, 'first_due' => today()->addMonthNoOverflow()->toDateString(),
            'frequency' => 'monthly', 'count' => 12, 'offsets' => '15, 45, 90', 'rate_type' => 'flat', 'rate' => 10,
            'support_rule' => 'next_kisti', 'late_rule' => 'reminder', 'account_id' => app(LedgerAccounts::class)->cashAccounts()[0]['id'] ?? null,
        ];
    }

    #[On('voucher-saved')]
    public function refreshData(): void
    {
        unset($this->all);
    }

    public function setDir(string $d): void
    {
        $this->dir = array_key_exists($d, self::DIRS) ? $d : 'all';
    }

    public function setFq(string $f): void
    {
        $this->fq = array_key_exists($f, self::FQ) ? $f : 'all';
    }

    public function setType(string $t): void
    {
        if (isset(self::TYPES[$t])) {
            $this->plan['type'] = $t;
            $this->plan['profile_id'] = null;
        }
    }

    /* ---------------- plans table ---------------- */

    /** @return Collection<int, array> */
    #[Computed]
    public function all(): Collection
    {
        return collect()
            ->merge($this->loanRows())
            ->merge($this->planRows())
            ->merge($this->advanceRows())
            ->sortBy(fn ($r) => [$r['st'] === 'late' ? 0 : ($r['st'] === 'soon' ? 1 : 2), $r['nextSort']])
            ->values();
    }

    #[Computed]
    public function rows(): Collection
    {
        return $this->all->filter(fn ($r) => ($this->dir === 'all' || $r['dir'] === $this->dir) && ($this->fq === 'all' || $r['fq'] === $this->fq))->values();
    }

    protected function loanRows(): Collection
    {
        return Loan::with(['profile:id,display_name', 'schedules' => fn ($q) => $q->orderBy('due_date'), 'repayments:id,loan_id,principal_amount,interest_amount'])
            ->whereIn('status', ['active', 'pending'])->get()
            ->map(function (Loan $l) {
                $s = $l->schedules;
                $total = (float) ($l->total_payable ?: ($s->sum(fn ($x) => $x->emi()) ?: $l->principal));
                $paid = (float) $l->repayments->sum(fn ($r) => (float) $r->principal_amount + (float) $r->interest_amount);
                $next = $s->first(fn ($x) => $x->status !== 'paid');
                $nextLeft = $next ? round($next->payable() - (float) $next->paid_amount, 2) : 0;
                $overdue = $s->filter(fn ($x) => $x->status !== 'paid' && $x->due_date->lt(today()));
                $rate = $l->rate_type !== 'none' && $l->interest_rate ? ' · ' . rtrim(rtrim(number_format((float) $l->interest_rate, 2), '0'), '.') . '% ' . $l->rate_type : ($l->rate_type === 'none' ? ' · no interest' : '');

                [$st, $status] = match (true) {
                    $l->status === 'pending' => ['flex', 'Receipt pending'],
                    $s->isEmpty() => ['flex', 'Flexible'],
                    default => $this->state($overdue->sum(fn ($x) => $x->payable() - (float) $x->paid_amount), $next?->due_date, 'pay'),
                };

                return [
                    'key' => "loan-{$l->id}", 'profile_id' => $l->master_profile_id, 'party' => $l->profile?->display_name ?? $l->name,
                    'kind' => ($l->lender_type === 'owner' ? 'Owner loan' : 'Loan taken') . ' · ' . Bdt::money($l->principal) . $rate,
                    'dir' => 'pay', 'fq' => $s->isEmpty() ? 'custom' : ($l->frequency ?: 'monthly'),
                    'rule' => $s->isEmpty() ? 'Flexible' : $this->rule($l->frequency, $s->count(), $l->custom_offsets),
                    'done' => $s->isEmpty() ? Bdt::money($paid) . ' paid' : $this->doneCount($s->where('status', 'paid')->count(), $s->filter(fn ($x) => $x->status !== 'paid')->sum(fn ($x) => (float) $x->paid_amount / max(1, $x->payable())), $s->count()),
                    'pct' => $total > 0 ? (int) min(100, round($paid / $total * 100)) : 0,
                    'kisti' => $s->isEmpty() ? '—' : Bdt::money($s->first()->emi()),
                    'left' => Bdt::money(max(0, $total - $paid)),
                    'next' => $next ? $next->due_date->format('d M') : 'No fixed date', 'nextSort' => $next?->due_date?->timestamp ?? PHP_INT_MAX,
                    'nextNote' => $next && $next->payable() < $next->emi() - 0.5 ? Bdt::money($nextLeft) . ' after support' : '',
                    'st' => $st, 'status' => $status,
                ];
            });
    }

    protected function planRows(): Collection
    {
        return InstallmentPlan::with(['profile:id,display_name', 'installments'])->where('status', 'active')->get()
            ->map(function (InstallmentPlan $p) {
                $k = $p->installments;
                $dir = $p->role === 'customer' ? 'recv' : 'pay';
                $total = (float) $k->sum('adjusted_amount') ?: (float) $p->total;
                $paid = (float) $k->sum('paid_amount');
                $next = $k->first(fn ($x) => $x->status !== 'paid');
                $overdue = $k->filter(fn ($x) => $x->status !== 'paid' && $x->due_date->lt(today()));
                [$st, $status] = $this->state($overdue->sum(fn ($x) => (float) $x->adjusted_amount - (float) $x->paid_amount), $next?->due_date, $dir, $overdue->first()?->due_date);

                return [
                    'key' => "plan-{$p->id}", 'profile_id' => $p->master_profile_id, 'party' => $p->profile?->display_name ?? '—',
                    'kind' => ($dir === 'recv' ? 'Credit sale on kisti' : 'Supplier kisti') . ' · ' . Bdt::money($p->total) . ($p->reference ? " · {$p->reference}" : ''),
                    'dir' => $dir, 'fq' => $p->frequency, 'rule' => $this->rule($p->frequency, $p->count, $p->custom_offsets),
                    'done' => $this->doneCount($k->where('status', 'paid')->count(), $k->filter(fn ($x) => $x->status !== 'paid')->sum(fn ($x) => (float) $x->paid_amount / max(1, (float) $x->adjusted_amount)), $k->count()),
                    'pct' => $total > 0 ? (int) min(100, round($paid / $total * 100)) : 0,
                    'kisti' => Bdt::money($p->amount), 'left' => Bdt::money(max(0, $total - $paid)),
                    'next' => $next ? $next->due_date->format('d M') : '—', 'nextSort' => $next?->due_date?->timestamp ?? PHP_INT_MAX,
                    'nextNote' => $next && (float) $next->adjusted_amount < (float) $next->amount - 0.5 ? Bdt::money((float) $next->adjusted_amount - (float) $next->paid_amount) . ' after support' : '',
                    'st' => $st, 'status' => $status,
                ];
            });
    }

    protected function advanceRows(): Collection
    {
        $payday = CarbonImmutable::today()->startOfMonth()->addMonth()->day(5);

        return EmployeeAdvance::with('profile:id,display_name')->where('balance', '>', 0)->get()
            ->map(function (EmployeeAdvance $a) use ($payday) {
                $per = max(1, (float) $a->monthly_deduction);
                $count = (int) ceil((float) $a->amount / $per);
                $paid = (float) $a->amount - (float) $a->balance;

                return [
                    'key' => "adv-{$a->id}", 'profile_id' => $a->master_profile_id, 'party' => $a->profile?->display_name ?? 'Employee',
                    'kind' => 'Staff advance · ' . Bdt::money($a->amount), 'dir' => 'recv', 'fq' => 'monthly', 'rule' => 'Monthly · from salary',
                    'done' => (int) floor($paid / $per) . ' / ' . $count, 'pct' => (int) round($paid / max(1, (float) $a->amount) * 100),
                    'kisti' => Bdt::money($per), 'left' => Bdt::money($a->balance),
                    'next' => $payday->format('d M'), 'nextSort' => $payday->timestamp, 'nextNote' => 'payroll-এ কাটবে',
                    'st' => 'ok', 'status' => 'On track',
                ];
            });
    }

    /** [style key, label]: late / soon (≤3 days) / ok */
    protected function state(float $overdue, $next, string $dir, $firstOverdue = null): array
    {
        if ($overdue > 0.5) {
            return ['late', $dir === 'recv' && $firstOverdue ? 'Missed ' . $firstOverdue->format('M') : Bdt::money($overdue) . ' overdue'];
        }
        if (! $next) {
            return ['ok', 'Completed'];
        }
        $d = (int) today()->diffInDays($next, false);

        return match (true) {
            $d <= 0 => ['soon', 'Due today'],
            $d === 1 => ['soon', 'Due tomorrow'],
            $d <= 3 => ['soon', "Due in {$d} days"],
            default => ['ok', 'On track'],
        };
    }

    protected function rule(?string $frequency, int $count, ?array $offsets): string
    {
        return ($frequency ?? 'monthly') === 'custom'
            ? 'Custom · ' . implode('/', $offsets ?? []) . 'd'
            : ucfirst($frequency ?? 'monthly') . " · {$count}";
    }

    /** "5 / 12", or "1.6 / 3" when a kisti is part-paid */
    protected function doneCount(int $paid, float $partial, int $count): string
    {
        $done = round($paid + $partial, 1);

        return (fmod($done, 1.0) === 0.0 ? (string) (int) $done : (string) $done) . " / {$count}";
    }

    #[Computed]
    public function cards(): array
    {
        $all = $this->all;
        $from = today()->startOfMonth()->toDateString();
        $to = today()->endOfMonth()->toDateString();

        $loanDue = LoanRepaymentSchedule::whereHas('loan', fn ($q) => $q->where('status', 'active'))->whereBetween('due_date', [$from, $to])->get();
        $kisti = fn ($role) => Installment::whereHas('plan', fn ($q) => $q->where('role', $role)->where('status', 'active'))->whereBetween('due_date', [$from, $to])->get();
        $supplier = $kisti('supplier');
        $customer = $kisti('customer');

        $toPay = $loanDue->sum(fn ($s) => max(0, $s->payable() - (float) $s->paid_amount)) + $supplier->sum(fn ($k) => max(0, (float) $k->adjusted_amount - (float) $k->paid_amount));
        $paidPay = (float) LoanRepayment::whereBetween('paid_at', [$from, $to])->sum(DB::raw('principal_amount + interest_amount'))
            + (float) Installment::whereHas('plan', fn ($q) => $q->where('role', 'supplier'))->whereBetween('paid_at', [$from, $to . ' 23:59:59'])->sum('paid_amount');
        $advances = (float) EmployeeAdvance::where('balance', '>', 0)->get()->sum(fn ($a) => min((float) $a->monthly_deduction, (float) $a->balance));
        $toGet = $customer->sum(fn ($k) => max(0, (float) $k->adjusted_amount - (float) $k->paid_amount)) + $advances;
        $got = (float) Installment::whereHas('plan', fn ($q) => $q->where('role', 'customer'))->whereBetween('paid_at', [$from, $to . ' 23:59:59'])->sum('paid_amount');

        $late = $all->where('st', 'late');
        $supportPay = (float) LoanRepaymentSchedule::whereHas('loan', fn ($q) => $q->where('status', 'active'))->sum('support_amount')
            + (float) Installment::whereHas('plan', fn ($q) => $q->where('role', 'supplier')->where('status', 'active'))->sum('support_amount');
        $supportGet = (float) Installment::whereHas('plan', fn ($q) => $q->where('role', 'customer')->where('status', 'active'))->sum('support_amount');

        return [
            'active' => [$all->count(), 'দিবো ' . $all->where('dir', 'pay')->count() . ' · পাবো ' . $all->where('dir', 'recv')->count()],
            'pay'    => [$toPay, Bdt::money($paidPay) . ' paid already'],
            'get'    => [$toGet, Bdt::money($got) . ' collected so far'],
            'late'   => [$late->count(), $late->isEmpty() ? 'কোনো কিস্তি মিস হয়নি' : $late->take(3)->map(fn ($r) => $r['party'] . ' (' . ($r['dir'] === 'pay' ? 'দিবো' : 'পাবো') . ')')->implode(' · ')],
            'support' => [$supportPay + $supportGet, 'দিয়েছি ' . Bdt::money($supportPay) . ' · পেয়েছি ' . Bdt::money($supportGet)],
        ];
    }

    /* ---------------- builder ---------------- */

    #[Computed]
    public function partyOptions(): array
    {
        return MasterProfile::withRole(self::TYPES[$this->plan['type']]['role'] ?? 'lender')->orderBy('display_name')->pluck('display_name', 'id')->all();
    }

    #[Computed]
    public function cashAccounts(): array
    {
        return app(LedgerAccounts::class)->cashAccounts();
    }

    #[Computed]
    public function preview(): array
    {
        $p = $this->plan;
        try {
            $s = app(KistiBuilder::class)->build($p['frequency'], (int) $p['count'], $p['first_due'] ?: today()->toDateString(), (float) $p['amount'], $p['rate_type'], (float) $p['rate'], (string) $p['offsets']);
        } catch (Throwable) {
            $s = ['rows' => [], 'total' => 0, 'interest' => 0, 'kisti' => 0, 'last' => 0, 'count' => 0, 'end' => null];
        }

        $rows = $s['rows'];
        $sched = count($rows) > 7
            ? [...array_slice($rows, 0, 5), ['no' => '…', 'more' => count($rows) - 6], end($rows)]
            : $rows;
        $amount = Bdt::money((float) $p['amount']);
        $n = $s['count'];

        return $s + [
            'sched' => $sched,
            'note' => match ($p['type']) {
                'loan' => "Create করলে ঋণ পাওয়ার voucher (Dr Bank / Cr Loan — {$amount}) Posting Pending-এ যাবে, আর {$n}টা কিস্তি schedule-এ বসবে।",
                'sale' => "Create করলে invoice-এর বাকি {$amount} এই plan-এ চলে যাবে। প্রতি কিস্তির আগের দিন customer-কে SMS যাবে।",
                default => "Create করলে supplier-এর {$amount} দেনা {$n}টা কিস্তিতে ভাগ হবে, Payables-এ due date অনুযায়ী দেখাবে।",
            } . ($s['interest'] > 0 && $p['type'] !== 'loan' ? ' লাভ/সুদ ' . Bdt::money($s['interest']) . '-এর একটা draft journal-ও হবে।' : ''),
        ];
    }

    public function createPlan(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'plan.type' => 'required|in:loan,sale,supplier',
            'plan.profile_id' => 'required|integer|exists:master_profiles,id',
            'plan.amount' => 'required|numeric|min:1',
            'plan.first_due' => 'required|date',
            'plan.frequency' => 'required|in:' . implode(',', array_keys(KistiBuilder::FREQUENCIES)),
            'plan.count' => 'required_unless:plan.frequency,custom|nullable|integer|min:1|max:600',
            'plan.offsets' => ['required_if:plan.frequency,custom', 'nullable', 'string', 'max:200', 'regex:/^[\d\s,\/]*$/'],
            'plan.rate_type' => 'required|in:flat,reducing,none',
            'plan.rate' => 'required_unless:plan.rate_type,none|nullable|numeric|min:0|max:100',
            'plan.support_rule' => 'required|in:' . implode(',', KistiPlans::SUPPORT),
            'plan.late_rule' => 'required|in:' . implode(',', array_keys(KistiPlans::LATE)),
            'plan.account_id' => 'required_if:plan.type,loan|nullable|integer|exists:accounts,id',
        ], [], [
            'plan.profile_id' => strtolower(self::TYPES[$this->plan['type']]['party'] ?? 'party'), 'plan.amount' => 'amount', 'plan.first_due' => 'first due date',
            'plan.count' => 'number of kisti', 'plan.offsets' => 'day offsets', 'plan.rate' => 'rate', 'plan.account_id' => 'received in',
        ]);

        try {
            [$made, $entry] = app(KistiPlans::class)->create($this->plan);
        } catch (Throwable $e) {
            report($e);
            $this->addError('plan.amount', $e->getMessage());

            return;
        }

        $count = $made instanceof Loan ? $made->schedules()->count() : $made->installments()->count();
        $this->dispatch('toast', type: 'success', message: "Plan created · {$count} kisti" . ($entry ? " · {$entry->ref()} waiting in Posting Pending" : '') . '.');
        $this->resetPlan($this->plan['type']);
        unset($this->all);
    }

    public function render()
    {
        return view('livewire.accounts.loans-emi', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'loans', 'heading' => 'Loans & EMI', 'crumb' => 'Accounts / Loans & EMI']);
    }
}
