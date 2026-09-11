<?php

namespace App\Livewire\Admin\Accounts\Settings;

use App\Enums\Accounts\AccountType;
use App\Models\Account;
use Livewire\Component;

/**
 * Read-mostly view of the full chart of accounts tree, with the ability to
 * add non-system accounts. System accounts (seeded by AccountSeeder) are
 * shown but not editable/deletable from here — see Account::$fillable and
 * the is_system flag.
 */
class ChartOfAccounts extends Component
{
    public bool $createModal = false;
    public string $newName = '';
    public ?int $newParentId = null;
    public string $newDescription = '';

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newParentId', 'newDescription']);
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createAccount(): void
    {
        $this->validate([
            'newName'     => 'required|string|max:150',
            'newParentId' => 'required|exists:accounts,id',
        ]);

        $parent = Account::findOrFail($this->newParentId);

        Account::create([
            'code'           => (string) ((int) Account::max('code') + 1),
            'name'           => $this->newName,
            'type'           => $parent->type,
            'subtype'        => $parent->subtype,
            'normal_balance' => $parent->normal_balance,
            'parent_id'      => $parent->id,
            'is_system'      => false,
            'is_active'      => true,
            'description'    => $this->newDescription ?: null,
        ]);

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account added']);
    }

    public function render(): mixed
    {
        $accountsByType = collect(AccountType::cases())->mapWithKeys(fn ($type) => [
            $type->value => Account::where('type', $type)->whereNull('parent_id')->with('children')->orderBy('code')->get(),
        ]);

        return view('livewire.admin.accounts.settings.chart-of-accounts', [
            'accountsByType' => $accountsByType,
            'allAccounts'    => Account::orderBy('code')->get(),
        ])->layout('layouts.admin.admin');
    }
}
