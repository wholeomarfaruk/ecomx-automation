<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class LovedProducts extends Component
{
    use WithPagination;

    public string $search = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void { $this->resetPage(); }

    public function render(): mixed
    {
        $products = Product::query()
            ->withCount('wishlistItems')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->having('wishlist_items_count', '>', 0)
            ->orderByDesc('wishlist_items_count')
            ->paginate(15);

        return view('livewire.admin.customers.loved-products', [
            'products'   => $products,
            'totalLoves' => \App\Models\WishlistItem::count(),
        ])->layout('layouts.admin.admin');
    }
}
