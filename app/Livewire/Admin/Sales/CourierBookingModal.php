<?php

namespace App\Livewire\Admin\Sales;

use App\Livewire\Concerns\BooksCourierShipments;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The "Book Courier Shipment" modal as its own component, shared by the
 * Orders list and the Order page. It used to live inside those pages, so
 * opening it, picking a courier account and booking each re-rendered the
 * whole page (the orders table with its queries, or the full order view);
 * now only this modal re-renders. Opened with the browser event
 * `open-courier-booking` ({ orderId }); after a successful booking it
 * dispatches `courier-booked` ({ orderId }) so the page refreshes once.
 */
class CourierBookingModal extends Component
{
    use BooksCourierShipments;

    #[On('open-courier-booking')]
    public function open(int $orderId): void
    {
        $this->openBookingModal($orderId);
    }

    public function render()
    {
        return view('livewire.admin.sales.courier-booking-modal', [
            // Only needed while the modal is open — no courier queries on a closed modal.
            'bookableAccounts' => $this->bookingModal && auth()->user()?->can('courier_configuration.manage')
                ? $this->bookableAccounts()
                : collect(),
        ]);
    }
}
