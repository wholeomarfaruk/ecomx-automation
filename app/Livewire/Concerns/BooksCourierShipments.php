<?php

namespace App\Livewire\Concerns;

use App\Courier\CourierManager;
use App\Courier\DTO\ShipmentRequest;
use App\Courier\Enums\CourierCapability;
use App\Courier\Enums\ShipmentType;
use App\Courier\Exceptions\CourierException;
use App\Enums\Sales\OrderStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Courier;
use App\Models\CourierAccount;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
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

        $order = Order::with(['shippingAddress', 'items.product'])->findOrFail($orderId);
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
        $this->bookingWeight           = (string) $this->totalWeightFor($order);
        $this->bookingQuantity         = (string) max(1, $order->items->sum('quantity'));
        $this->bookingDescription      = $this->itemDescriptionFor($order);
        $this->bookingInstruction      = '';

        $this->bookingModal = true;
    }

    /**
     * Sum of each item's product weight × quantity, defaulting a
     * weightless product/combo line to 0.5kg (the same flat fallback this
     * form used unconditionally before) so a courier weight is never left
     * at 0 just because a product's weight was never filled in on the
     * catalog. The admin can still edit the field afterward — this only
     * changes what the modal opens with.
     */
    /**
     * Courier item description, one line per product with its maths:
     *   Zareen 4 Pcs - 2x2200=4400,
     *   Elara Purple - 1x1950=1950
     * Capped to the field's 500 characters.
     */
    protected function itemDescriptionFor(Order $order): string
    {
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.') ?: '0';

        $lines = $order->items
            ->filter(fn (OrderItem $item) => filled($item->product_name))
            ->map(function (OrderItem $item) use ($fmt) {
                if ($item->is_gift) {
                    return "{$item->product_name} - {$fmt($item->quantity)}x (gift)";
                }

                $lineTotal = (float) $item->quantity * (float) $item->unit_price;

                return "{$item->product_name} - {$fmt($item->quantity)}x{$fmt($item->unit_price)}={$fmt($lineTotal)}";
            });

        return mb_substr($lines->implode(",\n"), 0, 500) ?: "Order #{$order->id}";
    }

    protected function totalWeightFor(Order $order): float
    {
        $total = $order->items->sum(function (OrderItem $item) {
            $weight = (float) ($item->product?->weight ?? 0);

            return ($weight > 0 ? $weight : 0.5) * (float) $item->quantity;
        });

        return $total > 0 ? round($total, 3) : 0.5;
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

        $statusNote = '';

        if ($response->success) {
            activity('sales')
                ->causedBy(auth()->user())
                ->performedOn($order)
                ->event('updated')
                ->log("Courier shipment booked with {$account->courier->name} for Order #{$order->id} (tracking: {$response->trackingNumber})");

            $statusNote = $this->advanceToProcessingAfterBooking($order);
        }

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success
                ? "Shipment booked — tracking #{$response->trackingNumber}{$statusNote}"
                : ($response->errorMessage ?? 'Failed to book shipment.'),
        ]);

        if ($response->success) {
            $this->bookingModal = false;
            $this->bookingOrderId = null;
            $this->dispatch('courier-booked', orderId: $order->id);
        }
    }

    /**
     * A successfully booked courier shipment means the order is being
     * processed — move a Pending/Confirmed order to Processing (never
     * backwards: Shipped/Delivered/etc. are left alone). Pending → Processing
     * books stock exactly like the status dropdown does (book_on_order_confirm);
     * if that fails for lack of stock, the status is left unchanged and the
     * returned note says so — the shipment itself stays booked.
     *
     * @return string suffix for the booking toast ('' when nothing changed)
     */
    protected function advanceToProcessingAfterBooking(Order $order): string
    {
        $order->refresh();
        $oldStatus = $order->status;

        if (! in_array($oldStatus, [OrderStatus::PENDING, OrderStatus::CONFIRMED], true)) {
            return '';
        }

        try {
            DB::transaction(function () use ($order, $oldStatus) {
                $order->update(['status' => OrderStatus::PROCESSING]);

                app(StockService::class)->onStatusChange($order->load('items'), $oldStatus, OrderStatus::PROCESSING);
            });
        } catch (InsufficientStockException $e) {
            return " — status not changed to Processing: {$e->getMessage()}";
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->withProperties(['changes' => ['before' => ['status' => $oldStatus->value], 'after' => ['status' => OrderStatus::PROCESSING->value]]])
            ->log("Order #{$order->id} moved to Processing after courier booking");

        // OrderDetail keeps its own status dropdown value — keep it in sync.
        if (property_exists($this, 'orderId') && property_exists($this, 'status') && $this->orderId === $order->id) {
            $this->status = OrderStatus::PROCESSING->value;
        }

        return ' — order moved to Processing';
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
