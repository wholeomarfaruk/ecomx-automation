<?php

namespace App\Livewire\Admin\Accounts\Settings;

use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Accounts\ReverseJournalEntry;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Exceptions\Accounts\UnbalancedJournalEntryException;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * "Advanced Accounting > Journal Entries" — free-form manual entry with
 * any number of Dr/Cr lines, for cases the guided Transactions screens
 * don't cover. Still funnels through PostJournalEntry, so the balance
 * invariant and idempotency guard apply exactly the same as every other
 * Action.
 */
class JournalEntries extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public bool $createModal = false;
    public string $entryDate = '';
    public string $description = '';
    /** @var array<int, array{account_id: string, debit: string, credit: string}> */
    public array $lines = [];

    public function openCreateModal(): void
    {
        $this->entryDate = now()->toDateString();
        $this->description = '';
        $this->lines = [
            ['account_id' => '', 'debit' => '', 'credit' => ''],
            ['account_id' => '', 'debit' => '', 'credit' => ''],
        ];
        $this->resetValidation();
        $this->createModal = true;
    }

    public function addLine(): void
    {
        $this->lines[] = ['account_id' => '', 'debit' => '', 'credit' => ''];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(): void
    {
        $this->validate([
            'entryDate'            => 'required|date',
            'lines'                => 'required|array|min:2',
            'lines.*.account_id'   => 'required|exists:accounts,id',
        ]);

        $lines = collect($this->lines)->map(fn ($line) => [
            'account_id' => (int) $line['account_id'],
            'debit'      => (float) ($line['debit'] ?: 0),
            'credit'     => (float) ($line['credit'] ?: 0),
        ])->all();

        try {
            app(PostJournalEntry::class)->handle([
                'entry_date'       => $this->entryDate,
                'description'      => $this->description ?: null,
                'transaction_type' => \App\Enums\Accounts\TransactionType::MANUAL_EXPENSE->value,
            ], $lines);
        } catch (UnbalancedJournalEntryException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Journal entry posted']);
    }

    public function reverse(int $id): void
    {
        $entry = JournalEntry::findOrFail($id);
        app(ReverseJournalEntry::class)->handle($entry);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Entry reversed']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.accounts.settings.journal-entries', [
            'entries'  => JournalEntry::with('lines.account')->latest('entry_date')->paginate(20),
            'accounts' => Account::active()->orderBy('code')->get(),
        ])->layout('layouts.admin.admin');
    }
}
