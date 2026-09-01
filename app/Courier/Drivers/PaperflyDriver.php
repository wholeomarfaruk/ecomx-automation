<?php

namespace App\Courier\Drivers;

use App\Courier\Contracts\CourierDriverInterface;
use App\Courier\CourierStatusNormalizer;
use App\Courier\DTO\CourierResponse;
use App\Courier\DTO\RateRequest;
use App\Courier\DTO\RateResponse;
use App\Courier\DTO\ShipmentRequest;
use App\Courier\DTO\ShipmentResponse;
use App\Courier\DTO\TrackingEvent;
use App\Courier\DTO\TrackingResponse;
use App\Courier\Enums\CourierCapability;
use App\Courier\Enums\ShipmentType;
use App\Courier\Exceptions\CourierAuthenticationException;
use App\Courier\Exceptions\CourierException;
use App\Courier\Exceptions\CourierGatewayUnavailableException;
use App\Enums\Sales\CourierStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Paperfly (paperfly.com.bd) merchant API — built from Paperfly's own
 * developer documentation (order creation, tracking, cancel, webhooks with
 * full exchange-parcel support), NOT verified against a live account —
 * no Paperfly credentials were available to test with, unlike SteadFast/
 * Pathao/RedX which were all confirmed against real responses before
 * being trusted. Test every method here via Accounts > Test before
 * activating this courier for real orders.
 *
 * Auth is HTTP Basic (merchant panel username/password) PLUS a static
 * "paperflykey" header — Paperfly is the only courier in this system that
 * combines both, so credentials carry three distinct secrets: username,
 * password, and a separate API key.
 */
class PaperflyDriver implements CourierDriverInterface
{
    protected const BASE_URL = 'https://api.paperfly.com.bd';

    protected string $username;
    protected string $password;
    protected string $paperflyKey;
    protected string $storeName;
    protected int $timeout;

    public function __construct(array $credentials, array $options = [])
    {
        $this->username = $credentials['username'] ?? '';
        $this->password = $credentials['password'] ?? '';
        $this->paperflyKey = $credentials['paperfly_key'] ?? '';
        $this->storeName = $credentials['store_name'] ?? '';
        $this->timeout = $options['timeout'] ?? 30;
    }

