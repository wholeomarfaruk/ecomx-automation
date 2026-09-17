<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Enums\Accounts\NormalBalance;
use App\Models\Customer;
use App\Models\JournalEntryLine;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Browsable list of every customer with any ledger activity (a
 * subledger-tagged journal_entry_line against them) — each row's current
 * balance, click through to CustomerLedger for the full statement. Entry
 * point for "see all customer ledgers" rather than searching for one by name.
 */
class CustomerLedgers extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $customerIds = JournalEntryLine::query()
            ->where('subledger_type', Customer::class)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', '!=', 'void'))
            ->distinct()
            ->pluck('subledger_id');

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->when($this->search, fn ($q) => $q
                ->where('full_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy('full_name')
            ->paginate(20);

        foreach ($customers as $customer) {
            $totals = JournalEntryLine::query()
                ->where('subledger_type', Customer::class)
                ->where('subledger_id', $customer->id)
                ->whereHas('journalEntry', fn ($q) => $q->where('status', '!=', 'void'))
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();

            $customer->ledgerBalance = NormalBalance::DEBIT->signedDelta(
                (float) $totals->total_debit,
                (float) $totals->total_credit,
            );
        }

        return view('livewire.admin.accounts.reports.customer-ledgers', [
            'customers' => $customers,
        ])->layout('layouts.admin.admin');
    }
}
