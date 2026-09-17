<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Models\JournalEntry;
use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Browsable list of every order that has at least one posted journal entry
 * — click through to OrderLedger for that order's full accounting trail.
 * Entry point for "see all order ledgers" rather than searching for one by
 * order number.
 */
class OrderLedgers extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $orderIds = JournalEntry::query()
            ->where('source_type', Order::class)
            ->where('status', '!=', 'void')
            ->distinct()
            ->pluck('source_id');

        $orders = Order::query()
            ->with('customer:id,full_name,phone')
            ->whereIn('id', $orderIds)
            ->when($this->search, fn ($q) => $q
                ->where('id', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($c) => $c
                    ->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.accounts.reports.order-ledgers', [
            'orders' => $orders,
        ])->layout('layouts.admin.admin');
    }
}
