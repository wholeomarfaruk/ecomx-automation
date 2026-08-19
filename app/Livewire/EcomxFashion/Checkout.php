<?php

namespace App\Livewire\EcomxFashion;

use App\Models\Block;
use App\Models\Cart;
use App\Services\BlockGuard;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Checkout extends Component
{
    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $note = '';
    public string $delivery_area = 'dhaka';
    public string $payment_method = 'cod';
    public string $transaction_id = '';

    public bool $placed = false;

    public array $deliveryAreas = [
        ['id' => 'dhaka', 'name' => 'Inside Dhaka', 'charge' => 70],
        ['id' => 'outside', 'name' => 'Outside Dhaka', 'charge' => 130],
    ];

    public string $bkashNumber = '01682963493';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'delivery_area' => 'required|in:dhaka,outside',
            'payment_method' => 'required|in:cod,bkash',
            'transaction_id' => 'required_if:payment_method,bkash|nullable|string|max:100',
        ];
    }

    public function placeOrder(): void
    {
        if (app(BlockGuard::class)->isBlocked(request(), Block::SCOPE_CHECKOUT)) {
            abort(403, 'This action is not available for your account.');
        }

        $this->validate();

        $this->placed = true;
    }

    public function getCartProperty(): Cart
    {
        $device = request()->attributes->get('device');

        $cart = Cart::firstOrCreate(
            ['customer_id' => null, 'device_id' => $device?->id],
            []
        );

        $cart->load('items.product', 'items.variant.values.productAttributeValue.attributeValue.attribute');

        return $cart;
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.checkout', [
            'cart' => $this->cart,
        ]);
    }
}
