<?php

namespace App\Livewire\Admin\Accounts\Settings;

use App\Actions\Accounts\CreateFiscalYear;
use App\Actions\Accounts\LockFiscalPeriod;
use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use Livewire\Component;

/**
 * "Advanced Accounting > Accounting Periods" — lock/reopen individual
 * months (Golden Rule #7). Locking is narrowly scoped (see
 * LockFiscalPeriod) and activity-logged so a reopen is always auditable.
 */
class FiscalPeriods extends Component
{
    public bool $createYearModal = false;
    public string $newYearName = '';
    public string $newYearStartDate = '';

    public function openCreateYearModal(): void
    {
        $this->newYearName = '';
        $this->newYearStartDate = now()->startOfYear()->toDateString();
        $this->resetValidation();
        $this->createYearModal = true;
    }

    public function createYear(): void
    {
        $this->validate([
            'newYearName'      => 'required|string|max:50',
            'newYearStartDate' => 'required|date',
        ]);

        app(CreateFiscalYear::class)->handle($this->newYearName, $this->newYearStartDate);

        $this->createYearModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Fiscal year created with 12 monthly periods']);
    }

    public function toggleLock(int $periodId): void
    {
        $period = FiscalPeriod::findOrFail($periodId);

        if ($period->is_locked) {
            app(LockFiscalPeriod::class)->reopen($period);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Period reopened']);
        } else {
            app(LockFiscalPeriod::class)->lock($period);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Period locked']);
        }
    }

    public function render(): mixed
    {
        return view('livewire.admin.accounts.settings.fiscal-periods', [
            'fiscalYears' => FiscalYear::with('periods')->latest('start_date')->get(),
        ])->layout('layouts.admin.admin');
    }
}
