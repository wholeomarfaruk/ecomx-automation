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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Pathao Courier & Parcel (merchant.pathao.com) "Aladdin" API.
 * Docs: https://merchant.pathao.com/courier/developer or the PDF Pathao
 * issues to registered merchants — endpoints below match that spec.
 *
 * Auth is OAuth2 password-grant, unlike SteadFast's static header keys:
 * client_id/client_secret/username/password exchange for a short-lived
 * access token, which is what's actually sent with every request after.
 * The token is cached per-account (keyed by client_id) so we don't
 * re-authenticate on every single API call.
 */
class PathaoDriver implements CourierDriverInterface
{
    protected const BASE_URL = 'https://api-hermes.pathao.com';

    protected string $clientId;
    protected string $clientSecret;
    protected string $username;
    protected string $password;
    protected ?int $storeId;
    protected int $timeout;

    public function __construct(array $credentials, array $options = [])
    {
        $this->clientId = $credentials['client_id'] ?? '';
        $this->clientSecret = $credentials['client_secret'] ?? '';
        $this->username = $credentials['username'] ?? '';
        $this->password = $credentials['password'] ?? '';
        $this->storeId = isset($credentials['store_id']) && $credentials['store_id'] !== ''
            ? (int) $credentials['store_id']
            : null;
        $this->timeout = $options['timeout'] ?? 30;
    }

