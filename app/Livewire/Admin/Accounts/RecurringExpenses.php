<?php

namespace App\Livewire\Admin\Accounts;

use App\Actions\Accounts\PostRecurringExpenseRun;
use App\Models\Account;
use App\Models\RecurringExpense;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Case 6.2 — recurring expense templates. A "Run Now" button lets an admin
 * manually trigger an overdue recurrence instead of waiting for the
 * scheduler; PostRecurringExpenseRun's idempotency guards against
 * double-posting either way.
 */
class RecurringExpenses extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public bool $createModal = false;
    public string $newName = '';
    public ?int $newAccountId = null;
    public ?int $newFromAccountId = null;
    public string $newAmount = '';
    public string $newCadence = 'monthly';
    public string $newNextRunDate = '';

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newAccountId', 'newFromAccountId', 'newAmount']);
        $this->newCadence = 'monthly';
        $this->newNextRunDate = now()->toDateString();
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createRecurring(): void
    {
        $this->validate([
            'newName'          => 'required|string|max:150',
            'newAccountId'     => 'required|exists:accounts,id',
            'newFromAccountId' => 'required|different:newAccountId|exists:accounts,id',
            'newAmount'        => 'required|numeric|gt:0',
            'newCadence'       => 'required|in:weekly,monthly,yearly',
            'newNextRunDate'   => 'required|date',
        ]);

        RecurringExpense::create([
            'name'            => $this->newName,
            'account_id'      => $this->newAccountId,
            'from_account_id' => $this->newFromAccountId,
            'amount'          => $this->newAmount,
            'cadence'         => $this->newCadence,
            'next_run_date'   => $this->newNextRunDate,
            'is_active'       => true,
        ]);

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Recurring expense created']);
    }

    public function runNow(int $id): void
    {
        $recurring = RecurringExpense::findOrFail($id);

        app(PostRecurringExpenseRun::class)->handle($recurring);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Posted']);
    }

    public function toggleActive(int $id): void
    {
        $recurring = RecurringExpense::findOrFail($id);
        $recurring->update(['is_active' => ! $recurring->is_active]);
    }

    public function render(): mixed
    {
        return view('livewire.admin.accounts.recurring-expenses', [
            'recurrences'      => RecurringExpense::with('account', 'fromAccount')->latest()->paginate(15),
            'expenseAccounts'  => Account::active()->where('type', 'expense')->orderBy('code')->get(),
            'paidFromAccounts' => Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(),
        ])->layout('layouts.admin.admin');
    }
}
