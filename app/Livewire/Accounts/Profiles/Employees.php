<?php

namespace App\Livewire\Accounts\Profiles;

use App\Actions\Profiles\Payroll;
use App\Exceptions\Accounts\LockedFiscalPeriodException;
use App\Livewire\Accounts\Profiles\Concerns\HandlesVoucherModals;
use App\Models\MasterProfile;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use App\Support\Bdt;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Accounts Panel › Employees — design: Employees.dc.html.
 * Salary settings live on the employee role; advances in
 * employee_advances; payroll runs post the month's salary to Salary
 * Payable, and paying clears it.
 */
#[Title('Employees')]
#[Layout('layouts.accounts.app')]
class Employees extends Component
{
    use HandlesVoucherModals;

    public const CHIPS = ['all' => 'All', 'partial' => 'Salary বাকি', 'adv' => 'Advance আছে', 'login' => 'Login access', 'inactive' => 'Inactive'];

    public const DEPARTMENTS = ['Accounts', 'Sales', 'Inventory', 'Production', 'Logistics', 'Admin'];

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $department = '';

    #[Url(except: 'all')]
    public string $st = 'all';

    public bool $allHistory = false;

    public bool $showPrepare = false;

    public array $prepare = [];

    public function setSt(string $st): void
    {
        $this->st = array_key_exists($st, self::CHIPS) ? $st : 'all';
    }

    protected function lastMonth(): CarbonInterface
    {
        return now()->subMonthNoOverflow()->startOfMonth();
    }