    protected function accessToken(): string
    {
        $cacheKey = 'courier:pathao:token:' . md5($this->clientId . $this->username);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            try {
                $response = Http::timeout($this->timeout)
                    ->baseUrl(self::BASE_URL)
                    ->post('/aladdin/api/v1/issue-token', [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'username' => $this->username,
                        'password' => $this->password,
                        'grant_type' => 'password',
                    ]);
            } catch (\Throwable $e) {
                throw new CourierGatewayUnavailableException($e->getMessage());
            }

            $data = $response->json() ?? [];

            if (! $response->successful() || empty($data['access_token'])) {
                throw new CourierAuthenticationException($data['message'] ?? 'Pathao authentication failed.', $data);
            }

            return $data['access_token'];
        });
    }

    protected function client()
    {
        return Http::timeout($this->timeout)
            ->baseUrl(self::BASE_URL)
            ->withToken($this->accessToken())
            ->acceptJson();
    }

    /**
     * Pathao requires a numeric store_id (one merchant account can have
     * multiple pickup stores) — resolved once and cached, since credentials
     * only carry it if the admin filled it in explicitly.
     */
    protected function resolveStoreId(): int
    {
        if ($this->storeId) {
            return $this->storeId;
        }

        try {
            $response = $this->client()->get('/aladdin/api/v1/stores');
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];
        $store = $data['data']['data'][0] ?? null;

        if (! $store) {
            throw new CourierException('No Pathao store found on this account — add one from the Pathao merchant panel first.', 'no_store_configured', $data);
        }

        return $this->storeId = $store['store_id'];
    }

    public function createShipment(ShipmentRequest $request): ShipmentResponse
    {
        if ($request->type === ShipmentType::EXCHANGE) {
            throw new CourierException(
                'Pathao exchange-parcel booking is not yet wired up — verify the exact API fields against Pathao\'s current merchant API docs before enabling this.',
                'not_supported',
            );
        }

        $cityId = $request->meta['pathao_city_id'] ?? 1;
        $zoneId = $request->meta['pathao_zone_id'] ?? 1;
        $areaId = $request->meta['pathao_area_id'] ?? 1;

        if (! $cityId || ! $zoneId) {
            throw new CourierException(
                'Pathao requires a resolved city/zone (and usually area) id for the recipient address — pass pathao_city_id/pathao_zone_id/pathao_area_id in ShipmentRequest::$meta.',
                'address_not_resolved',
            );
        }

        try {
            $response = $this->client()->post('/aladdin/api/v1/orders', [
                'store_id' => $this->resolveStoreId(),
                'merchant_order_id' => $request->orderId,
                'recipient_name' => $request->recipientName,
                'recipient_phone' => $request->recipientPhone,
                'recipient_address' => $request->recipientAddress,
                'recipient_city' => $cityId,
                'recipient_zone' => $zoneId,
                'recipient_area' => $areaId,
                'delivery_type' => 48, // Normal Delivery
                'item_type' => 2, // Parcel
                'special_instruction' => $request->specialInstruction,
                'item_quantity' => $request->itemQuantity ?? 1,
                'item_weight' => $request->itemWeight ?? 0.5,
                'item_description' => $request->itemDescription,
                'amount_to_collect' => $request->codAmount,
            ]);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($data['message'] ?? 'Pathao authentication failed.', $data);
        }

        if (! $response->successful()) {
            throw new CourierException($data['message'] ?? 'Failed to create Pathao shipment.', 'shipment_create_failed', $data);
        }

        $order = $data['data'] ?? [];

        return ShipmentResponse::success(
            courier: 'pathao',
            shipmentId: (string) ($order['consignment_id'] ?? ''),
            trackingNumber: $order['consignment_id'] ?? null,
            consignmentId: $order['consignment_id'] ?? null,
            status: $this->normalizeStatus($order['order_status'] ?? 'pending'),
            rawResponse: $data,
        );
    }

    public function cancelShipment(string $trackingNumber): CourierResponse
    {
        throw new CourierException(
            'Pathao does not expose a cancel-shipment endpoint in its public merchant API — cancel it from the Pathao merchant portal.',
            'not_supported',
        );
    }

    public function getTracking(string $trackingNumber): TrackingResponse
    {
        try {
            $response = $this->client()->get("/aladdin/api/v1/orders/{$trackingNumber}/info");
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($data['message'] ?? 'Pathao authentication failed.', $data);
        }

        if (! $response->successful()) {
            throw new CourierException($data['message'] ?? 'Failed to fetch Pathao tracking.', 'tracking_failed', $data);
        }

        $order = $data['data'] ?? [];
        $rawStatus = $order['order_status'] ?? 'pending';
        $status = $this->normalizeStatus($rawStatus);

        return TrackingResponse::success(
            courier: 'pathao',
            status: $status,
            events: [
                new TrackingEvent(
                    status: $status,
                    rawStatus: $rawStatus,
                    message: str_replace('_', ' ', ucfirst($rawStatus)),
                    eventAt: Carbon::now(),
                    rawData: $data,
                ),
            ],
            rawResponse: $data,
        );
    }

    public function webhookIdentifier(array $payload): ?array
    {
        if ($consignmentId = ($payload['consignment_id'] ?? null)) {
            return ['by' => 'tracking_number', 'value' => $consignmentId];
        }

        if ($orderId = ($payload['merchant_order_id'] ?? null)) {
            return ['by' => 'order_id', 'value' => $orderId];
        }

        return null;
    }

    public function parseWebhookEvent(array $payload): ?TrackingEvent
    {
        $rawStatus = $payload['order_status'] ?? $payload['event'] ?? null;

        if (! $rawStatus) {
            return null;
        }

        return new TrackingEvent(
            status: $this->normalizeStatus($rawStatus),
            rawStatus: $rawStatus,
            message: 'Webhook update.',
            eventAt: Carbon::now(),
            rawData: $payload,
        );
    }

    public function calculateRate(RateRequest $request): RateResponse
    {
        // Pathao's price-plan endpoint needs resolved city/zone ids the
        // same way createShipment does — not wired up until the
        // city/zone/area resolution UI exists in the admin panel.
        throw new CourierException(
            'Pathao rate calculation requires resolved city/zone ids — not yet wired up.',
            'not_supported',
        );
    }

    public function getBalance(): CourierResponse
    {
        // Pathao's merchant API has no documented balance-check endpoint —
        // COD is settled to the merchant's bank account on their schedule.
        throw new CourierException(
            'Pathao does not expose a balance-check API.',
            'not_supported',
        );
    }

    public function testConnection(): CourierResponse
    {
        $storeId = $this->resolveStoreId();

        return CourierResponse::success('pathao', ['store_id' => $storeId]);
    }

    public function validateCredentials(): CourierResponse
    {
        if ($this->clientId === '' || $this->clientSecret === '' || $this->username === '' || $this->password === '') {
            throw new CourierAuthenticationException('Pathao Client ID, Client Secret, Username, and Password are all required.');
        }

        // Forces a fresh token request rather than reusing a cached one.
        Cache::forget('courier:pathao:token:' . md5($this->clientId . $this->username));

        $this->accessToken();

        return CourierResponse::success('pathao', ['message' => 'Credentials are valid.']);
    }

    public function getStatus(): array
    {
        try {
            $this->accessToken();

            return ['online' => true, 'last_checked_at' => now()->toIso8601String()];
        } catch (\Throwable $e) {
            return ['online' => false, 'last_checked_at' => now()->toIso8601String()];
        }
    }

    public function capabilities(): array
    {
        return [
            CourierCapability::SHIPMENT_CREATE->value => true,
            CourierCapability::SHIPMENT_CANCEL->value => false,
            CourierCapability::TRACKING->value => true,
            CourierCapability::STATUS_SYNC->value => true,
            CourierCapability::RATE_CALCULATION->value => false,
            CourierCapability::COD->value => true,
            CourierCapability::RETURN->value => false,
            CourierCapability::PICKUP_REQUEST->value => false,
            CourierCapability::WEBHOOK->value => true,
            CourierCapability::LABEL->value => false,
            CourierCapability::BALANCE->value => false,
            CourierCapability::EXCHANGE->value => false,
        ];
    }

    public static function meta(): array
    {
        return [
            'key' => 'pathao',
            'label' => 'Pathao Courier',
            'version' => '1.0',
            'fields' => [
                ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
                ['key' => 'username', 'label' => 'Username (email)', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
                ['key' => 'store_id', 'label' => 'Store ID (optional — auto-detected if left blank)', 'type' => 'text', 'required' => false],
            ],
        ];
    }

    /**
     * Pathao's order_status vocabulary mapped onto our canonical CourierStatus.
     */
    protected function normalizeStatus(string $rawStatus): CourierStatus
    {
        return CourierStatusNormalizer::normalize($rawStatus, [
            'pending' => CourierStatus::PENDING,
            'pickup_requested' => CourierStatus::PENDING,
            'assigned_for_pickup' => CourierStatus::PENDING,
            'picked' => CourierStatus::PICKED_UP,
            'at_the_sorting_hub' => CourierStatus::IN_TRANSIT,
            'in_transit' => CourierStatus::IN_TRANSIT,
            'received_at_last_mile_hub' => CourierStatus::IN_TRANSIT,
            'assigned_for_delivery' => CourierStatus::OUT_FOR_DELIVERY,
            'delivered' => CourierStatus::DELIVERED,
            'partial_delivery' => CourierStatus::DELIVERED,
            'return' => CourierStatus::RETURNED,
            'returned' => CourierStatus::RETURNED,
            'cancelled' => CourierStatus::CANCELLED,
            'delivery_failed' => CourierStatus::FAILED,
        ]);
    }
}
