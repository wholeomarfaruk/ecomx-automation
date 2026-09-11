<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Models\Account;
use Livewire\Component;

/**
 * Assets = Liabilities + Equity, as of a point in time (Account::balance()
 * is always cumulative from inception, so "as of" is just "now" — a future
 * as-of-date filter would need a date param threaded into balance()).
 * Retained Earnings for the current period is approximated as the running
 * P&L total (income - expense accounts) since no period-close/rollover
 * step exists yet — see plan risk on fiscal year closing.
 */
class BalanceSheet extends Component
{
    public function render(): mixed
    {
        $assets = Account::where('type', 'asset')->get()->map(fn ($a) => ['account' => $a, 'amount' => $a->balance()]);
        $liabilities = Account::where('type', 'liability')->get()->map(fn ($a) => ['account' => $a, 'amount' => $a->balance()]);
        $equity = Account::where('type', 'equity')->get()->map(fn ($a) => ['account' => $a, 'amount' => $a->balance()]);

        $totalAssets = $assets->sum('amount');
        $totalLiabilities = $liabilities->sum('amount');

        $incomeTotal = Account::where('type', 'income')->get()->sum(fn ($a) => $a->balance());
        $expenseTotal = Account::where('type', 'expense')->get()->sum(fn ($a) => $a->balance());
        $currentPeriodProfit = $incomeTotal - $expenseTotal;

        $totalEquity = $equity->sum('amount') + $currentPeriodProfit;

        return view('livewire.admin.accounts.reports.balance-sheet', [
            'assets'               => $assets,
            'liabilities'          => $liabilities,
            'equity'               => $equity,
            'currentPeriodProfit'  => $currentPeriodProfit,
            'totalAssets'          => $totalAssets,
            'totalLiabilities'     => $totalLiabilities,
            'totalEquity'          => $totalEquity,
            'balances'             => abs($totalAssets - ($totalLiabilities + $totalEquity)) <= 0.01,
        ])->layout('layouts.admin.admin');
    }
}
