<?php

namespace App\Livewire\Admin\Sales;

use App\Models\Order;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The Orders list's "View details" modal as its own component — it used
 * to live inside the Orders page, so opening and closing it re-rendered the
 * whole orders table. Opened with the browser event `open-order-view`
 * ({ orderId }); closed by `close-order-view` ({ orderId }) when that order
 * is deleted.
 */
class OrderQuickView extends Component
{
    public bool $viewModal = false;

    public ?int $viewOrderId = null;

    #[On('open-order-view')]
    public function open(int $orderId): void
    {
        $this->viewOrderId = $orderId;
        $this->viewModal = true;
    }

    #[On('close-order-view')]
    public function closeIfShowing(int $orderId): void
    {
        if ($this->viewOrderId === $orderId) {
            $this->closeViewModal();
        }
    }

    public function closeViewModal(): void
    {
        $this->viewModal = false;
        $this->viewOrderId = null;
    }

    public function render()
    {
        return view('livewire.admin.sales.order-quick-view', [
            'viewingOrder' => $this->viewModal && $this->viewOrderId
                ? Order::with([
                    'customer',
                    'billingAddress',
                    'shippingAddress',
                    'items.product',
                    'items.variant',
                    'payments',
                    'courierShipments.courier',
                ])->find($this->viewOrderId)
                : null,
        ]);
    }
}
