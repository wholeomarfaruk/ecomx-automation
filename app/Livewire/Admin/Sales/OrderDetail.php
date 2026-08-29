<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentMethod;
use App\Enums\Sales\PaymentStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Order;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class OrderDetail extends Component
{
    public int $orderId;

    public string $status            = '';
    public string $paymentStatus     = '';
    public string $fulfillmentStatus = '';

    public string $courierProvider = '';
    public string $courierTrackingNumber = '';
    public string $courierCharge   = '';
    public string $courierStatus   = '';

    public bool   $paymentModal    = false;
    public string $paymentMethod   = 'cash';
    public string $transactionId   = '';
    public string $paymentAmount   = '';
    public string $paymentStatusNew = 'paid';

    /** @var array<int, string> item_id => returned_quantity */
    public array $returnedQuantities = [];

    public function mount(int $id): void
    {
        $order = Order::findOrFail($id);

        $this->orderId           = $order->id;
        $this->status            = $order->status->value;
        $this->paymentStatus     = $order->payment_status->value;
        $this->fulfillmentStatus = $order->fulfillment_status->value;

        $this->courierProvider        = $order->courier_provider ?? '';
        $this->courierTrackingNumber  = $order->courier_tracking_number ?? '';
        $this->courierCharge          = $order->courier_charge !== null ? (string) $order->courier_charge : '';
        $this->courierStatus          = $order->courier_status?->value ?? '';

        foreach ($order->items as $item) {
            $this->returnedQuantities[$item->id] = (string) $item->returned_quantity;
        }
    }

    public function updateStatus(): void
    {
        $order = Order::with('items')->findOrFail($this->orderId);
        $previousStatus = $order->status->value;
        $stockService = app(StockService::class);

        try {
            DB::transaction(function () use ($order, $previousStatus, $stockService) {
                $order->update([
                    'status'             => $this->status,
                    'payment_status'     => $this->paymentStatus,
                    'fulfillment_status' => $this->fulfillmentStatus,
                ]);

                $deductOnConfirm = (bool) Setting::get('deduct_on_order_confirm', true, 'inventory');
                $restockOnRelease = (bool) Setting::get('restock_on_cancel_or_return', true, 'inventory');

                if ($deductOnConfirm && $this->status === 'confirmed' && $previousStatus !== 'confirmed') {
                    $stockService->commitOrder($order);
                } elseif ($restockOnRelease && in_array($this->status, ['cancelled', 'returned'], true) && $previousStatus === 'confirmed') {
                    $stockService->releaseOrder($order);
                }
            });
        } catch (InsufficientStockException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Order #{$order->id} status updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Order status updated']);
    }

    public function updateCourier(): void
    {
        $order = Order::findOrFail($this->orderId);

        $order->update([
            'courier_provider'          => $this->courierProvider ?: null,
            'courier_tracking_number'   => $this->courierTrackingNumber ?: null,
            'courier_charge'            => $this->courierCharge !== '' ? $this->courierCharge : null,
            'courier_status'            => $this->courierStatus ?: null,
            'courier_status_updated_at' => now(),
        ]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Order #{$order->id} courier details updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Courier details updated']);
    }

    public function openPaymentModal(): void
    {
        $this->reset(['paymentMethod', 'transactionId', 'paymentAmount']);
        $this->paymentMethod    = 'cash';
        $this->paymentStatusNew = 'paid';
        $this->resetValidation();
        $this->paymentModal = true;
    }

    public function addPayment(): void
    {
        $this->validate([
            'paymentMethod'    => ['required', Rule::enum(PaymentMethod::class)],
            'transactionId'    => 'nullable|string|max:255',
            'paymentAmount'    => 'required|numeric|min:0.01',
            'paymentStatusNew' => 'required|in:pending,partial,paid,failed,refunded',
        ]);

        $order = Order::findOrFail($this->orderId);

        $order->payments()->create([
            'payment_method' => $this->paymentMethod,
            'transaction_id' => $this->transactionId ?: null,
            'amount'         => $this->paymentAmount,
            'status'         => $this->paymentStatusNew,
            'paid_at'        => $this->paymentStatusNew === 'paid' ? now() : null,
        ]);

        $order->recalculateTotals();

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Payment of {$this->paymentAmount} recorded for Order #{$order->id}");

        $this->paymentModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment recorded']);
    }

    public function saveReturns(): void
    {
        $order = Order::with('items')->findOrFail($this->orderId);
        $stockService = app(StockService::class);
        $restockOnReturn = (bool) Setting::get('restock_on_cancel_or_return', true, 'inventory');

        DB::transaction(function () use ($order, $stockService, $restockOnReturn) {
            foreach ($order->items as $item) {
                $value = $this->returnedQuantities[$item->id] ?? '0';
                $qty   = min((float) $value, (float) $item->quantity);
                $qty   = max(0, $qty);

                $item->update(['returned_quantity' => $qty]);

                if ($restockOnReturn && $qty > 0) {
                    $stockService->restockReturnedItem($item, $qty);
                }
            }
        });

        $order->syncReturnStatus();
        $this->status = $order->fresh()->status->value;

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Returns recorded for Order #{$order->id}");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Returns saved']);
    }

    public function render(): mixed
    {
        $order = Order::with([
            'customer',
            'billingAddress',
            'shippingAddress',
            'items.product',
            'items.variant',
            'items.combo.items.product',
            'items.combo.items.variant',
            'payments',
        ])->findOrFail($this->orderId);

        return view('livewire.admin.sales.order-detail', [
            'order'               => $order,
            'statuses'            => OrderStatus::cases(),
            'paymentStatuses'     => PaymentStatus::cases(),
            'paymentMethods'      => PaymentMethod::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
            'courierStatuses'     => CourierStatus::cases(),
        ])->layout('layouts.admin.admin');
    }
}
