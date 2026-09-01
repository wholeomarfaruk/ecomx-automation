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
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * RedX OpenAPI (redx.com.bd/developer-api). Verified against the sandbox
 * environment (sandbox.redx.com.bd) — /areas, /pickup/stores, and
 * /charge/charge_calculator all confirmed live before this driver was
 * written. Production base URL is openapi.redx.com.bd, same paths.
 *
 * Auth is a static long-lived JWT issued per merchant from the RedX
 * merchant panel, sent as "API-ACCESS-TOKEN: Bearer <token>" — note the
 * non-standard header name, RedX does not use a plain Authorization header.
 */
class RedXDriver implements CourierDriverInterface
{
    protected string $baseUrl;
    protected string $token;
    protected ?int $pickupStoreId;
    protected int $timeout;

    public function __construct(array $credentials, array $options = [])
    {
        $this->token = $credentials['token'] ?? '';
        $this->baseUrl = ($credentials['environment'] ?? 'production') === 'sandbox'
            ? 'https://sandbox.redx.com.bd/v1.0.0-beta'
            : 'https://openapi.redx.com.bd/v1.0.0-beta';
        $this->pickupStoreId = isset($credentials['pickup_store_id']) && $credentials['pickup_store_id'] !== ''
            ? (int) $credentials['pickup_store_id']
            : null;
        $this->timeout = $options['timeout'] ?? 30;
    }

    protected function client()
    {
        return Http::timeout($this->timeout)
            ->baseUrl($this->baseUrl)
            ->withHeaders([
                'API-ACCESS-TOKEN' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ]);
    }

    public function createShipment(ShipmentRequest $request): ShipmentResponse
    {
        $areaId = $request->meta['redx_delivery_area_id'] ?? null;
        $areaName = $request->recipientArea ?? $request->recipientZone ?? $request->recipientCity;

        if (! $areaId) {
            throw new CourierException(
                'RedX requires a resolved delivery_area_id for the recipient address — pass redx_delivery_area_id in ShipmentRequest::$meta (resolve it via getDeliverableAreas()).',
                'address_not_resolved',
            );
        }

        if (! $this->pickupStoreId) {
            throw new CourierException(
                'No RedX pickup store is configured for this account — set pickup_store_id in the account credentials.',
                'no_store_configured',
            );
        }

        $payload = [
            'customer_name' => $request->recipientName,
            'customer_phone' => $request->recipientPhone,
            'delivery_area' => $areaName,
            'delivery_area_id' => $areaId,
            'customer_address' => $request->recipientAddress,
            'merchant_invoice_id' => $request->orderId,
            'cash_collection_amount' => (string) $request->codAmount,
            'parcel_weight' => (string) (($request->itemWeight ?? 0.5) * 1000), // kg -> grams
            'instruction' => $request->specialInstruction,
            'value' => (string) 0,
            'pickup_store_id' => $this->pickupStoreId,
        ];

        if ($request->type === ShipmentType::EXCHANGE) {
            // RedX documents "exchange-delivery" as a real delivery_type for
            // the forward leg of an exchange parcel (see the Delivery Type
            // Reference Table in their docs) — the courier collects the old
            // item and drops the new one in the same trip.
            $payload['type'] = 'exchange-delivery';

            if ($request->exchangeItemDescription) {
                $payload['instruction'] = trim(
                    ($payload['instruction'] ? $payload['instruction'] . ' | ' : '')
                    . 'Exchange: ' . $request->exchangeItemDescription
                );
            }
        }

        try {
            $response = $this->client()->post('/parcel', $payload);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($this->errorMessageFrom($response, $data), $this->rawResponseArray($response, $data));
        }

        if (! $response->successful() || empty($data['tracking_id'])) {
            throw new CourierException($this->errorMessageFrom($response, $data, 'Failed to create RedX parcel.'), 'shipment_create_failed', $this->rawResponseArray($response, $data));
        }

        return ShipmentResponse::success(
            courier: 'redx',
            shipmentId: $data['tracking_id'],
            trackingNumber: $data['tracking_id'],
            consignmentId: $data['tracking_id'],
            status: CourierStatus::PENDING,
            rawResponse: $data,
        );
    }

