<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\Account;
use Livewire\Component;

/**
 * Case 2.1 — "হাতে আছে": every cash/bank/mobile-banking account the
 * business holds, plus the ability to add a new one. New accounts are
 * created as non-system children of the matching system parent (Cash 1010,
 * Bank 1020, Mobile Banking 1030) so they still roll up into that parent's
 * type/subtype grouping. Also lists 'courier' subtype accounts (1045 and
 * its per-courier children, seeded by AccountSeeder) — money couriers are
 * currently holding on the business's behalf (COD collected but not yet
 * settled to a real bank account), shown here alongside true liquid
 * accounts so the total picture of "what do we currently hold" is visible
 * in one place, even though it isn't itself a manual payment/refund source.
 */
class CashBankAccounts extends Component
{
    public bool $createModal = false;

    public string $newName = '';
    public ?int $newParentId = null;
    public string $newOpeningBalance = '0';

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newParentId', 'newOpeningBalance']);
        $this->newOpeningBalance = '0';
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createAccount(): void
    {
        $this->validate([
            'newName'            => 'required|string|max:150|unique:accounts,name',
            'newParentId'        => 'required|exists:accounts,id',
            'newOpeningBalance'  => 'nullable|numeric',
        ]);

        $parent = Account::findOrFail($this->newParentId);

        $nextCode = (string) ((int) Account::max('code') + 1);

        $account = Account::create([
            'code'           => $nextCode,
            'name'           => $this->newName,
            'type'           => $parent->type,
            'subtype'        => $parent->subtype,
            'normal_balance' => $parent->normal_balance,
            'parent_id'      => $parent->id,
            'is_system'      => false,
            'is_active'      => true,
            'opening_balance'=> $this->newOpeningBalance ?: 0,
        ]);

        activity('accounts')
            ->causedBy(auth()->user())
            ->performedOn($account)
            ->event('created')
            ->log("Account \"{$account->name}\" was added under {$parent->name}");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account added — record its opening balance from Settings > Opening Balance']);
    }

    public function toggleActive(int $id): void
    {
        $account = Account::findOrFail($id);

        if ($account->is_system) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'System accounts cannot be deactivated']);
            return;
        }

        $account->update(['is_active' => ! $account->is_active]);
        $this->dispatch('toast', ['type' => 'success', 'message' => "{$account->name} is now " . ($account->is_active ? 'active' : 'inactive')]);
    }

    public function render(): mixed
    {
        $cashLikeParents = Account::query()
            ->whereIn('code', ['1010', '1020', '1030', '1040'])
            ->get();

        $accounts = Account::query()
            ->whereIn('subtype', ['cash', 'bank', 'mobile_banking', 'courier'])
            ->orderBy('code')
            ->get();

        return view('livewire.admin.accounts.cash-bank-accounts', [
            'accounts'          => $accounts,
            'cashLikeParents'   => $cashLikeParents,
        ])->layout('layouts.admin.admin');
    }
}
