<?php

namespace App\Livewire\Admin\Accounts\Settings;

use App\Actions\Accounts\PostOpeningBalance;
use App\Models\Account;
use Livewire\Component;

/**
 * Case 9.1 — a one-time (or occasional, if adding a newly-discovered
 * account) multi-line opening balance entry. Rows with a blank amount are
 * skipped; PostOpeningBalance computes the Opening Balance Equity plug so
 * the entry always balances regardless of how many rows are filled in.
 */
class OpeningBalance extends Component
{
    public string $entryDate = '';

    /** @var array<int, string> account_id => signed amount as typed */
    public array $amounts = [];

    public function mount(): void
    {
        $this->entryDate = now()->toDateString();
    }

    public function save(): void
    {
        $this->validate([
            'entryDate' => 'required|date',
        ]);

        $signedAmounts = collect($this->amounts)
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->map(fn ($v) => (float) $v)
            ->all();

        if (empty($signedAmounts)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Enter at least one opening balance']);
            return;
        }

        app(PostOpeningBalance::class)->handle(
            $signedAmounts,
            Account::where('code', '3950')->value('id'),
            $this->entryDate,
        );

        $this->amounts = [];
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Opening balance posted']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.accounts.settings.opening-balance', [
            'accounts' => Account::active()->where('code', '!=', '3950')->orderBy('code')->get(),
        ])->layout('layouts.admin.admin');
    }
}
