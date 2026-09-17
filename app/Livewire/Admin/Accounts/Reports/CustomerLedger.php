<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Enums\Accounts\NormalBalance;
use App\Models\Customer;
use App\Models\JournalEntryLine;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Full statement for one customer: every posted journal_entry_line whose
 * subledger points at this Customer — regardless of which account it hit
 * (Accounts Receivable 1100, Customer Credit 2150, Customer Advance 2160,
 * ...) — oldest-first with a running balance, mirroring CashBankLedger's
 * shape but scoped by subledger instead of by a single account. The running
 * balance is kept in "receivable" terms (debit-normal): positive means the
 * customer still owes money, negative means the business is holding a
 * credit/advance for them, so every line — no matter which underlying
 * account it posted to — contributes on the same footing.
 */
class CustomerLedger extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public int $customerId;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function render(): mixed
    {
        $customer = Customer::findOrFail($this->customerId);

        $lines = JournalEntryLine::query()
            ->where('subledger_type', Customer::class)
            ->where('subledger_id', $customer->id)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', '!=', 'void'))
            ->with(['journalEntry', 'account'])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*')
            ->get();

        $running = 0.0;
        foreach ($lines as $line) {
            $running += NormalBalance::DEBIT->signedDelta((float) $line->debit, (float) $line->credit);
            $line->running_balance = $running;
        }

        $paginated = $this->paginate($lines->reverse()->values());

        return view('livewire.admin.accounts.reports.customer-ledger', [
            'customer'       => $customer,
            'lines'          => $paginated,
            'currentBalance' => $running,
        ])->layout('layouts.admin.admin');
    }

    protected function paginate($collection)
    {
        $page = $this->getPage();
        $perPage = 25;

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $collection->forPage($page, $perPage),
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }
}