    public function cancelShipment(string $trackingNumber): CourierResponse
    {
        try {
            $response = $this->client()->patch('/parcels', [
                'entity_type' => 'parcel-tracking-id',
                'entity_id' => $trackingNumber,
                'update_details' => [
                    'property_name' => 'status',
                    'new_value' => 'cancelled',
                    'reason' => 'Cancelled from admin panel.',
                ],
            ]);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($this->errorMessageFrom($response, $data), $this->rawResponseArray($response, $data));
        }

        // RedX's gateway has been observed returning a non-2xx HTTP status
        // (e.g. 503) alongside a valid {"success":true,...} body — trust the
        // body's own success flag first, since it's the more reliable signal.
        if (($data['success'] ?? null) !== true) {
            throw new CourierException($this->errorMessageFrom($response, $data, 'Failed to cancel RedX parcel.'), 'cancel_failed', $this->rawResponseArray($response, $data));
        }

        return CourierResponse::success('redx', ['message' => $data['message'] ?? 'ok'], $data);
    }

    public function getTracking(string $trackingNumber): TrackingResponse
    {
        try {
            $response = $this->client()->get("/parcel/track/{$trackingNumber}");
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($this->errorMessageFrom($response, $data), $this->rawResponseArray($response, $data));
        }

        if (! $response->successful()) {
            throw new CourierException($this->errorMessageFrom($response, $data, 'Failed to fetch RedX tracking.'), 'tracking_failed', $this->rawResponseArray($response, $data));
        }

        $entries = $data['tracking'] ?? [];

        $events = array_map(fn (array $entry) => new TrackingEvent(
            status: $this->normalizeStatus($entry['message_en'] ?? ''),
            rawStatus: $entry['message_en'] ?? null,
            message: $entry['message_en'] ?? null,
            eventAt: isset($entry['time']) ? Carbon::parse($entry['time']) : null,
            rawData: $entry,
        ), $entries);

        $latestStatus = ! empty($events) ? end($events)->status : CourierStatus::PENDING;

        return TrackingResponse::success(
            courier: 'redx',
            status: $latestStatus,
            events: $events,
            rawResponse: $data,
        );
    }

    /**
     * RedX's sandbox has been observed returning a non-JSON body on some
     * gateway-level failures — fall back to the raw text rather than a
     * generic message when there's no parsed {message} field.
     */
    protected function errorMessageFrom(Response $response, array $data, string $fallback = 'RedX authentication failed.'): string
    {
        if (! empty($data['message'])) {
            return $data['message'];
        }

        $body = trim($response->body());

        return $body !== '' ? $body : $fallback;
    }

    /**
     * What gets stored as this call's rawResponse (and from there,
     * courier_api_logs.response_payload) — always includes the HTTP status
     * and raw body text, not just the JSON-decoded array, so a non-JSON
     * error response is never logged as an empty [] with no way to see
     * what RedX actually sent.
     */
    protected function rawResponseArray(Response $response, array $data): array
    {
        return array_merge($data, [
            'http_status' => $response->status(),
            'raw_body' => $response->body(),
        ]);
    }

    /**
     * RedX's tracking feed carries free-text messages, not a fixed status
     * enum (that's only defined for the webhook payload's `status` field) —
     * so this matches on the message content instead of an exact key.
     */
    protected function normalizeStatus(string $message): CourierStatus
    {
        $message = strtolower($message);

        return match (true) {
            str_contains($message, 'delivered') => CourierStatus::DELIVERED,
            str_contains($message, 'picked up') => CourierStatus::PICKED_UP,
            str_contains($message, 'returned') => CourierStatus::RETURNED,
            str_contains($message, 'cancel') => CourierStatus::CANCELLED,
            str_contains($message, 'out for delivery') || str_contains($message, 'dispatch') => CourierStatus::OUT_FOR_DELIVERY,
            str_contains($message, 'transit') || str_contains($message, 'hub') => CourierStatus::IN_TRANSIT,
            str_contains($message, 'created') || str_contains($message, 'placed') => CourierStatus::PENDING,
            default => CourierStatus::PENDING,
        };
    }

    public function webhookIdentifier(array $payload): ?array
    {
        if ($trackingNumber = ($payload['tracking_number'] ?? null)) {
            return ['by' => 'tracking_number', 'value' => $trackingNumber];
        }

        if ($invoice = ($payload['invoice_number'] ?? null)) {
            return ['by' => 'order_id', 'value' => $invoice];
        }

        return null;
    }

    public function parseWebhookEvent(array $payload): ?TrackingEvent
    {
        $rawStatus = $payload['status'] ?? null;

        if (! $rawStatus) {
            return null;
        }

        return new TrackingEvent(
            status: $this->normalizeWebhookStatus($rawStatus),
            rawStatus: $rawStatus,
            message: $payload['message_en'] ?? null,
            eventAt: isset($payload['timestamp']) ? Carbon::parse($payload['timestamp']) : Carbon::now(),
            rawData: $payload,
        );
    }