    /** Every employee (active and inactive), shaped for the table. */
    #[Computed]
    public function all(): Collection
    {
        $profiles = MasterProfile::withRole('employee')
            ->with(['roles', 'advances' => fn ($q) => $q->where('balance', '>', 0)])
            ->orderBy('display_name')->get();
        $ids = $profiles->pluck('id')->all();

        $users = User::whereIn('master_profile_id', $ids)->with('roles:id,name')->get()->keyBy('master_profile_id');

        $lastRun = PayrollRun::whereDate('month', $this->lastMonth()->toDateString())->first();
        $lastItems = $lastRun ? $lastRun->items()->get()->keyBy('master_profile_id') : collect();
        $unpaid = PayrollItem::whereIn('master_profile_id', $ids)->where('status', '!=', 'paid')->where('include', true)
            ->whereHas('run', fn ($q) => $q->where('status', '!=', 'draft'))->get()->groupBy('master_profile_id');

        return $profiles->map(function (MasterProfile $p) use ($users, $lastItems, $unpaid) {
            $set = $p->roleSettings('employee');
            $advance = round((float) $p->advances->sum('balance'), 2);
            $monthly = round((float) $p->advances->sum('monthly_deduction'), 2);
            $item = $lastItems->get($p->id);
            $user = $users->get($p->id);
            $owed = round((float) ($unpaid->get($p->id)?->sum(fn ($i) => $i->unpaid()) ?? 0), 2);
            $joined = ! empty($set['joined_on']) ? Carbon::parse($set['joined_on']) : null;

            return [
                'profile'     => $p,
                'active'      => $p->status !== 'inactive',
                'designation' => $set['designation'] ?? '—',
                'department'  => $set['department'] ?? '',
                'joined'      => $joined?->format('M Y') ?? '—',
                'salary'      => (float) ($set['salary'] ?? 0),
                'advance'     => $advance,
                'advNote'     => $advance > 0 && $monthly > 0 ? Bdt::money($monthly) . ' / month' : '',
                'status'      => $item ? ($item->unpaid() > 0.5 ? Bdt::money($item->unpaid()) . ' বাকি' : 'Paid') : '—',
                'statusPaid'  => $item ? $item->unpaid() <= 0.5 : null,
                'owed'        => $owed,
                'via'         => ['cash' => 'Cash', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank'][$set['pay_via'] ?? ''] ?? '—',
                'login'       => $user ? ($user->roles->first()?->name ? ucfirst($user->roles->first()->name) : 'Login') : null,
            ];
        });
    }

    #[Computed]
    public function rows(): Collection
    {
        $q = mb_strtolower(trim($this->q));

        return $this->all->filter(function ($r) use ($q) {
            $ok = match ($this->st) {
                'partial'  => $r['active'] && $r['owed'] > 0.5,
                'adv'      => $r['active'] && $r['advance'] > 0.5,
                'login'    => $r['active'] && $r['login'],
                'inactive' => ! $r['active'],
                default    => $r['active'],
            };

            return $ok
                && ($this->department === '' || $r['department'] === $this->department)
                && ($q === '' || str_contains(mb_strtolower($r['profile']->display_name . ' ' . $r['profile']->phone . ' ' . $r['designation']), $q));
        })->values();
    }

    #[Computed]
    public function summary(): array
    {
        $active = $this->all->where('active', true);
        $unpaidRuns = PayrollRun::whereNotIn('status', ['paid', 'draft'])->orderBy('month')->get();
        $oldest = $unpaidRuns->first();
        $owing = $this->all->where('owed', '>', 0.5);

        $month = now()->startOfMonth();
        $run = PayrollRun::whereDate('month', $month->toDateString())->first();

        if ($run) {
            $banner = ['gross' => (float) $run->gross + (float) $run->bonus, 'deduction' => (float) $run->advance_deducted + (float) $run->other_deducted, 'net' => (float) $run->net, 'due' => $run->due_on, 'prepared' => true, 'draft' => $run->isDraft()];
        } else {
            $preview = app(Payroll::class)->preview();
            $banner = ['gross' => $preview->sum('salary'), 'deduction' => $preview->sum('deduction'), 'net' => $preview->sum('net'), 'due' => $month->copy()->addMonth()->day(5), 'prepared' => false, 'draft' => false];
        }

        return [
            'headcount'  => $active->count(),
            'logins'     => $active->filter(fn ($r) => $r['login'])->count(),
            'payroll'    => $active->sum('salary'),
            'month'      => $month,
            'payable'    => $owing->sum('owed'),
            'payableSub' => $oldest ? $oldest->month->format('F') . ' · ' . $owing->count() . ' employees' : 'সব বেতন পরিশোধিত',
            'advance'    => $active->sum('advance'),
            'banner'     => $banner,
            'chips'      => collect(self::CHIPS)->keys()->mapWithKeys(fn ($k) => [$k => match ($k) {
                'partial'  => $active->where('owed', '>', 0.5)->count(),
                'adv'      => $active->where('advance', '>', 0.5)->count(),
                'login'    => $active->filter(fn ($r) => $r['login'])->count(),
                'inactive' => $this->all->where('active', false)->count(),
                default    => $active->count(),
            }])->all(),
        ];
    }

    #[Computed]
    public function history(): Collection
    {
        return PayrollRun::orderByDesc('month')->limit($this->allHistory ? 36 : 4)->get();
    }

    /* ---------------- Prepare payroll ---------------- */

    public function openPrepare(): void
    {
        $month = now()->startOfMonth();

        if (PayrollRun::whereDate('month', $month->toDateString())->exists()) {
            $this->redirectRoute('accounts.profiles.payroll', ['month' => $month->format('Y-m')], navigate: true);

            return;
        }

        $this->resetErrorBag();
        $this->prepare = [
            'month'      => $month->format('Y-m'),
            'bonus'      => null,
            'bonus_note' => '',
            'due_on'     => $month->copy()->addMonth()->day(5)->toDateString(),
        ];
        $this->showPrepare = true;
    }

    public function preparePayroll(): void
    {
        $this->validate([
            'prepare.month'      => 'required|date_format:Y-m',
            'prepare.bonus'      => 'nullable|numeric|min:0|max:500',
            'prepare.bonus_note' => 'nullable|string|max:60',
            'prepare.due_on'     => 'required|date',
        ], [], ['prepare.bonus' => 'bonus %']);

        $month = Carbon::createFromFormat('Y-m', $this->prepare['month'])->startOfMonth();

        try {
            // A draft salary sheet — edited per employee, posted when approved.
            app(Payroll::class)->createDraft($month, $this->prepare['due_on'], (float) ($this->prepare['bonus'] ?: 0), $this->prepare['bonus_note'] ?: null);
        } catch (LockedFiscalPeriodException|\RuntimeException $e) {
            $this->addError('prepare.month', $e->getMessage());

            return;
        }

        $this->redirectRoute('accounts.profiles.payroll', ['month' => $month->format('Y-m')], navigate: true);
    }

    public function render()
    {
        return view('livewire.accounts.profiles.employees', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'employees']);
    }
}
