<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\Account;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsSupplierAdvance;
use App\Models\AccountsSupplierBill;
use App\Models\InventoryBatch;
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
        // signedForTypeTotal() (not balance()) so a contra-income account
        // like Sales Return correctly reduces income instead of adding to
        // it — see Account::signedForTypeTotal() for why balance() alone
        // gets this backwards.
        $income = Account::where('type', 'income')->get()->sum(fn ($a) => $a->signedForTypeTotal());
        $expense = Account::where('type', 'expense')->get()->sum(fn ($a) => $a->signedForTypeTotal());

        return $income - $expense;
    }

    /**
     * Total purchase-cost value of everything in stock, priced lot-by-lot
     * from inventory_batches (quantity × that batch's own purchase_price) —
     * the same product bought at ৳1000 one time and ৳1500 another must value
     * each remaining unit at what was actually paid for it, not one
     * overwritten "latest price" field on the product/variant. Any stock
     * quantity not covered by an active batch (e.g. an opening balance
     * entered before batch tracking, or without a batch at all) falls back
     * to the product/variant's own stored purchase_price for just that
     * uncovered remainder, so total valued quantity never exceeds what's
     * actually on hand per inventory_stocks.
     */
    protected function stockValue(): float
    {
        $batchValue = (float) InventoryBatch::query()
            ->active()
            ->selectRaw('COALESCE(SUM(quantity * purchase_price), 0) as total')
            ->value('total');

        $batchedQtyByKey = InventoryBatch::query()
            ->active()
            ->selectRaw('warehouse_id, product_id, variant_id, SUM(quantity) as qty')
            ->groupBy('warehouse_id', 'product_id', 'variant_id')
            ->get()
            ->keyBy(fn ($row) => "{$row->warehouse_id}:{$row->product_id}:{$row->variant_id}");

        $fallbackValue = 0.0;

        InventoryStock::query()
            ->with(['variant:id,purchase_price', 'product:id,purchase_price'])
            ->where('quantity', '>', 0)
            ->get(['id', 'warehouse_id', 'product_id', 'variant_id', 'quantity'])
            ->each(function (InventoryStock $stock) use ($batchedQtyByKey, &$fallbackValue) {
                $key = "{$stock->warehouse_id}:{$stock->product_id}:{$stock->variant_id}";
                $batchedQty = (float) ($batchedQtyByKey->get($key)->qty ?? 0);
                $uncovered = max(0, (float) $stock->quantity - $batchedQty);

                if ($uncovered <= 0) {
                    return;
                }

                $price = (float) ($stock->variant?->purchase_price ?? $stock->product?->purchase_price ?? 0);
                $fallbackValue += $uncovered * $price;
            });

        return $batchValue + $fallbackValue;
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

        $openAdvances = AccountsSupplierAdvance::whereIn('status', ['open', 'partial'])->with('supplier')->get();
        $supplierAdvanceTotal = $openAdvances->sum(fn ($a) => $a->amountRemaining());

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
            'supplierAdvanceTotal'   => $supplierAdvanceTotal,
            'openAdvances'           => $openAdvances->sortByDesc(fn ($a) => $a->amountRemaining())->take(5),
            'todaysTransactions'     => $todaysTransactions,
            'overdueReceivables'     => $overdueReceivables,
            'overdueLoanSchedules'   => $overdueLoanSchedules,
            'dueRecurringExpenses'   => $dueRecurringExpenses,
        ])->layout('layouts.admin.admin');
    }
}
