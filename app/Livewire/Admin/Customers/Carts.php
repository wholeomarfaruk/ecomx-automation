<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Cart;
use Livewire\Component;
use Livewire\WithPagination;

class Carts extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function render(): mixed
    {
        $carts = Cart::query()
            ->with('customer', 'device')
            ->withCount('items')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->whereHas('customer', fn($c) => $c
                    ->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.customers.carts', [
            'carts'          => $carts,
            'totalCount'     => Cart::count(),
            'activeCount'    => Cart::where('status', 'active')->count(),
            'convertedCount' => Cart::where('status', 'converted')->count(),
            'abandonedCount' => Cart::where('status', 'abandoned')->count(),
        ])->layout('layouts.admin.admin');
    }
}
