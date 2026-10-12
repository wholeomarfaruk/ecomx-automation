<?php

namespace App\Livewire\Accounts\Profiles;

use App\Actions\Profiles\LoanKisti;
use App\Exceptions\Accounts\LockedFiscalPeriodException;
use App\Livewire\Accounts\Profiles\Concerns\HandlesVoucherModals;
use App\Models\Loan;
use App\Models\LoanRepaymentSchedule;
use App\Models\MasterProfile;
use App\Services\Profiles\InstallmentScheduler;
use App\Services\Profiles\ProfileCounts;
use App\Support\Bdt;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Accounts Panel › Lenders — design: Lenders.dc.html.
 * One card per active loan, the next 60 days of kisti, and the New loan /
 * EMI rule form (spec §9) with a live schedule preview.
 */
#[Title('Lenders')]
#[Layout('layouts.accounts.profiles')]
class Lenders extends Component
{
    use HandlesVoucherModals;

    public bool $showLoanForm = false;

    public array $loanForm = [];

    /** Loan whose full schedule is open; 0 = all loans. */
    public ?int $scheduleLoan = null;

    #[Url(as: 'new', except: false)]
    public bool $openNewLoan = false;

    public function mount(): void
    {
        if ($this->openNewLoan) {
            $this->openLoanForm();
            $this->openNewLoan = false;
        }
    }

    #[Computed]
    public function loans(): Collection
    {
        return Loan::with(['profile.roles', 'profile.city:id,name', 'schedules' => fn ($q) => $q->orderBy('due_date'), 'repayments'])
            ->where('status', 'active')
            ->orderBy('start_date')
            ->get();
    }

    /** Card figures in the design's shape. */
    public function card(Loan $loan): array
    {
        $schedules = $loan->schedules;
        $paidAmount = round((float) $loan->repayments->sum(fn ($r) => (float) $r->principal_amount + (float) $r->interest_amount), 2);
        $total = round((float) ($loan->total_payable ?: ((float) $loan->principal + (float) $schedules->sum('interest_due'))), 2);
        $outstanding = max(0, round($total - $paidAmount, 2));
        $paidCount = $schedules->where('status', 'paid')->count();
        $next = $schedules->first(fn ($s) => $s->status !== 'paid');
        $profile = $loan->profile;

        $type = match (true) {
            $profile?->hasRole('owner') => 'Owner loan',
            ($loan->lender_type ?? $profile?->roleSettings('lender')['lender_type'] ?? null) === 'bank' => 'Bank',
            ($loan->lender_type ?? $profile?->roleSettings('lender')['lender_type'] ?? null) === 'personal' => 'Personal',
            default => 'Business',
        };

        $rate = match ($loan->rate_type) {
            'flat'     => 'Flat ' . Bdt::money($total - (float) $loan->principal) . ' profit',
            'reducing' => rtrim(rtrim(number_format((float) $loan->interest_rate, 2), '0'), '.') . '% reducing',
            default    => 'No interest',
        };

        $adjusted = $next && $next->adjusted_amount !== null && $next->payable() < $next->emi();

        return [
            'name'        => $loan->name,
            'code'        => $profile?->code ?? '—',
            'note'        => $loan->note ?: trim(($profile?->city?->name ? $profile->city->name . ' · ' : '') . 'since ' . $loan->start_date->format('M Y')),
            'type'        => $type,
            'typeClass'   => ['Bank' => 'bg-[#e0f2fe] text-[#075985]', 'Owner loan' => 'bg-[#fef3c7] text-[#92400e]'][$type] ?? 'bg-[#e0e7ff] text-[#3730a3]',
            'rule'        => InstallmentScheduler::ruleLabel($loan->frequency ?? 'monthly', $schedules->count() ?: null, $loan->custom_offsets),
            'rate'        => $rate,
            'paidCount'   => $schedules->count() ? "{$paidCount} / {$schedules->count()}" : Bdt::money($paidAmount) . ' of ' . Bdt::money($total),
            'pct'         => $total > 0 ? (int) round($paidAmount / $total * 100) : 0,
            'borrowed'    => Bdt::money($loan->principal),
            'total'       => Bdt::money($total),
            'paid'        => Bdt::money($paidAmount),
            'outstanding' => Bdt::money($outstanding),
            'outstandingRaw' => $outstanding,
            'nextDate'    => $next ? $next->due_date->format('d M') . ' · kisti ' . ($next->installment_no ?? '') . '/' . $schedules->count() : 'No fixed date',
            'nextAmt'     => $next ? Bdt::money($next->payable() - (float) $next->paid_amount) : '—',
            'nextNote'    => $adjusted ? Bdt::money($next->emi() - $next->payable()) . ' support adjusted' : '',
            'profileId'   => $profile?->id,
        ];
    }

