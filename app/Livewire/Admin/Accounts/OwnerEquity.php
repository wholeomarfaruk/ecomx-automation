<?php

namespace App\Livewire\Admin\Accounts;

use App\Actions\Accounts\PostOwnerInvestment;
use App\Actions\Accounts\PostOwnerWithdrawal;
use App\Models\Account;
use App\Models\JournalEntry;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cases 8.1-8.3 — owner investment/withdrawal. Both bypass P&L entirely
 * (Golden Rule #5), so this screen exists separately from Transactions
 * rather than reusing its income/expense modes.
 */
class OwnerEquity extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $mode = 'investment';
    public bool $modal = false;

    public string $amount = '';
    public string $entryDate = '';
    public ?int $assetAccountId = null;

    public function openModal(string $mode): void
    {
        $this->mode = $mode;
        $this->amount = '';
        $this->entryDate = now()->toDateString();
        $this->assetAccountId = null;
        $this->resetValidation();
        $this->modal = true;
    }

    public function save(): void
    {
        $this->validate([
            'amount'         => 'required|numeric|gt:0',
            'entryDate'      => 'required|date',
            'assetAccountId' => 'required|exists:accounts,id',
        ]);

        if ($this->mode === 'investment') {
            app(PostOwnerInvestment::class)->handle(
                (int) $this->assetAccountId,
                Account::where('code', '3000')->value('id'),
                (float) $this->amount,
                $this->entryDate,
            );
        } else {
            app(PostOwnerWithdrawal::class)->handle(
                Account::where('code', '3100')->value('id'),
                (int) $this->assetAccountId,
                (float) $this->amount,
                $this->entryDate,
            );
        }

        $this->modal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Recorded']);
    }

    public function render(): mixed
    {
        $capital = Account::where('code', '3000')->firstOrFail();
        $drawings = Account::where('code', '3100')->firstOrFail();

        $entries = JournalEntry::query()
            ->whereIn('transaction_type', ['owner_investment', 'owner_withdrawal'])
            ->with('lines.account')
            ->latest('entry_date')
            ->paginate(15);

        return view('livewire.admin.accounts.owner-equity', [
            'entries'        => $entries,
            'netInvestment'  => $capital->balance() - $drawings->balance(),
            'totalInvested'  => $capital->balance(),
            'totalWithdrawn' => $drawings->balance(),
            'assetAccounts'  => Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking', 'fixed_asset'])->orderBy('code')->get(),
        ])->layout('layouts.admin.admin');
    }
}
