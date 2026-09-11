<?php

namespace App\Livewire\Admin\Accounts;

use App\Actions\Accounts\PostCurrencyConversion;
use App\Actions\Accounts\PostManualTransaction;
use App\Actions\Accounts\PostTransferWithFee;
use App\Actions\Accounts\VoidJournalEntry;
use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\JournalEntry;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cases 1.1-1.7 — the simple "টাকা পেলাম / টাকা দিলাম / ট্রান্সফার" screens.
 * The business only ever picks a mode, two accounts, an amount, and
 * optionally a fee; PostJournalEntry (via the mode-specific Action) is what
 * actually builds the balanced Dr/Cr lines behind the scenes.
 */
class Transactions extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $filterType = '';
    public string $filterStatus = '';

    public bool $createModal = false;
    public string $mode = 'income'; // income, expense, transfer, conversion

    public string $newDate = '';
    public string $newDescription = '';
    public ?int $newDebitAccountId = null;
    public ?int $newCreditAccountId = null;
    public string $newAmount = '';
    public ?int $newFeeAccountId = null;
    public string $newFee = '0';
    public string $newAmountReceived = '';

    public ?int $voidTargetId = null;
    public string $voidReason = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterType(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openCreateModal(string $mode = 'income'): void
    {
        $this->reset([
            'newDescription', 'newDebitAccountId', 'newCreditAccountId',
            'newAmount', 'newFeeAccountId', 'newFee', 'newAmountReceived',
        ]);
        $this->mode = $mode;
        $this->newDate = now()->toDateString();
        $this->newFee = '0';
        $this->resetValidation();
        $this->createModal = true;
    }

    public function save(): void
    {
        match ($this->mode) {
            'income'      => $this->saveManual(TransactionType::MANUAL_INCOME),
            'expense'     => $this->saveManual(TransactionType::MANUAL_EXPENSE),
            'transfer'    => $this->saveTransfer(),
            'conversion'  => $this->saveConversion(),
            default       => null,
        };
    }

    protected function saveManual(TransactionType $type): void
    {
        $this->validate([
            'newDate'            => 'required|date',
            'newDebitAccountId'  => 'required|exists:accounts,id',
            'newCreditAccountId' => 'required|different:newDebitAccountId|exists:accounts,id',
            'newAmount'          => 'required|numeric|gt:0',
        ]);

        app(PostManualTransaction::class)->handle(
            (int) $this->newDebitAccountId,
            (int) $this->newCreditAccountId,
            (float) $this->newAmount,
            $this->newDate,
            $type,
            $this->newDescription ?: null,
        );

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Transaction posted']);
    }

    protected function saveTransfer(): void
    {
        $this->validate([
            'newDate'            => 'required|date',
            'newDebitAccountId'  => 'required|exists:accounts,id',
            'newCreditAccountId' => 'required|different:newDebitAccountId|exists:accounts,id',
            'newAmount'          => 'required|numeric|gt:0',
            'newFee'             => 'nullable|numeric|gte:0',
            'newFeeAccountId'    => 'required_if:newFee,!=,0|nullable|exists:accounts,id',
        ]);

        app(PostTransferWithFee::class)->handle(
            (int) $this->newCreditAccountId, // from
            (int) $this->newDebitAccountId,  // to
            (float) $this->newAmount,
            $this->newDate,
            $this->newFeeAccountId ? (int) $this->newFeeAccountId : null,
            (float) ($this->newFee ?: 0),
            $this->newDescription ?: null,
        );

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer posted']);
    }

    protected function saveConversion(): void
    {
        $this->validate([
            'newDate'            => 'required|date',
            'newDebitAccountId'  => 'required|exists:accounts,id',
            'newCreditAccountId' => 'required|different:newDebitAccountId|exists:accounts,id',
            'newAmount'          => 'required|numeric|gt:0',
            'newAmountReceived'  => 'required|numeric|gt:0',
            'newFee'             => 'nullable|numeric|gte:0',
            'newFeeAccountId'    => 'required_if:newFee,!=,0|nullable|exists:accounts,id',
        ]);

        app(PostCurrencyConversion::class)->handle(
            (int) $this->newCreditAccountId, // from
            (int) $this->newDebitAccountId,  // to
            (float) $this->newAmount,
            (float) $this->newAmountReceived,
            $this->newDate,
            $this->newFeeAccountId ? (int) $this->newFeeAccountId : null,
            (float) ($this->newFee ?: 0),
            $this->newDescription ?: null,
        );

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Conversion posted']);
    }

    public function openVoidModal(int $id): void
    {
        $this->voidTargetId = $id;
        $this->voidReason = '';
    }

    public function confirmVoid(): void
    {
        $entry = JournalEntry::findOrFail($this->voidTargetId);

        app(VoidJournalEntry::class)->handle($entry, $this->voidReason ?: null);

        $this->voidTargetId = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => "{$entry->entry_number} voided"]);
    }

    public function render(): mixed
    {
        $entries = JournalEntry::query()
            ->with(['lines.account'])
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('entry_number', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
            ))
            ->when($this->filterType !== '', fn ($q) => $q->where('transaction_type', $this->filterType))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.admin.accounts.transactions', [
            'entries'    => $entries,
            'accounts'   => Account::active()->orderBy('code')->get(),
            'types'      => TransactionType::cases(),
            'statuses'   => JournalEntryStatus::cases(),
        ])->layout('layouts.admin.admin');
    }
}
