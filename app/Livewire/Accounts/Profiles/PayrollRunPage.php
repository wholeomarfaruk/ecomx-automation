<?php

namespace App\Livewire\Accounts\Profiles;

use App\Livewire\Accounts\Profiles\Concerns\HandlesVoucherModals;
use App\Models\PayrollRun;
use App\Support\Bdt;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Accounts Panel › Employees › one month's payroll run (spec §9, not designed). */
#[Title('Payroll')]
#[Layout('layouts.accounts.profiles')]
class PayrollRunPage extends Component
{
    use HandlesVoucherModals;

    public string $month;

    public function mount(?string $month = null): void
    {
        $this->month = $month ?: now()->format('Y-m');
    }

    #[Computed]
    public function run(): ?PayrollRun
    {
        return PayrollRun::with(['items.profile.roles'])
            ->whereDate('month', Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->toDateString())
            ->first();
    }

    public function render()
    {
        return view('livewire.accounts.profiles.payroll-run', [
            'money' => fn ($v) => Bdt::money($v),
            'label' => Carbon::createFromFormat('Y-m', $this->month)->format('F Y'),
        ])->layoutData(['active' => 'employees']);
    }
}