    #[Computed]
    public function summary(): array
    {
        $loans = $this->loans;
        $cards = $loans->map(fn ($l) => $this->card($l));
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $thisMonth = $loans->flatMap->schedules->filter(fn ($s) => $s->due_date->between($monthStart, $monthEnd));
        $paid = $thisMonth->sum(fn ($s) => (float) $s->paid_amount);
        $upcoming = $thisMonth->filter(fn ($s) => $s->status !== 'paid');

        $support = $loans->flatMap->schedules->filter(fn ($s) => $s->status !== 'paid' && $s->adjusted_amount !== null && $s->payable() < $s->emi());

        return [
            'borrowed'    => $loans->sum(fn ($l) => (float) $l->principal),
            'lenders'     => $loans->pluck('master_profile_id')->filter()->unique()->count(),
            'loans'       => $loans->count(),
            'outstanding' => $cards->sum('outstandingRaw'),
            'month'       => $thisMonth->sum(fn ($s) => max($s->payable(), (float) $s->paid_amount)),
            'monthPaid'   => $paid,
            'monthLeft'   => $upcoming->sum(fn ($s) => $s->payable() - (float) $s->paid_amount),
            'monthNext'   => $upcoming->sortBy('due_date')->first()?->due_date?->format('d M'),
            'support'     => $support->sum(fn ($s) => $s->emi() - $s->payable()),
            'supportWho'  => $support->map(fn ($s) => $loans->firstWhere('id', $s->loan_id)?->profile?->display_name)->filter()->unique()->implode(', '),
        ];
    }

    /** Kisti due from the start of this month to 60 days ahead. */
    #[Computed]
    public function upcoming(): array
    {
        $loans = $this->loans->keyBy('id');

        return LoanRepaymentSchedule::whereIn('loan_id', $loans->keys())
            ->whereBetween('due_date', [now()->startOfMonth()->toDateString(), now()->addDays(60)->toDateString()])
            ->orderBy('due_date')
            ->get()
            ->map(fn ($s) => $this->scheduleRow($s, $loans[$s->loan_id]))
            ->all();
    }

    public function scheduleRow(LoanRepaymentSchedule $s, Loan $loan): array
    {
        $count = $loan->installments ?: $loan->schedules()->count();
        $support = '—';
        if ($s->status === 'paid' && (float) $s->support_amount > 0) {
            $support = '+ ' . Bdt::money($s->support_amount) . ' extra';
        } elseif ($s->status !== 'paid' && $s->payable() < $s->emi()) {
            $support = '− ' . Bdt::money($s->emi() - $s->payable());
        }

        $status = match (true) {
            $s->status === 'paid' => 'Paid',
            $s->due_date->lt(today()) => 'Overdue',
            $s->due_date->lte(today()->addDays(7)) => 'Due soon',
            default => 'Upcoming',
        };

        return [
            'date'    => $s->due_date->format('d M'),
            'lender'  => $loan->name,
            'no'      => ($s->installment_no ?? '—') . ' / ' . $count,
            'emi'     => Bdt::money($s->emi()),
            'support' => $support,
            'pay'     => Bdt::money($s->status === 'paid' ? max((float) $s->paid_amount, $s->payable()) : $s->payable() - (float) $s->paid_amount),
            'status'  => $status,
            'badge'   => [
                'Paid'     => 'bg-[#d1fae5] text-[#065f46]',
                'Due soon' => 'bg-[#fef3c7] text-[#92400e]',
                'Overdue'  => 'bg-[#fee2e2] text-[#991b1b]',
            ][$status] ?? 'bg-[#f3f4f6] text-[#374151]',
        ];
    }

    /* ---------------- Schedule modal ---------------- */

    public function showSchedule(int $loanId = 0): void
    {
        $this->scheduleLoan = $loanId;
    }

    public function closeSchedule(): void
    {
        $this->scheduleLoan = null;
    }

