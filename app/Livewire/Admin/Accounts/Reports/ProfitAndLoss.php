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

    /**
     * accountTotal() as it should count toward the income/expense group
     * total — a contra-income account like Sales Return deliberately has
     * normal_balance flipped relative to "income", so its own positive
     * accountTotal() needs negating here or it reads as extra revenue
     * instead of a reduction. Mirrors Account::signedForTypeTotal(), just
     * over this report's own date-ranged total instead of balance().
     */
    protected function signedAccountTotal(Account $account): float
    {
        $isContra = str_starts_with((string) $account->subtype, 'contra_');
        $total = $this->accountTotal($account);

        return $isContra ? -$total : $total;
    }

    public function render(): mixed
    {
        $incomeAccounts = Account::where('type', 'income')->get();
        $expenseAccounts = Account::where('type', 'expense')->get();

        $income = $incomeAccounts->map(fn ($a) => ['account' => $a, 'amount' => $this->accountTotal($a)]);
        $expenses = $expenseAccounts->map(fn ($a) => ['account' => $a, 'amount' => $this->accountTotal($a)]);

        $totalIncome = $incomeAccounts->sum(fn ($a) => $this->signedAccountTotal($a));
        $totalExpenses = $expenseAccounts->sum(fn ($a) => $this->signedAccountTotal($a));

        return view('livewire.admin.accounts.reports.profit-and-loss', [
            'income'         => $income,
            'expenses'       => $expenses,
            'totalIncome'    => $totalIncome,
            'totalExpenses'  => $totalExpenses,
            'netProfit'      => $totalIncome - $totalExpenses,
        ])->layout('layouts.admin.admin');
    }
}
