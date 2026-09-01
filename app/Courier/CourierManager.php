<?php

namespace App\Courier;

use App\Courier\Contracts\CourierDriverInterface;
use App\Courier\DTO\CourierResponse;
use App\Courier\DTO\RateRequest;
use App\Courier\DTO\RateResponse;
use App\Courier\DTO\ShipmentRequest;
use App\Courier\DTO\ShipmentResponse;
use App\Courier\DTO\TrackingResponse;
use App\Courier\Exceptions\CourierException;
use App\Enums\Sales\CourierStatus;
use App\Jobs\CreateCourierShipmentJob;
use App\Models\Courier;
use App\Models\CourierAccount;
use App\Models\CourierApiLog;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Manager;

/**
 * The single entry point Order/admin code talks to — it never knows which
 * courier or account it's actually dealing with. Resolves a courier_key
 * (e.g. "steadfast") to its registered driver class, instantiates it with
 * the right account's credentials, and logs every call to courier_api_logs.
 */
class CourierManager extends Manager
{
    public function getDefaultDriver()
    {
        $default = CourierAccount::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('courier')
            ->first();

        return $default?->courier?->driver_key ?? config('courier.default');
    }

    protected function createDriver($driver)
    {
        $drivers = config('courier.drivers', []);

        if (! isset($drivers[$driver])) {
            throw new \InvalidArgumentException("Courier driver [{$driver}] is not registered in config/courier.php.");
        }

        $account = $this->resolveAccount($driver);

        $class = $drivers[$driver];

        return new $class($account?->credentials ?? [], [
            'timeout' => 30,
        ]);
    }

    public function driverFor(string $key): CourierDriverInterface
    {
        return $this->driver($key);
    }

    protected function resolveAccount(string $driverKey): ?CourierAccount
    {
        return CourierAccount::query()
            ->whereHas('courier', fn ($q) => $q->where('driver_key', $driverKey))
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();
    }

    public function installedCouriers(): array
    {
        return collect(config('courier.drivers', []))
            ->map(fn (string $class) => $class::meta())
            ->values()
            ->all();
    }

    /**
     * Books a shipment respecting the "queue shipment creation" setting —
     * queued, this returns immediately with a queued placeholder response;
     * the real ShipmentResponse only exists once the job runs (check the
     * order/shipment record afterwards for the outcome). Prefer this over
     * calling createShipment() directly from a web request.
     */
    public function bookShipment(Order $order, ?string $courierKey, ShipmentRequest $request): ShipmentResponse
    {
        $courierKey ??= $this->defaultCourierKeyFor($order);

        if (! $courierKey) {
            return ShipmentResponse::failure('unknown', 'no_courier_selected', 'No courier was selected and the customer has no default courier set.');
        }

        if (Setting::get('queue_shipment_creation', true, 'courier')) {
            CreateCourierShipmentJob::dispatch($order->id, $courierKey, $request);

            return ShipmentResponse::success($courierKey, status: CourierStatus::PENDING);
        }

        return $this->createShipment($order, $courierKey, $request);
    }

    /**
     * A customer's preferred courier (Customer.default_courier_id), used to
     * pre-select/highlight that courier when booking a single order, and to
     * resolve each order's courier automatically during bulk shipment creation.
     */
    public function defaultCourierKeyFor(Order $order): ?string
    {
        return $order->customer?->defaultCourier?->driver_key;
    }

    /**
     * Books shipments for many orders at once, each using its own
     * customer's default courier (falling back to $fallbackCourierKey for
     * customers with none set) — never one courier forced on the whole batch.
     *
     * @param  Order[]  $orders
     * @return array<int, ShipmentResponse> keyed by order id
     */
    public function bulkBookShipments(array $orders, callable $requestFactory, ?string $fallbackCourierKey = null): array
    {
        $results = [];

        foreach ($orders as $order) {
            $courierKey = $this->defaultCourierKeyFor($order) ?? $fallbackCourierKey;

            $results[$order->id] = $this->bookShipment($order, $courierKey, $requestFactory($order));
        }

        return $results;
    }

