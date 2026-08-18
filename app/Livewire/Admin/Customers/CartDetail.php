<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Cart;
use Livewire\Component;

class CartDetail extends Component
{
    public int $cartId;

    public function mount(int $id): void
    {
        $this->cartId = $id;
    }

    public function render(): mixed
    {
        $cart = Cart::with([
            'customer',
            'device',
            'items.product',
            'items.variant',
            'items.combo.items.product',
            'items.combo.items.variant',
        ])->findOrFail($this->cartId);

        return view('livewire.admin.customers.cart-detail', [
            'cart' => $cart,
        ])->layout('layouts.admin.admin');
    }
}