    #[Computed]
    public function scheduleRows(): array
    {
        if ($this->scheduleLoan === null) {
            return [];
        }

        $loans = $this->scheduleLoan ? Loan::whereKey($this->scheduleLoan)->get() : $this->loans;

        return $loans->flatMap(fn (Loan $l) => $l->schedules()->get()->map(fn ($s) => [$s, $l]))
            ->sortBy(fn ($pair) => $pair[0]->due_date->timestamp)
            ->map(fn ($pair) => ['year' => $pair[0]->due_date->format('Y')] + $this->scheduleRow($pair[0], $pair[1]))
            ->values()->all();
    }

    /* ---------------- New loan / EMI rule ---------------- */

    public function openLoanForm(?int $profileId = null): void
    {
        $this->resetErrorBag();
        $this->loanForm = [
            'profile_id'      => $profileId,
            'name'            => '',
            'principal'       => null,
            'cash_account_id' => $this->defaultCashAccount(),
            'rate_type'       => 'none',
            'rate'            => null,
            'total_payable'   => null,
            'frequency'       => 'monthly',
            'installments'    => 12,
            'start_date'      => today()->toDateString(),
            'custom_offsets'  => '',
            'support_rule'    => 'next_kisti',
            'note'            => '',
        ];
        $this->showLoanForm = true;
    }

    public function closeLoanForm(): void
    {
        $this->showLoanForm = false;
    }

    #[Computed]
    public function lenderOptions(): array
    {
        return MasterProfile::withRole('lender')->orderBy('display_name')->pluck('display_name', 'id')->all();
    }

    protected function offsets(): array
    {
        return array_values(array_filter(array_map('intval', preg_split('/[\s,\/]+/', (string) ($this->loanForm['custom_offsets'] ?? ''))), fn ($d) => $d > 0));
    }

    #[Computed]
    public function preview(): array
    {
        $f = $this->loanForm;

        if (! $this->showLoanForm || ! is_numeric($f['principal'] ?? null) || (float) $f['principal'] <= 0) {
            return [];
        }

        try {
            return app(InstallmentScheduler::class)->build(
                $f['frequency'], (int) $f['installments'], $f['start_date'] ?: today()->toDateString(), (float) $f['principal'],
                $f['rate_type'], is_numeric($f['rate']) ? (float) $f['rate'] : null,
                is_numeric($f['total_payable']) ? (float) $f['total_payable'] : null, $this->offsets(),
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public function saveLoan(): void
    {
        $this->validate([
            'loanForm.profile_id'      => 'required|integer|exists:master_profiles,id',
            'loanForm.principal'       => 'required|numeric|min:1',
            'loanForm.cash_account_id' => 'required|integer|exists:accounts,id',
            'loanForm.rate_type'       => 'required|in:none,flat,reducing',
            'loanForm.rate'            => 'nullable|numeric|min:0|max:100',
            'loanForm.total_payable'   => 'nullable|numeric|min:0',
            'loanForm.frequency'       => 'required|in:' . implode(',', InstallmentScheduler::FREQUENCIES),
            'loanForm.installments'    => 'required_unless:loanForm.frequency,custom|nullable|integer|min:1|max:600',
            'loanForm.start_date'      => 'required|date',
            'loanForm.support_rule'    => 'required|in:next_kisti,principal',
            'loanForm.name'            => 'nullable|string|max:120',
            'loanForm.note'            => 'nullable|string|max:255',
        ], [], ['loanForm.profile_id' => 'lender', 'loanForm.principal' => 'principal', 'loanForm.installments' => 'number of kisti']);

        if ($this->loanForm['frequency'] === 'custom' && ! $this->offsets()) {
            $this->addError('loanForm.custom_offsets', 'Give the day offsets, e.g. 15, 45, 90.');

            return;
        }

        try {
            $loan = app(LoanKisti::class)->create(MasterProfile::findOrFail($this->loanForm['profile_id']), [
                ...$this->loanForm,
                'custom_offsets' => $this->offsets(),
            ]);
        } catch (LockedFiscalPeriodException|\RuntimeException $e) {
            $this->addError('loanForm.principal', $e->getMessage());

            return;
        }

        $this->showLoanForm = false;
        ProfileCounts::flush();
        unset($this->loans, $this->summary, $this->upcoming);
        $this->dispatch('toast', type: 'success', message: "Loan {$loan->name} added with " . $loan->schedules()->count() . ' kisti.');
    }

    public function render()
    {
        return view('livewire.accounts.profiles.lenders', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'lenders']);
    }
}