    /**
     * Books a real shipment with the given courier for an order, updating
     * the order's flat courier_* snapshot columns and creating both a
     * courier_shipments row and its first tracking event.
     */
    public function createShipment(Order $order, string $courierKey, ShipmentRequest $request): ShipmentResponse
    {
        $courier = Courier::where('driver_key', $courierKey)->firstOrFail();
        $account = $this->resolveAccount($courierKey);

        if (! $account) {
            return ShipmentResponse::failure($courierKey, 'no_active_account', 'No active account is configured for this courier.');
        }

        $shipment = CourierShipment::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'courier_account_id' => $account->id,
            'status' => 'pending',
            'request_payload' => json_encode($request),
        ]);

        $response = $this->logged(
            $courier,
            $account,
            $shipment,
            'create_shipment',
            fn () => $this->driver($courierKey)->createShipment($request),
            fn (string $code, string $message, array $raw) => ShipmentResponse::failure($courierKey, $code, $message, $raw),
        );

        $shipment->update([
            'shipment_id' => $response->shipmentId,
            'tracking_number' => $response->trackingNumber,
            'consignment_id' => $response->consignmentId,
            'status' => $response->status->value,
            'response_payload' => json_encode($response->rawResponse),
            'error_message' => $response->success ? null : $response->errorMessage,
        ]);

        if ($response->success) {
            $shipment->trackingEvents()->create([
                'status' => $response->status->value,
                'message' => 'Shipment created.',
                'event_at' => now(),
                'raw_data' => json_encode($response->rawResponse),
            ]);

            $order->forceFill([
                'courier_provider' => $courierKey,
                'courier_tracking_number' => $response->trackingNumber,
                'courier_status' => $response->status,
                'courier_meta' => array_merge($order->courier_meta ?? [], [
                    'shipment_id' => $response->shipmentId,
                    'consignment_id' => $response->consignmentId,
                    'courier_shipment_id' => $shipment->id,
                ]),
                'courier_status_updated_at' => now(),
            ])->save();
        }

        return $response;
    }

    public function cancelShipment(CourierShipment $shipment): CourierResponse
    {
        $courierKey = $shipment->courier->driver_key;

        $response = $this->logged(
            $shipment->courier,
            $shipment->courierAccount,
            $shipment,
            'cancel_shipment',
            fn () => $this->driver($courierKey)->cancelShipment($shipment->tracking_number),
            fn (string $code, string $message, array $raw) => CourierResponse::failure($courierKey, $code, $message, $raw),
        );

        if ($response->success) {
            $shipment->update([
                'previous_status' => $shipment->status,
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $shipment->trackingEvents()->create([
                'status' => 'cancelled',
                'message' => 'Shipment cancelled.',
                'event_at' => now(),
            ]);

            $shipment->order->forceFill([
                'courier_status' => CourierStatus::CANCELLED,
                'courier_status_updated_at' => now(),
            ])->save();
        }

        return $response;
    }

    public function syncTracking(CourierShipment $shipment): TrackingResponse
    {
        $courierKey = $shipment->courier->driver_key;

        $response = $this->logged(
            $shipment->courier,
            $shipment->courierAccount,
            $shipment,
            'sync_tracking',
            fn () => $this->driver($courierKey)->getTracking($shipment->tracking_number),
            fn (string $code, string $message, array $raw) => TrackingResponse::failure($courierKey, $code, $message, $raw),
        );

        if ($response->success) {
            foreach ($response->events as $event) {
                $shipment->trackingEvents()->create([
                    'status' => $event->status->value,
                    'raw_status' => $event->rawStatus,
                    'message' => $event->message,
                    'location' => $event->location,
                    'event_at' => $event->eventAt ?? now(),
                    'raw_data' => json_encode($event->rawData),
                ]);
            }

            $shipment->update([
                'previous_status' => $shipment->status,
                'status' => $response->status->value,
            ]);

            $shipment->order->forceFill([
                'courier_status' => $response->status,
                'courier_status_updated_at' => now(),
            ])->save();
        }

        return $response;
    }

    public function calculateRate(string $courierKey, RateRequest $request): RateResponse
    {
        try {
            return $this->driver($courierKey)->calculateRate($request);
        } catch (CourierException $e) {
            return RateResponse::failure($courierKey, $e->errorCode, $e->getMessage(), $e->rawResponse);
        }
    }

    public function balance(string $courierKey): CourierResponse
    {
        try {
            return $this->driver($courierKey)->getBalance();
        } catch (CourierException $e) {
            return CourierResponse::failure($courierKey, $e->errorCode, $e->getMessage(), $e->rawResponse);
        }
    }

    public function test(string $courierKey): CourierResponse
    {
        try {
            return $this->driver($courierKey)->testConnection();
        } catch (CourierException $e) {
            return CourierResponse::failure($courierKey, $e->errorCode, $e->getMessage(), $e->rawResponse);
        }
    }

    public function status(string $courierKey): array
    {
        return $this->driver($courierKey)->getStatus();
    }

    /**
     * Runs a driver call, timing it and writing one courier_api_logs row
     * regardless of outcome — shared by every manager method above so no
     * call to a courier's API ever goes unlogged. $onException builds the
     * right *Response::failure() for the caller's return type since PHP
     * has no way to infer that from a generic callable's return.
     */
    protected function logged(
        Courier $courier,
        ?CourierAccount $account,
        CourierShipment $shipment,
        string $action,
        callable $call,
        callable $onException,
    ) {
        $startedAt = microtime(true);

        try {
            $response = $call();
            $success = $response->success;
            $raw = $response->rawResponse;
            $errorCode = $success ? null : $response->errorCode;
            $errorMessage = $success ? null : $response->errorMessage;
        } catch (CourierException $e) {
            $response = $onException($e->errorCode, $e->getMessage(), $e->rawResponse);
            $success = false;
            $raw = $e->rawResponse;
            $errorCode = $e->errorCode;
            $errorMessage = $e->getMessage();
        }

        CourierApiLog::create([
            'courier_id' => $courier->id,
            'courier_account_id' => $account?->id,
            'courier_shipment_id' => $shipment->id,
            'action' => $action,
            'success' => $success,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'response_payload' => json_encode($raw),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }
}
