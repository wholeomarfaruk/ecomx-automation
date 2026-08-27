<?php

namespace App\Livewire\Admin\Purchase;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrders extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterStatus  = '';
    public string $filterSupplier = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingFilterStatus(): void   { $this->resetPage(); }
    public function updatingFilterSupplier(): void { $this->resetPage(); }

    public function cancelOrder(int $id): void
    {
        $order = PurchaseOrder::findOrFail($id);
        $order->update(['status' => 'cancelled']);

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Purchase order \"{$order->order_number}\" was cancelled");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order cancelled']);
    }

    public function deleteOrder(int $id): void
    {
        $order = PurchaseOrder::findOrFail($id);
        $number = $order->order_number;

        $order->delete();

        activity('purchase')
            ->causedBy(auth()->user())
            ->withProperties(['order_number' => $number])
            ->event('deleted')
            ->log("Purchase order \"{$number}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order deleted']);
    }

    public function render(): mixed
    {
        $orders = PurchaseOrder::query()
            ->withCount('items')
            ->with(['supplier', 'items'])
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('order_number', 'like', "%{$this->search}%")
                ->orWhereHas('supplier', fn($sup) => $sup->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSupplier !== '', fn($q) => $q->where('supplier_id', $this->filterSupplier))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.purchase.purchase-orders', [
            'orders'          => $orders,
            'suppliers'       => Supplier::orderBy('name')->get(['id', 'name']),
            'totalCount'      => PurchaseOrder::count(),
            'pendingCount'    => PurchaseOrder::where('status', 'pending')->count(),
            'receivedCount'   => PurchaseOrder::where('status', 'received')->count(),
        ])->layout('layouts.admin.admin');
    }
}
