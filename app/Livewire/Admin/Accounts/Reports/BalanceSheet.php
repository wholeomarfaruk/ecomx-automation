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
        // Each row shows balance() (a positive figure in the account's own
        // terms — e.g. Accumulated Depreciation grows as a positive number
        // as more depreciation is booked), but the group totals need
        // signedForTypeTotal() so a contra account (contra_asset,
        // contra_equity, contra_income) correctly subtracts from its type's
        // total instead of adding to it — see Account::signedForTypeTotal().
        $assets = Account::where('type', 'asset')->get()->map(fn ($a) => ['account' => $a, 'amount' => $a->balance()]);
        $liabilities = Account::where('type', 'liability')->get()->map(fn ($a) => ['account' => $a, 'amount' => $a->balance()]);
        $equity = Account::where('type', 'equity')->get()->map(fn ($a) => ['account' => $a, 'amount' => $a->balance()]);

        $totalAssets = Account::where('type', 'asset')->get()->sum(fn ($a) => $a->signedForTypeTotal());
        $totalLiabilities = $liabilities->sum('amount');

        $incomeTotal = Account::where('type', 'income')->get()->sum(fn ($a) => $a->signedForTypeTotal());
        $expenseTotal = Account::where('type', 'expense')->get()->sum(fn ($a) => $a->signedForTypeTotal());
        $currentPeriodProfit = $incomeTotal - $expenseTotal;

        $totalEquity = Account::where('type', 'equity')->get()->sum(fn ($a) => $a->signedForTypeTotal()) + $currentPeriodProfit;

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
