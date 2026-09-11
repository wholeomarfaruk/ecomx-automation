<?php

namespace App\Livewire\Admin\Accounts;

use App\Actions\Accounts\PostLoanReceived;
use App\Actions\Accounts\PostLoanRepayment;
use App\Models\Account;
use App\Models\Loan;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cases 5.1 (loan received) and 5.2 (repayment, principal/interest split).
 */
class Loans extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public bool $createModal = false;
    public string $newName = '';
    public string $newLender = '';
    public string $newPrincipal = '';
    public string $newInterestRate = '';
    public string $newStartDate = '';
    public string $newTermMonths = '';
    public ?int $newCashAccountId = null;

    public ?int $repayLoanId = null;
    public string $repayPrincipal = '';
    public string $repayInterest = '';
    public string $repayDate = '';
    public ?int $repayCashAccountId = null;

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newLender', 'newPrincipal', 'newInterestRate', 'newTermMonths', 'newCashAccountId']);
        $this->newStartDate = now()->toDateString();
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createLoan(): void
    {
        $this->validate([
            'newName'          => 'required|string|max:150',
            'newPrincipal'     => 'required|numeric|gt:0',
            'newStartDate'     => 'required|date',
            'newCashAccountId' => 'required|exists:accounts,id',
        ]);

        app(PostLoanReceived::class)->handle(
            $this->newName,
            $this->newLender ?: null,
            (float) $this->newPrincipal,
            $this->newStartDate,
            (int) $this->newCashAccountId,
            Account::where('code', '2200')->value('id'),
            $this->newInterestRate !== '' ? (float) $this->newInterestRate : null,
            $this->newTermMonths !== '' ? (int) $this->newTermMonths : null,
        );

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Loan recorded']);
    }

    public function openRepayModal(int $loanId): void
    {
        $this->repayLoanId = $loanId;
        $this->repayPrincipal = '';
        $this->repayInterest = '0';
        $this->repayDate = now()->toDateString();
        $this->resetValidation();
    }

    public function repay(): void
    {
        $this->validate([
            'repayPrincipal'     => 'required|numeric|gt:0',
            'repayInterest'      => 'nullable|numeric|gte:0',
            'repayDate'          => 'required|date',
            'repayCashAccountId' => 'required|exists:accounts,id',
        ]);

        $loan = Loan::findOrFail($this->repayLoanId);

        app(PostLoanRepayment::class)->handle(
            $loan,
            (int) $this->repayCashAccountId,
            Account::where('code', '5400')->value('id'),
            (float) $this->repayPrincipal,
            (float) ($this->repayInterest ?: 0),
            $this->repayDate,
        );

        $this->repayLoanId = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Repayment recorded']);
    }

    public function render(): mixed
    {
        $loans = Loan::query()->with('payableAccount')->latest()->paginate(15);

        foreach ($loans as $loan) {
            $loan->outstanding = $loan->outstandingPrincipal();
        }

        return view('livewire.admin.accounts.loans', [
            'loans'        => $loans,
            'cashAccounts' => Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(),
        ])->layout('layouts.admin.admin');
    }
}
