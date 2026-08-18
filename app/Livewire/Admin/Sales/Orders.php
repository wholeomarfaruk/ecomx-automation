<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search              = '';
    public string $filterStatus        = '';
    public string $filterPaymentStatus = '';
    public string $filterSource        = '';
    public string $dateFrom            = '';
    public string $dateTo              = '';

    public function updatingSearch(): void              { $this->resetPage(); }
    public function updatingFilterStatus(): void        { $this->resetPage(); }
    public function updatingFilterPaymentStatus(): void { $this->resetPage(); }
    public function updatingFilterSource(): void        { $this->resetPage(); }
    public function updatingDateFrom(): void            { $this->resetPage(); }
    public function updatingDateTo(): void              { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterPaymentStatus', 'filterSource', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render(): mixed
    {
        $orders = Order::query()
            ->with('customer')
            ->withCount('items')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('id', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($c) => $c
                    ->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPaymentStatus !== '', fn ($q) => $q->where('payment_status', $this->filterPaymentStatus))
            ->when($this->filterSource !== '', fn ($q) => $q->where('source', $this->filterSource))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.admin.sales.orders', [
            'orders'        => $orders,
            'statuses'      => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'sources'       => OrderSource::cases(),
            'totalCount'    => Order::count(),
            'totalRevenue'  => Order::where('payment_status', PaymentStatus::PAID)->sum('total_amount'),
            'pendingCount'  => Order::where('status', OrderStatus::PENDING)->count(),
            'dueTotal'      => Order::sum('due_amount'),
        ])->layout('layouts.admin.admin');
    }
}
