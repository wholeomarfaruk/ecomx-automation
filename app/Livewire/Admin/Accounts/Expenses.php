<?php

namespace App\Livewire\Admin\Accounts;

use App\Actions\Accounts\PostManualTransaction;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\JournalEntry;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Case 6.1 — general expenses list + quick-create. Recurring templates
 * (Case 6.2) live on their own screen (RecurringExpenses) since they're a
 * template, not a transaction. Fee tracking (Case 6.3) is achieved simply
 * by filtering Transactions by any account with subtype=fee — no separate
 * screen needed since every fee already posts through PostTransferWithFee/
 * PostCurrencyConversion/settlement Actions into a normal expense account.
 */
class Expenses extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public bool $createModal = false;
    public string $newDate = '';
    public ?int $newExpenseAccountId = null;
    public ?int $newPaidFromAccountId = null;
    public string $newAmount = '';
    public string $newDescription = '';

    public function openCreateModal(): void
    {
        $this->reset(['newExpenseAccountId', 'newPaidFromAccountId', 'newAmount', 'newDescription']);
        $this->newDate = now()->toDateString();
        $this->resetValidation();
        $this->createModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'newDate'              => 'required|date',
            'newExpenseAccountId'  => 'required|exists:accounts,id',
            'newPaidFromAccountId' => 'required|different:newExpenseAccountId|exists:accounts,id',
            'newAmount'            => 'required|numeric|gt:0',
        ]);

        app(PostManualTransaction::class)->handle(
            (int) $this->newExpenseAccountId,
            (int) $this->newPaidFromAccountId,
            (float) $this->newAmount,
            $this->newDate,
            TransactionType::MANUAL_EXPENSE,
            $this->newDescription ?: null,
        );

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Expense recorded']);
    }

    public function render(): mixed
    {
        $expenseAccountIds = Account::where('type', 'expense')->pluck('id');

        $entries = JournalEntry::query()
            ->whereHas('lines', fn ($q) => $q->whereIn('account_id', $expenseAccountIds)->where('debit', '>', 0))
            ->when($this->search, fn ($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->with('lines.account')
            ->orderByDesc('entry_date')
            ->paginate(20);

        return view('livewire.admin.accounts.expenses', [
            'entries'         => $entries,
            'expenseAccounts' => Account::active()->where('type', 'expense')->orderBy('code')->get(),
            'paidFromAccounts'=> Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(),
        ])->layout('layouts.admin.admin');
    }
}
