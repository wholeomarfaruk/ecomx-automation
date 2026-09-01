<?php

namespace App\Livewire\Admin\Sales;

use App\Courier\CourierManager;
use App\Courier\DTO\ShipmentRequest;
use App\Courier\Enums\CourierCapability;
use App\Courier\Enums\ShipmentType;
use App\Courier\Exceptions\CourierException;
use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentMethod;
use App\Enums\Sales\PaymentStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Courier;
use App\Models\CourierAccount;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
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

    // Courier booking form
    public bool   $bookingModal          = false;
    public ?int   $bookingAccountId      = null;
    public string $bookingRecipientName  = '';
    public string $bookingRecipientPhone = '';
    public string $bookingRecipientAddress = '';
    public string $bookingCodAmount      = '';
    public string $bookingWeight         = '0.5';
    public string $bookingQuantity       = '1';
    public string $bookingDescription    = '';
    public string $bookingInstruction    = '';
    public bool   $bookingIsExchange     = false;
    public string $bookingExchangeDescription = '';

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

    public function openBookingModal(): void
    {
        $this->guardManage();

        $order = Order::with('shippingAddress')->findOrFail($this->orderId);
        $address = $order->shippingAddress;

        $this->reset([
            'bookingAccountId', 'bookingIsExchange', 'bookingExchangeDescription',
        ]);
        $this->resetValidation();

        $this->bookingRecipientName    = $address->name ?? '';
        $this->bookingRecipientPhone   = $address->phone ?? '';
        $this->bookingRecipientAddress = $address->full_address ?? '';
        $this->bookingCodAmount        = (string) $order->due_amount;
        $this->bookingWeight           = '0.5';
        $this->bookingQuantity         = (string) max(1, $order->items->sum('quantity'));
        $this->bookingDescription      = $order->items->pluck('product_name')->filter()->implode(', ') ?: "Order #{$order->id}";
        $this->bookingInstruction      = '';

        $this->bookingModal = true;
    }

    public function closeBookingModal(): void
    {
        $this->bookingModal = false;
    }

    /**
     * Every active courier account whose driver actually supports booking a
     * shipment — an account for a courier with no working driver (e.g.
     * Sundarban/SA Paribahan before their driver ships) is excluded here
     * rather than shown and failing when picked.
     */
    public function bookableAccounts()
    {
        return CourierAccount::query()
            ->where('is_active', true)
            ->whereHas('courier', fn ($q) => $q->where('is_active', true))
            ->with('courier')
            ->get()
            ->filter(fn (CourierAccount $account) => $account->courier->hasCapability(CourierCapability::SHIPMENT_CREATE->value))
            ->values();
    }

    #[Computed]
    public function selectedAccountCourier(): ?Courier
    {
        return $this->bookingAccountId
            ? CourierAccount::find($this->bookingAccountId)?->courier
            : null;
    }

    public function bookShipment(): void
    {
        $this->guardManage();

        $this->validate([
            'bookingAccountId' => 'required|integer|exists:courier_accounts,id',
            'bookingRecipientName' => 'required|string|max:255',
            'bookingRecipientPhone' => 'required|string|max:20',
            'bookingRecipientAddress' => 'required|string',
            'bookingCodAmount' => 'required|numeric|min:0',
            'bookingWeight' => 'required|numeric|min:0.01',
            'bookingQuantity' => 'required|integer|min:1',
            'bookingDescription' => 'nullable|string|max:500',
            'bookingInstruction' => 'nullable|string|max:500',
            'bookingExchangeDescription' => 'required_if:bookingIsExchange,true|nullable|string|max:500',
        ]);

        $account = CourierAccount::with('courier')->findOrFail($this->bookingAccountId);
        $order = Order::findOrFail($this->orderId);

        $request = new ShipmentRequest(
            orderId: (string) $order->id,
            invoiceNumber: (string) $order->id,
            recipientName: $this->bookingRecipientName,
            recipientPhone: $this->bookingRecipientPhone,
            recipientAddress: $this->bookingRecipientAddress,
            codAmount: (float) $this->bookingCodAmount,
            itemWeight: (float) $this->bookingWeight,
            itemQuantity: (int) $this->bookingQuantity,
            itemDescription: $this->bookingDescription ?: null,
            specialInstruction: $this->bookingInstruction ?: null,
            type: $this->bookingIsExchange ? ShipmentType::EXCHANGE : ShipmentType::NORMAL,
            exchangeItemDescription: $this->bookingIsExchange ? $this->bookingExchangeDescription : null,
        );

        try {
            $response = app(CourierManager::class)->createShipment($order, $account->courier->driver_key, $request);
        } catch (CourierException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        if ($response->success) {
            activity('sales')
                ->causedBy(auth()->user())
                ->performedOn($order)
                ->event('updated')
                ->log("Courier shipment booked with {$account->courier->name} for Order #{$order->id} (tracking: {$response->trackingNumber})");
        }

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success
                ? "Shipment booked — tracking #{$response->trackingNumber}"
                : ($response->errorMessage ?? 'Failed to book shipment.'),
        ]);

        if ($response->success) {
            $this->bookingModal = false;
            $this->courierStatus = $response->status->value;
        }
    }

    public function syncCourierShipment(int $shipmentId): void
    {
        $this->guardManage();

        $shipment = CourierShipment::findOrFail($shipmentId);
        $response = app(CourierManager::class)->syncTracking($shipment);

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Tracking synced.' : ($response->errorMessage ?? 'Sync failed.'),
        ]);
    }

    public function cancelCourierShipment(int $shipmentId): void
    {
        $this->guardManage();

        $shipment = CourierShipment::findOrFail($shipmentId);
        $response = app(CourierManager::class)->cancelShipment($shipment);

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Shipment cancelled.' : ($response->errorMessage ?? 'Cancellation failed.'),
        ]);
    }

    protected function guardManage(): void
    {
        if (! auth()->user()->can('courier_configuration.manage')) {
            abort(403, 'Unauthorized action.');
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
            'courierShipments.courier',
            'courierShipments.courierAccount',
            'courierShipments.trackingEvents',
        ])->findOrFail($this->orderId);

        $canManageCourier = auth()->user()->can('courier_configuration.manage');

        return view('livewire.admin.sales.order-detail', [
            'order'               => $order,
            'statuses'            => OrderStatus::cases(),
            'paymentStatuses'     => PaymentStatus::cases(),
            'paymentMethods'      => PaymentMethod::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
            'courierStatuses'     => CourierStatus::cases(),
            'bookableAccounts'    => $canManageCourier ? $this->bookableAccounts() : collect(),
            'canManageCourier'    => $canManageCourier,
        ])->layout('layouts.admin.admin');
    }
}
