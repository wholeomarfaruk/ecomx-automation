<?php

namespace App\Livewire\Accounts\Profiles\Concerns;

use App\Actions\Profiles\LoanKisti;
use App\Actions\Profiles\Payroll;
use App\Actions\Profiles\ProfileVouchers;
use App\Models\Loan;
use App\Models\MasterProfile;
use App\Services\Profiles\LedgerAccounts;
use App\Services\Profiles\ProfileBalances;
use App\Services\Profiles\ProfileCounts;
use App\Support\Bdt;
use App\Support\BdPhone;
use Livewire\Attributes\Computed;
use RuntimeException;

/**
 * The voucher modals shared by every profile page. One modal, keyed by
 * $voucherType:
 *   collect          receipt voucher (customer)          Dr cash / Cr receivable
 *   pay              payment voucher (supplier)          Dr payable / Cr cash
 *   supplier_advance advance to supplier                 Dr supplier advance / Cr cash
 *   kisti            loan kisti with support field       Dr loan + interest / Cr cash
 *   capital          owner capital in                    Dr cash / Cr capital
 *   drawing          owner drawing                       Dr drawings / Cr cash
 *   salary           pay salary                          Dr salary payable / Cr cash
 *   salary_advance   salary advance                      Dr salary advance / Cr cash
 */
trait HandlesVoucherModals
{
    public ?string $voucherType = null;

    public array $voucher = [];

    public string $voucherSearch = '';

    public const VOUCHER_ROLES = [
        'collect'          => 'customer',
        'pay'              => 'supplier',
        'supplier_advance' => 'supplier',
        'kisti'            => 'lender',
        'capital'          => 'owner',
        'drawing'          => 'owner',
        'salary'           => 'employee',
        'salary_advance'   => 'employee',
    ];

    public const VOUCHER_TITLES = [
        'collect'          => 'টাকা গ্রহণ · Collect',
        'pay'              => 'টাকা দিন · Pay',
        'supplier_advance' => 'Advance দিন',
        'kisti'            => 'কিস্তি দিন · Pay kisti',
        'capital'          => 'মূলধন যোগ · Add capital',
        'drawing'          => 'উত্তোলন · Drawing',
        'salary'           => 'বেতন দিন · Pay salary',
        'salary_advance'   => 'অগ্রিম দিন · Advance',
    ];

    public function openVoucher(string $type, ?int $profileId = null, ?int $loanId = null): void
    {
        abort_unless(isset(self::VOUCHER_ROLES[$type]), 404);

        $this->resetErrorBag();
        $this->voucherSearch = '';
        $this->voucherType = $type;
        $this->voucher = [
            'profile_id' => $profileId,
            'loan_id'    => $loanId,
            'amount'     => null,
            'monthly'    => null,
            'account_id' => $this->defaultCashAccount(),
            'date'       => today()->toDateString(),
            'note'       => '',
        ];

        if ($loanId && ! $profileId) {
            $this->voucher['profile_id'] = Loan::whereKey($loanId)->value('master_profile_id');
        }

        $this->suggestAmount();
    }

    public function closeVoucher(): void
    {
        $this->voucherType = null;
    }

    public function pickVoucherProfile(int $id): void
    {
        $this->voucher['profile_id'] = $id;
        $this->voucher['loan_id'] = null;
        $this->voucherSearch = '';
        $this->suggestAmount();
    }

    public function updatedVoucher($value, $key): void
    {
        if ($key === 'loan_id') {
            $this->suggestAmount();
        }
    }

    /** Pre-fill the amount with what is currently owed, where that is obvious. */
    protected function suggestAmount(): void
    {
        $id = $this->voucher['profile_id'] ?? null;
        if (! $id) {
            return;
        }

        $b = app(ProfileBalances::class)->forProfile($id);

        $amount = match ($this->voucherType) {
            'collect' => (float) $b->receivable,
            'pay'     => (float) $b->payable,
            'salary'  => (float) $b->salary_payable,
            'kisti'   => $this->nextKistiAmount(),
            default   => null,
        };

        $this->voucher['amount'] = $amount && $amount > 0 ? round($amount, 2) : null;
    }

    protected function nextKistiAmount(): ?float
    {
        $loan = $this->voucherLoan();
        $next = $loan?->schedules()->where('status', '!=', 'paid')->orderBy('due_date')->first();

        return $next ? round($next->payable() - (float) $next->paid_amount, 2) : null;
    }

    protected function voucherLoan(): ?Loan
    {
        $loanId = $this->voucher['loan_id'] ?? null;
        $profileId = $this->voucher['profile_id'] ?? null;

        if (! $loanId && $profileId) {
            $loanId = Loan::where('master_profile_id', $profileId)->where('status', 'active')->orderBy('id')->value('id');
            $this->voucher['loan_id'] = $loanId;
        }

        return $loanId ? Loan::find($loanId) : null;
    }

