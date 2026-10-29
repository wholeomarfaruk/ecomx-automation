<?php

namespace App\Actions\Sales\Exchanges;

use App\Courier\CourierManager;
use App\Courier\DTO\ShipmentRequest;
use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\ExchangeStatus;
use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\OrderExchange;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Puts an exchange on a courier parcel of its own (courier_shipments.type =
 * 'exchange', merchant_ref = "268-EX1"), either:
 *
 *  - book(): through the courier's API, as a normal delivery whose
 *    instruction tells the rider to collect the old goods — no integrated
 *    driver books a real "exchange" parcel type yet; or
 *  - attach(): a parcel already booked on the courier's own panel (e.g. a
 *    Pathao exchange parcel), by its consignment / tracking ID, so the
 *    courier's webhooks find it by that ID.
 *
 * Either way the parcel never touches the order's own courier snapshot or
 * status (see ApplyCourierStatus / SyncExchangeFromCourier).
 */
class ShipExchange
{
    public function __construct(protected CourierManager $courierManager) {}

    /** @return string  the tracking number */
    public function book(OrderExchange $exchange, Courier $courier, float $cod, float $weight, int $quantity, ?string $description, ?string $instruction): string
    {
        $this->guard($exchange);

        $order = $exchange->order()->with('shippingAddress')->firstOrFail();
        $address = $order->shippingAddress;
        $reference = $exchange->reference();

        $request = new ShipmentRequest(
            orderId: $reference,
            invoiceNumber: $reference,
            recipientName: (string) ($address->name ?? ''),
            recipientPhone: (string) ($address->phone ?? ''),
            recipientAddress: (string) ($address->full_address ?? ''),
            codAmount: max(0.0, $cod),
            itemWeight: $weight,
            itemQuantity: max(1, $quantity),
            itemDescription: $description ?: null,
            specialInstruction: $instruction ?: null,
        );

        $response = $this->courierManager->createShipment($order, $courier->driver_key, $request, 'exchange');

        if (! $response->success) {
            throw new DomainException($response->errorMessage ?? 'The courier did not accept the parcel.');
        }

        $shipment = CourierShipment::where('order_id', $order->id)->where('type', 'exchange')
            ->where('merchant_ref', $reference)->latest('id')->firstOrFail();

        $this->link($exchange, $shipment, "booked with {$courier->name} (tracking {$response->trackingNumber})");

        return (string) $response->trackingNumber;
    }

    public function attach(OrderExchange $exchange, Courier $courier, string $trackingNumber, float $cod): CourierShipment
    {
        $this->guard($exchange);

        $trackingNumber = trim($trackingNumber);
        $account = $courier->activeAccount();

        if ($trackingNumber === '') {
            throw new DomainException('Enter the consignment / tracking ID.');
        }

        if (! $account) {
            throw new DomainException("{$courier->name} has no active account — add one in Courier Settings first.");
        }

        // Webhooks find a parcel by this ID — it must belong to one live parcel only.
        $existing = CourierShipment::where('tracking_number', $trackingNumber)->get();

        if ($existing->contains(fn (CourierShipment $s) => $s->status !== CourierStatus::CANCELLED->value)) {
            throw new DomainException("Tracking {$trackingNumber} is already on another parcel.");
        }

        // The same parcel removed from this exchange earlier — put it back rather than duplicate it.
        $revive = $existing->first(fn (CourierShipment $s) => (int) $s->order_id === (int) $exchange->order_id && $s->isExchange());

        return DB::transaction(function () use ($exchange, $courier, $account, $trackingNumber, $cod, $revive) {
            if ($revive) {
                $revive->update([
                    'courier_id'         => $courier->id,
                    'courier_account_id' => $account->id,
                    'merchant_ref'       => $exchange->reference(),
                    'previous_status'    => $revive->status,
                    'status'             => CourierStatus::PENDING->value,
                    'cod_amount'         => max(0.0, $cod),
                    'cancelled_at'       => null,
                ]);
                $revive->trackingEvents()->create([
                    'status'   => CourierStatus::PENDING->value,
                    'message'  => 'Exchange parcel attached again.',
                    'event_at' => now(),
                ]);
                $this->link($exchange, $revive, "attached again: {$courier->name} parcel {$trackingNumber}");

                return $revive;
            }

            $shipment = CourierShipment::create([
                'order_id'           => $exchange->order_id,
                'courier_id'         => $courier->id,
                'courier_account_id' => $account->id,
                'type'               => 'exchange',
                'shipment_id'        => $trackingNumber,
                // Pathao's webhooks carry the consignment ID — it's the tracking number too.
                'tracking_number'    => $trackingNumber,
                'consignment_id'     => $trackingNumber,
                'merchant_ref'       => $exchange->reference(),
                'status'             => CourierStatus::PENDING->value,
                'cod_amount'         => max(0.0, $cod),
            ]);

            $shipment->trackingEvents()->create([
                'status'   => CourierStatus::PENDING->value,
                'message'  => 'Exchange parcel attached (booked on the courier panel).',
                'event_at' => now(),
            ]);

            $this->link($exchange, $shipment, "attached: {$courier->name} parcel {$trackingNumber}");

            return $shipment;
        });
    }

    /**
     * Takes a not-yet-picked-up parcel off the exchange (attached by mistake,
     * or booked and then cancelled on the courier's own panel) — the parcel
     * is marked cancelled here only; it is not cancelled at the courier.
     */
    public function detach(OrderExchange $exchange): void
    {
        if (! $exchange->status->canDetach()) {
            throw new DomainException("Exchange #{$exchange->number}'s parcel is already moving — it can't be removed.");
        }

        DB::transaction(function () use ($exchange) {
            $shipment = $exchange->shipment;

            if ($shipment && $shipment->status !== CourierStatus::CANCELLED->value) {
                $shipment->update(['previous_status' => $shipment->status, 'status' => CourierStatus::CANCELLED->value, 'cancelled_at' => now()]);
                $shipment->trackingEvents()->create([
                    'status'   => CourierStatus::CANCELLED->value,
                    'message'  => 'Removed from the exchange in the admin (not cancelled at the courier).',
                    'event_at' => now(),
                ]);
            }

            $exchange->update(['courier_shipment_id' => null, 'status' => ExchangeStatus::REQUESTED]);

            activity('sales')->causedBy(auth()->user())->performedOn($exchange->order)->event('updated')
                ->log("Exchange #{$exchange->number} parcel " . ($shipment?->tracking_number ?? '') . ' removed');
        });
    }

    protected function guard(OrderExchange $exchange): void
    {
        if (! $exchange->status->canShip()) {
            throw new DomainException("Exchange #{$exchange->number} is {$exchange->status->label()} — it already has a parcel or is closed.");
        }
    }

    protected function link(OrderExchange $exchange, CourierShipment $shipment, string $what): void
    {
        $exchange->update(['courier_shipment_id' => $shipment->id, 'status' => ExchangeStatus::BOOKED]);

        activity('sales')->causedBy(auth()->user())->performedOn($exchange->order)->event('updated')
            ->log("Exchange #{$exchange->number} parcel {$what}");
    }
}
