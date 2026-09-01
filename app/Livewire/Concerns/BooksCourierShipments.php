<?php

namespace App\Livewire\Concerns;

use App\Courier\CourierManager;
use App\Courier\DTO\ShipmentRequest;
use App\Courier\Enums\CourierCapability;
use App\Courier\Enums\ShipmentType;
use App\Courier\Exceptions\CourierException;
use App\Models\Courier;
use App\Models\CourierAccount;
use App\Models\CourierShipment;
use App\Models\Order;
use Livewire\Attributes\Computed;

/**
 * Shared courier-booking modal logic for any admin page that lets someone
 * book/manage a shipment for an order — the Orders list (book right from a
 * row, no navigation) and OrderDetail (book from the order's own Courier
 * card) both use this instead of duplicating the same ~120 lines. Only
 * $bookingOrderId differs between call sites: OrderDetail always books for
 * its own $this->orderId, Orders passes whichever row's button was clicked.
 */
trait BooksCourierShipments
{
    public bool   $bookingModal              = false;
    public ?int   $bookingOrderId            = null;
    public ?int   $bookingAccountId          = null;
    public string $bookingRecipientName      = '';
    public string $bookingRecipientPhone     = '';
    public string $bookingRecipientAddress   = '';
    public string $bookingCodAmount          = '';
    public string $bookingWeight             = '0.5';
    public string $bookingQuantity           = '1';
    public string $bookingDescription        = '';
    public string $bookingInstruction        = '';
    public bool   $bookingIsExchange         = false;
    public string $bookingExchangeDescription = '';

    public function openBookingModal(int $orderId): void
    {
        $this->guardCourierManage();

        $order = Order::with(['shippingAddress', 'items'])->findOrFail($orderId);
        $address = $order->shippingAddress;

        $this->reset([
            'bookingAccountId', 'bookingIsExchange', 'bookingExchangeDescription',
        ]);
        $this->resetValidation();

        $this->bookingOrderId          = $order->id;
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
        $this->bookingOrderId = null;
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
        $this->guardCourierManage();

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
        $order = Order::findOrFail($this->bookingOrderId);

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
            $this->bookingOrderId = null;
        }
    }

    public function syncCourierShipment(int $shipmentId): void
    {
        $this->guardCourierManage();

        $shipment = CourierShipment::findOrFail($shipmentId);
        $response = app(CourierManager::class)->syncTracking($shipment);

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Tracking synced.' : ($response->errorMessage ?? 'Sync failed.'),
        ]);
    }

    public function cancelCourierShipment(int $shipmentId): void
    {
        $this->guardCourierManage();

        $shipment = CourierShipment::findOrFail($shipmentId);
        $response = app(CourierManager::class)->cancelShipment($shipment);

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Shipment cancelled.' : ($response->errorMessage ?? 'Cancellation failed.'),
        ]);
    }

    protected function guardCourierManage(): void
    {
        if (! auth()->user()->can('courier_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }
}
