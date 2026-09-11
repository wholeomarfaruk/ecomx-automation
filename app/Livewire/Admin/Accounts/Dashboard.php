<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\Account;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsSupplierBill;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LoanRepaymentSchedule;
use App\Models\RecurringExpense;
use Livewire\Component;

/**
 * "হোম" — the Accounts landing page from docs/ecomX-accounts-cases.md:
 * today's summary (in/out/cash-on-hand/profit), receivable/payable quick
 * totals, inventory value, today's transactions, and alerts (overdue,
 * recurring due). Every figure here is derived from journal_entry_lines /
 * open-item tables at render time — nothing is cached or denormalized, so
 * the dashboard can never drift from the ledger.
 */
class Dashboard extends Component
{
    protected function todayRange(): array
    {
        return [now()->startOfDay()->toDateString(), now()->endOfDay()->toDateString()];
    }

    protected function todayInOut(): array
    {
        [$start, $end] = $this->todayRange();

        $lines = JournalEntryLine::query()
            ->whereHas('journalEntry', fn ($q) => $q
                ->where('status', '!=', 'void')
                ->whereBetween('entry_date', [$start, $end]))
            ->whereHas('account', fn ($q) => $q->whereIn('subtype', ['cash', 'bank', 'mobile_banking']))
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return [
            'in'  => (float) $lines->total_debit,
            'out' => (float) $lines->total_credit,
        ];
    }

    protected function cashOnHand(): float
    {
        return Account::whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->get()->sum(fn ($a) => $a->balance());
    }

    protected function currentProfit(): float
    {
        $income = Account::where('type', 'income')->get()->sum(fn ($a) => $a->balance());
        $expense = Account::where('type', 'expense')->get()->sum(fn ($a) => $a->balance());

        return $income - $expense;
    }

    protected function stockValue(): float
    {
        return (float) InventoryStock::query()
            ->join('product_variants', 'product_variants.id', '=', 'inventory_stocks.variant_id')
            ->selectRaw('COALESCE(SUM(inventory_stocks.quantity * product_variants.purchase_price), 0) as total')
            ->value('total');
    }

    public function render(): mixed
    {
        $inOut = $this->todayInOut();
        [$start, $end] = $this->todayRange();

        $todaysTransactions = JournalEntry::query()
            ->where('status', '!=', 'void')
            ->whereBetween('entry_date', [$start, $end])
            ->with('lines.account')
            ->latest('id')
            ->limit(10)
            ->get();

        $receivableTotal = AccountsCustomerInvoice::whereIn('status', ['open', 'partial'])->get()->sum(fn ($i) => $i->amountDue());
        $payableTotal = AccountsSupplierBill::whereIn('status', ['open', 'partial'])->get()->sum(fn ($b) => $b->amountDue());

        // No due-date column exists yet on accounts_customer_invoices (Case 3.5
        // references "due date" without the schema for it being introduced in
        // any milestone so far) — 7+ days since the invoice was recorded is
        // used as a stand-in "overdue" signal until a real terms/due_date
        // field is added.
        $overdueReceivables = AccountsCustomerInvoice::whereIn('status', ['open', 'partial'])
            ->where('created_at', '<', now()->subDays(7))
            ->with('customer')
            ->limit(5)
            ->get();

        $overdueLoanSchedules = LoanRepaymentSchedule::with('loan')
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->limit(5)
            ->get();

        $dueRecurringExpenses = RecurringExpense::due()->limit(5)->get();

        return view('livewire.admin.accounts.dashboard', [
            'todayIn'                => $inOut['in'],
            'todayOut'               => $inOut['out'],
            'cashOnHand'             => $this->cashOnHand(),
            'currentProfit'          => $this->currentProfit(),
            'stockValue'             => $this->stockValue(),
            'receivableTotal'        => $receivableTotal,
            'payableTotal'           => $payableTotal,
            'todaysTransactions'     => $todaysTransactions,
            'overdueReceivables'     => $overdueReceivables,
            'overdueLoanSchedules'   => $overdueLoanSchedules,
            'dueRecurringExpenses'   => $dueRecurringExpenses,
        ])->layout('layouts.admin.admin');
    }
}