    /**
     * RedX's documented webhook status vocabulary — distinct from the
     * free-text tracking feed normalizeStatus() above.
     */
    protected function normalizeWebhookStatus(string $rawStatus): CourierStatus
    {
        return CourierStatusNormalizer::normalize($rawStatus, [
            'ready-for-delivery' => CourierStatus::PENDING,
            'delivery-in-progress' => CourierStatus::OUT_FOR_DELIVERY,
            'delivered' => CourierStatus::DELIVERED,
            'agent-hold' => CourierStatus::IN_TRANSIT,
            'agent-returning' => CourierStatus::IN_TRANSIT,
            'returned' => CourierStatus::RETURNED,
            'agent-area-change' => CourierStatus::IN_TRANSIT,
            'paid' => CourierStatus::DELIVERED,
        ]);
    }

    public function calculateRate(RateRequest $request): RateResponse
    {
        $deliveryAreaId = $request->meta['redx_delivery_area_id'] ?? null;
        $pickupAreaId = $request->meta['redx_pickup_area_id'] ?? null;

        if (! $deliveryAreaId || ! $pickupAreaId) {
            throw new CourierException(
                'RedX rate calculation requires resolved delivery_area_id and pickup_area_id — pass them in RateRequest::$meta.',
                'address_not_resolved',
            );
        }

        try {
            $response = $this->client()->get('/charge/charge_calculator', [
                'delivery_area_id' => $deliveryAreaId,
                'pickup_area_id' => $pickupAreaId,
                'cash_collection_amount' => $request->codAmount,
                'weight' => $request->weight * 1000, // kg -> grams
            ]);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if (! $response->successful()) {
            throw new CourierException($this->errorMessageFrom($response, $data, 'Failed to calculate RedX rate.'), 'rate_calculation_failed', $this->rawResponseArray($response, $data));
        }

        return RateResponse::success(
            courier: 'redx',
            deliveryCharge: $data['deliveryCharge'] ?? null,
            codCharge: $data['codCharge'] ?? null,
            rawResponse: $data,
        );
    }

    public function getBalance(): CourierResponse
    {
        // RedX's public OpenAPI has no documented balance-check endpoint —
        // COD is settled to the merchant's account on their own cycle.
        throw new CourierException(
            'RedX does not expose a balance-check API.',
            'not_supported',
        );
    }

    public function testConnection(): CourierResponse
    {
        try {
            $response = $this->client()->get('/areas');
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        if (! $response->successful()) {
            $data = $response->json() ?? [];

            throw new CourierAuthenticationException($this->errorMessageFrom($response, $data), $this->rawResponseArray($response, $data));
        }

        return CourierResponse::success('redx', ['message' => 'Connection successful.']);
    }

    public function validateCredentials(): CourierResponse
    {
        if ($this->token === '') {
            throw new CourierAuthenticationException('RedX API token is required.');
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
            CourierCapability::RATE_CALCULATION->value => true,
            CourierCapability::COD->value => true,
            CourierCapability::RETURN->value => true,
            CourierCapability::PICKUP_REQUEST->value => false,
            CourierCapability::WEBHOOK->value => true,
            CourierCapability::LABEL->value => false,
            CourierCapability::BALANCE->value => false,
            CourierCapability::EXCHANGE->value => true,
        ];
    }

    /**
     * Every deliverable area RedX serves — used by the admin UI to resolve
     * a free-text recipient address into the delivery_area_id createShipment()
     * and calculateRate() require.
     */
    public function getDeliverableAreas(): CourierResponse
    {
        try {
            $response = $this->client()->get('/areas');
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if (! $response->successful()) {
            throw new CourierException($this->errorMessageFrom($response, $data, 'Failed to fetch RedX areas.'), 'areas_fetch_failed', $this->rawResponseArray($response, $data));
        }

        return CourierResponse::success('redx', ['areas' => $data['areas'] ?? []], $data);
    }

    public static function meta(): array
    {
        return [
            'key' => 'redx',
            'label' => 'RedX',
            'version' => '1.0',
            'fields' => [
                ['key' => 'token', 'label' => 'API Access Token', 'type' => 'password', 'required' => true],
                ['key' => 'pickup_store_id', 'label' => 'Pickup Store ID', 'type' => 'text', 'required' => true],
                ['key' => 'environment', 'label' => 'Environment (production or sandbox)', 'type' => 'text', 'required' => false],
            ],
        ];
    }
}
