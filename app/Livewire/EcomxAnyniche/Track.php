<?php

namespace App\Livewire\EcomxAnyniche;

use App\Models\Order;
use App\Support\PhoneNumber;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Track-order search page. Visual design ported from
 * juwel-trade-corporation's storefront.track-order (jtc-track,
 * jtc-track-card, jtc-track-form classes). Splits the old single-page
 * Track component in two: this page is guest lookup + a logged-in
 * customer's order list; selecting or successfully looking up an order
 * navigates to {@see TrackDetails} at its own /track/{order} route, which
 * owns the timeline, items and OTP confirm/cancel flow.
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class Track extends Component
{
    public string $orderId = '';
    public string $phone = '';
    public string $trackError = '';

    /** Guest lookup: order # + phone must both match, so a stranger can't view someone else's order by guessing the ID. */
    public function track(): void
    {
        $this->trackError = '';

        $numericId = preg_replace('/\D/', '', $this->orderId);

        $nationalPhone = PhoneNumber::national($this->phone);

        $order = $numericId !== ''
            ? Order::where('id', $numericId)
                ->whereHas('customer', fn ($q) => $q->where('phone', $nationalPhone))
                ->first()
            : null;

        if (! $order) {
            $this->trackError = 'No order found with that ID and phone number.';

            return;
        }

        session(['track_order_verified_' . $order->id => true]);

        $this->redirectRoute('ecomx-anyniche.track.show', ['order' => $order->id], navigate: true);
    }

    public function viewOrder(int $orderId): void
    {
        $customer = auth()->user()?->customer;

        // Only ever navigate to an order that actually belongs to the logged-in customer.
        if (! $customer || ! Order::where('id', $orderId)->where('customer_id', $customer->id)->exists()) {
            return;
        }

        $this->redirectRoute('ecomx-anyniche.track.show', ['order' => $orderId], navigate: true);
    }

    public function render()
    {
        $customer = auth()->user()?->customer;

        $myOrders = $customer
            ? Order::where('customer_id', $customer->id)
                ->withCount('items')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('ecomx-anyniche.livewire.track', [
            'myOrders' => $myOrders,
        ]);
    }
}