    protected function client()
    {
        return Http::timeout($this->timeout)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'paperflykey' => $this->paperflyKey,
                'Content-Type' => 'application/json',
            ]);
    }

    public function createShipment(ShipmentRequest $request): ShipmentResponse
    {
        $payload = [
            'merchantOrderReference' => $request->orderId,
            'storeName' => $this->storeName,
            'productBrief' => $request->itemDescription ?? 'Order #' . $request->orderId,
            'packagePrice' => (string) $request->codAmount,
            'max_weight' => (string) ($request->itemWeight ?? 0.5),
            'customerName' => $request->recipientName,
            'customerAddress' => $request->recipientAddress,
            'customerPhone' => $request->recipientPhone,
        ];

        if ($request->type === ShipmentType::EXCHANGE) {
            $payload['orderType'] = 'Exchange';
            $payload['exchangeDescription'] = $request->exchangeItemDescription ?? $request->itemDescription ?? 'Exchange item';
            $payload['exchangePrice'] = (string) $request->codAmount;
            $payload['exchangeWeight'] = (string) ($request->itemWeight ?? 0.5);
        }

        try {
            $response = $this->client()->post(self::BASE_URL . '/merchant/api/service/new_order_v2.php', $payload);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($data['message'] ?? 'Paperfly authentication failed.', $data);
        }

        if (! $response->successful() || empty($data['success']['tracking_number'])) {
            throw new CourierException($data['error']['message'] ?? $data['message'] ?? 'Failed to create Paperfly order.', 'shipment_create_failed', $data);
        }

        $result = $data['success'];

        return ShipmentResponse::success(
            courier: 'paperfly',
            shipmentId: $result['tracking_number'],
            trackingNumber: $result['tracking_number'],
            consignmentId: $result['tracking_barcode'] ?? null,
            status: CourierStatus::PENDING,
            rawResponse: $data,
        );
    }

    public function cancelShipment(string $trackingNumber): CourierResponse
    {
        try {
            $response = $this->client()->post(self::BASE_URL . '/api/v1/cancel-order', [
                'order_id' => $trackingNumber,
            ]);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($data['message'] ?? 'Paperfly authentication failed.', $data);
        }

        $message = $data['success']['message'] ?? null;

        if (! $response->successful() || ! $message) {
            throw new CourierException($data['error']['message'] ?? $data['message'] ?? 'Failed to cancel Paperfly order.', 'cancel_failed', $data);
        }

        return CourierResponse::success('paperfly', ['message' => $message], $data);
    }

    public function getTracking(string $trackingNumber): TrackingResponse
    {
        try {
            $response = $this->client()->post(self::BASE_URL . '/API-Order-Tracking', [
                'ReferenceNumber' => $trackingNumber,
            ]);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($data['message'] ?? 'Paperfly authentication failed.', $data);
        }

        if (! $response->successful() || empty($data['success']['trackingStatus'])) {
            throw new CourierException($data['error']['message'] ?? $data['message'] ?? 'Failed to fetch Paperfly tracking.', 'tracking_failed', $data);
        }

        $stage = $data['success']['trackingStatus'][0] ?? [];

        // Paperfly's tracking response is a flat set of named stage
        // columns (Pick/PickTime, inTransit/inTransitTime, Delivered/
        // DeliveredTime, ...) rather than a status list — each populated
        // pair becomes one event, in the fixed pipeline order Paperfly
        // documents its own delivery flow in.
        $stages = [
            'Pick' => 'PickTime',
            'inTransit' => 'inTransitTime',
            'ReceivedAtPoint' => 'ReceivedAtPointTime',
            'PickedForDelivery' => 'PickedForDeliveryTime',
            'Delivered' => 'DeliveredTime',
            'Partial' => 'PartialTime',
            'Returned' => 'ReturnedTime',
            'close' => 'closeTime',
        ];

        $events = [];

        foreach ($stages as $stageKey => $timeKey) {
            if (! empty($stage[$stageKey])) {
                $events[] = new TrackingEvent(
                    status: $this->normalizeStatus($stageKey),
                    rawStatus: $stageKey,
                    message: $stage[$stageKey],
                    eventAt: ! empty($stage[$timeKey]) ? Carbon::parse($stage[$timeKey]) : null,
                    rawData: $stage,
                );
            }
        }

        $latestStatus = ! empty($events) ? end($events)->status : CourierStatus::PENDING;

        return TrackingResponse::success(
            courier: 'paperfly',
            status: $latestStatus,
            events: $events,
            rawResponse: $data,
        );
    }

    /**
     * Paperfly's tracking-stage column names mapped onto our canonical
     * CourierStatus — distinct from the webhook event vocabulary below.
     */
    protected function normalizeStatus(string $stageKey): CourierStatus
    {
        return CourierStatusNormalizer::normalize($stageKey, [
            'pick' => CourierStatus::PICKED_UP,
            'intransit' => CourierStatus::IN_TRANSIT,
            'receivedatpoint' => CourierStatus::IN_TRANSIT,
            'pickedfordelivery' => CourierStatus::OUT_FOR_DELIVERY,
            'delivered' => CourierStatus::DELIVERED,
            'partial' => CourierStatus::DELIVERED,
            'returned' => CourierStatus::RETURNED,
            'close' => CourierStatus::DELIVERED,
        ]);
    }

    public function webhookIdentifier(array $payload): ?array
    {
        $data = $payload['data'] ?? $payload;

        if ($orderNumber = ($data['order_number'] ?? null)) {
            return ['by' => 'tracking_number', 'value' => $orderNumber];
        }

        if ($reference = ($data['merchant_order_reference'] ?? null)) {
            return ['by' => 'order_id', 'value' => $reference];
        }

        return null;
    }

    public function parseWebhookEvent(array $payload): ?TrackingEvent
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if (! $event) {
            return null;
        }

        return new TrackingEvent(
            status: $this->normalizeWebhookEvent($event),
            rawStatus: $event,
            message: $data['hold_reason'] ?? $data['return_reason'] ?? null,
            eventAt: isset($payload['timestamp']) ? Carbon::parse($payload['timestamp']) : Carbon::now(),
            rawData: $payload,
        );
    }

    /**
     * Paperfly's documented webhook event names mapped onto our canonical
     * CourierStatus. See the "Available Events" section of their docs —
     * parcel.created through parcel.return_to_merchant.
     */
    protected function normalizeWebhookEvent(string $event): CourierStatus
    {
        return CourierStatusNormalizer::normalize($event, [
            'parcel.created' => CourierStatus::PENDING,
            'parcel.invoiced' => CourierStatus::PENDING,
            'parcel.cancelled' => CourierStatus::CANCELLED,
            'parcel.picked_up' => CourierStatus::PICKED_UP,
            'parcel.in_transit' => CourierStatus::IN_TRANSIT,
            'parcel.received_at_point' => CourierStatus::IN_TRANSIT,
            'parcel.assigned_for_delivery' => CourierStatus::OUT_FOR_DELIVERY,
            'parcel.delivered' => CourierStatus::DELIVERED,
            'parcel.partial' => CourierStatus::DELIVERED,
            'parcel.exchange' => CourierStatus::DELIVERED,
            'parcel.on_hold' => CourierStatus::IN_TRANSIT,
            'parcel.return' => CourierStatus::RETURNED,
            'parcel.return_transit' => CourierStatus::RETURNED,
            'parcel.return_to_merchant' => CourierStatus::RETURNED,
        ]);
    }

    public function calculateRate(RateRequest $request): RateResponse
    {
        throw new CourierException(
            'Paperfly does not expose a rate-calculation API in its documented endpoints.',
            'not_supported',
        );
    }

    public function getBalance(): CourierResponse
    {
        throw new CourierException(
            'Paperfly does not expose a balance-check API in its documented endpoints.',
            'not_supported',
        );
    }

    public function testConnection(): CourierResponse
    {
        // Paperfly has no dedicated health-check endpoint documented — a
        // tracking lookup on a bogus reference exercises auth without side
        // effects. Both a bad-credentials rejection and a genuine "not
        // found" come back as HTTP 400 with no distinct status code, so
        // the two are told apart by the error message text instead.
        try {
            $response = $this->client()->post(self::BASE_URL . '/API-Order-Tracking', [
                'ReferenceNumber' => '__connection_test__',
            ]);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];
        $errorMessage = $data['error']['message'] ?? '';

        if ($response->status() === 401
            || $response->status() === 403
            || str_contains(strtolower($errorMessage), 'credential')
            || str_contains(strtolower($errorMessage), 'api key')
        ) {
            throw new CourierAuthenticationException($errorMessage ?: 'Paperfly authentication failed.', $data);
        }

        return CourierResponse::success('paperfly', ['message' => 'Connection successful.']);
    }

    public function validateCredentials(): CourierResponse
    {
        if ($this->username === '' || $this->password === '' || $this->paperflyKey === '') {
            throw new CourierAuthenticationException('Paperfly Username, Password, and Paperfly Key are all required.');
        }

        return $this->testConnection();
    }

    public function getStatus(): array
    {
        try {
            $this->testConnection();

            return ['online' => true, 'last_checked_at' => now()->toIso8601String()];
        } catch (\Throwable $e) {
            return ['online' => false, 'last_checked_at' => now()->toIso8601String()];
        }
    }

    public function capabilities(): array
    {
        return [
            CourierCapability::SHIPMENT_CREATE->value => true,
            CourierCapability::SHIPMENT_CANCEL->value => true,
            CourierCapability::TRACKING->value => true,
            CourierCapability::STATUS_SYNC->value => true,
            CourierCapability::RATE_CALCULATION->value => false,
            CourierCapability::COD->value => true,
            CourierCapability::RETURN->value => true,
            CourierCapability::PICKUP_REQUEST->value => false,
            CourierCapability::WEBHOOK->value => true,
            CourierCapability::LABEL->value => false,
            CourierCapability::BALANCE->value => false,
            CourierCapability::EXCHANGE->value => true,
        ];
    }

    public static function meta(): array
    {
        return [
            'key' => 'paperfly',
            'label' => 'Paperfly',
            'version' => '1.0',
            'fields' => [
                ['key' => 'username', 'label' => 'Merchant Panel Username', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Merchant Panel Password', 'type' => 'password', 'required' => true],
                ['key' => 'paperfly_key', 'label' => 'Paperfly Key (paperflykey header)', 'type' => 'password', 'required' => true],
                ['key' => 'store_name', 'label' => 'Store Name', 'type' => 'text', 'required' => true],
            ],
        ];
    }
}
