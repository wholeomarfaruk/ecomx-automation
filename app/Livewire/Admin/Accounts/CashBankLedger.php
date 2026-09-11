<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Statement for a single cash/bank account: every posted journal_entry_line
 * against it, oldest-first, with a running balance computed on the fly.
 * There is no stored "balance" column to display — Account::balance()
 * (and this running total) are always derived from journal_entry_lines, so
 * they can never drift from the ledger (see Account model docblock).
 */
class CashBankLedger extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public int $accountId;

    public function mount(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function render(): mixed
    {
        $account = Account::findOrFail($this->accountId);

        $openingBalance = (float) $account->opening_balance;

        $lines = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', '!=', 'void'))
            ->with('journalEntry')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*')
            ->get();

        $running = $openingBalance;
        foreach ($lines as $line) {
            $running += $account->normal_balance->signedDelta((float) $line->debit, (float) $line->credit);
            $line->running_balance = $running;
        }

        $paginated = $this->paginate($lines->reverse()->values());

        return view('livewire.admin.accounts.cash-bank-ledger', [
            'account'         => $account,
            'lines'           => $paginated,
            'currentBalance'  => $account->balance(),
        ])->layout('layouts.admin.admin');
    }

    /**
     * Runs the running-balance calc over the full, in-memory ordered
     * collection (cheap for a single account's lifetime volume) then
     * paginates the already-annotated rows, newest first, for display.
     */
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
