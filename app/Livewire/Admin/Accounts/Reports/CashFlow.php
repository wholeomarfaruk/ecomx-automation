<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Livewire\Component;

/**
 * Simple direct-method cash flow: net movement across every cash/bank/
 * mobile-banking account within the date range, without the full
 * operating/investing/financing breakdown — a business-facing "did cash go
 * up or down and by how much" view matching the spec's simplicity goal.
 */
class CashFlow extends Component
{
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function render(): mixed
    {
        $cashAccounts = Account::whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->get();

        $rows = $cashAccounts->map(function (Account $account) {
            $totals = JournalEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('journalEntry', fn ($q) => $q
                    ->where('status', '!=', 'void')
                    ->whereBetween('entry_date', [$this->startDate, $this->endDate]))
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();

            $netChange = $account->normal_balance->signedDelta((float) $totals->total_debit, (float) $totals->total_credit);

            return ['account' => $account, 'netChange' => $netChange, 'endingBalance' => $account->balance()];
        });

        return view('livewire.admin.accounts.reports.cash-flow', [
            'rows'          => $rows,
            'totalNetChange'=> $rows->sum('netChange'),
        ])->layout('layouts.admin.admin');
    }
}