    public function saveVoucher(): void
    {
        $type = $this->voucherType;

        if (! isset(self::VOUCHER_ROLES[$type])) {
            return;
        }

        $this->validate([
            'voucher.profile_id' => 'required|integer|exists:master_profiles,id',
            'voucher.amount'     => 'required|numeric|min:1',
            'voucher.account_id' => 'required|integer|exists:accounts,id',
            'voucher.date'       => 'required|date',
            'voucher.note'       => 'nullable|string|max:255',
            'voucher.monthly'    => $type === 'salary_advance' ? 'nullable|numeric|min:0' : 'nullable',
            'voucher.loan_id'    => $type === 'kisti' ? 'required|integer|exists:loans,id' : 'nullable',
        ], [], [
            'voucher.profile_id' => 'party',
            'voucher.amount'     => 'amount',
            'voucher.account_id' => 'account',
            'voucher.loan_id'    => 'loan',
        ]);

        $p = MasterProfile::with('roles')->findOrFail($this->voucher['profile_id']);
        $v = $this->voucher;

        // The picker only lists profiles with the role, but the id comes from the client.
        if (! $p->hasRole(self::VOUCHER_ROLES[$type])) {
            $this->addError('voucher.profile_id', "{$p->display_name} is not a " . self::VOUCHER_ROLES[$type] . '.');

            return;
        }

        if ($type === 'kisti' && ! Loan::whereKey($v['loan_id'])->where('master_profile_id', $p->id)->exists()) {
            $this->addError('voucher.loan_id', 'This loan does not belong to the selected lender.');

            return;
        }
        $amount = round((float) $v['amount'], 2);
        $vouchers = app(ProfileVouchers::class);

        try {
            $message = match ($type) {
                'collect' => $this->done($vouchers->collect($p, $amount, (int) $v['account_id'], $v['date'], $v['note'] ?: null), 'Collected ' . Bdt::money($amount)),
                'pay' => $this->done($vouchers->pay($p, $amount, (int) $v['account_id'], $v['date'], $v['note'] ?: null), 'Paid ' . Bdt::money($amount)),
                'supplier_advance' => $this->done($vouchers->supplierAdvance($p, $amount, (int) $v['account_id'], $v['date'], $v['note'] ?: null), 'Advance ' . Bdt::money($amount) . ' given'),
                'capital' => $this->done($vouchers->addCapital($p, $amount, (int) $v['account_id'], $v['date'], $v['note'] ?: null), 'Capital ' . Bdt::money($amount) . ' added'),
                'drawing' => $this->done($vouchers->drawing($p, $amount, (int) $v['account_id'], $v['date'], $v['note'] ?: null), 'Drawing ' . Bdt::money($amount) . ' recorded'),
                'salary' => $this->done(app(Payroll::class)->pay($p, $amount, (int) $v['account_id'], $v['date']), 'Salary ' . Bdt::money($amount) . ' paid'),
                'salary_advance' => $this->done(app(Payroll::class)->advance($p, $amount, (float) ($v['monthly'] ?: $amount), (int) $v['account_id'], $v['date']), 'Advance ' . Bdt::money($amount) . ' given'),
                'kisti' => $this->kistiPaid(app(LoanKisti::class)->pay(Loan::findOrFail($v['loan_id']), $amount, (int) $v['account_id'], $v['date'])),
            };
        } catch (RuntimeException $e) {
            // Locked period, unbalanced / duplicate entry, missing ledger account.
            $this->addError('voucher.amount', $e->getMessage());

            return;
        }

        $this->voucherType = null;
        ProfileCounts::flush();
        unset($this->voucherProfile);
        $this->dispatch('toast', type: 'success', message: $message);
    }

    protected function done(mixed $result, string $message): string
    {
        return $message;
    }

    protected function kistiPaid(array $result): string
    {
        return 'Kisti paid' . ($result['support'] > 0 ? ' · ' . Bdt::money($result['support']) . ' support carried' : '');
    }

    protected function defaultCashAccount(): ?int
    {
        return app(LedgerAccounts::class)->cashAccounts()[0]['id'] ?? null;
    }

    #[Computed]
    public function cashAccounts(): array
    {
        return app(LedgerAccounts::class)->cashAccounts();
    }

    #[Computed]
    public function voucherProfile(): ?MasterProfile
    {
        $id = $this->voucher['profile_id'] ?? null;

        return $id ? MasterProfile::with('roles')->find($id) : null;
    }

    /** Up to 8 profiles of the voucher's role matching the search box. */
    #[Computed]
    public function voucherMatches(): array
    {
        $role = self::VOUCHER_ROLES[$this->voucherType] ?? null;
        $q = trim($this->voucherSearch);

        if (! $role) {
            return [];
        }

        return MasterProfile::query()
            ->withRole($role)
            ->when($q !== '', function ($query) use ($q) {
                $phone = BdPhone::normalize($q);
                $query->where(fn ($w) => $w->where('display_name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->when($phone, fn ($w) => $w->orWhere('phone', 'like', '%' . ltrim($phone, '0') . '%')));
            })
            ->orderBy('display_name')
            ->limit(8)
            ->get(['id', 'code', 'display_name', 'phone'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->display_name, 'meta' => trim($p->code . ' · ' . ($p->phone ?? ''), ' ·')])
            ->all();
    }

    /** Loans of the picked lender, for the kisti modal. */
    #[Computed]
    public function voucherLoans(): array
    {
        $id = $this->voucher['profile_id'] ?? null;

        return $id && $this->voucherType === 'kisti'
            ? Loan::where('master_profile_id', $id)->where('status', 'active')->orderBy('id')->get(['id', 'name'])->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->all()
            : [];
    }

    /** The open kisti the payment will land on, for the hint under the amount. */
    #[Computed]
    public function voucherKisti(): ?array
    {
        if ($this->voucherType !== 'kisti' || ! ($loan = $this->voucherLoan())) {
            return null;
        }

        $next = $loan->schedules()->where('status', '!=', 'paid')->orderBy('due_date')->first();

        return $next ? [
            'no'      => $next->installment_no,
            'of'      => $loan->installments,
            'date'    => $next->due_date->format('d M Y'),
            'due'     => round($next->payable() - (float) $next->paid_amount, 2),
            'rule'    => $loan->support_rule === 'principal' ? 'principal কমবে' : 'পরের কিস্তি কমবে',
        ] : null;
    }
}
