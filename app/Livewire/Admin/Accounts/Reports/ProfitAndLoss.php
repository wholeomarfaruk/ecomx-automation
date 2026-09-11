<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Livewire\Component;

/**
 * Income minus expenses over a date range, excluding owner investment/
 * withdrawal (Golden Rule #5 — those never touch P&L since they're posted
 * to Equity accounts, not Income/Expense, so they're naturally excluded
 * just by summing accounts of type=income/expense).
 */
class ProfitAndLoss extends Component
{
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    protected function accountTotal(Account $account): float
    {
        $totals = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', fn ($q) => $q
                ->where('status', '!=', 'void')
                ->whereBetween('entry_date', [$this->startDate, $this->endDate]))
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return $account->normal_balance->signedDelta((float) $totals->total_debit, (float) $totals->total_credit);
    }

    public function render(): mixed
    {
        $incomeAccounts = Account::where('type', 'income')->get();
        $expenseAccounts = Account::where('type', 'expense')->get();

        $income = $incomeAccounts->map(fn ($a) => ['account' => $a, 'amount' => $this->accountTotal($a)]);
        $expenses = $expenseAccounts->map(fn ($a) => ['account' => $a, 'amount' => $this->accountTotal($a)]);

        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');

        return view('livewire.admin.accounts.reports.profit-and-loss', [
            'income'         => $income,
            'expenses'       => $expenses,
            'totalIncome'    => $totalIncome,
            'totalExpenses'  => $totalExpenses,
            'netProfit'      => $totalIncome - $totalExpenses,
        ])->layout('layouts.admin.admin');
    }
}
