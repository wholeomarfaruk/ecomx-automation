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
 * SteadFast Courier Limited (steadfast.com.bd) REST API.
 * Docs: https://docs.google.com/document/d/16TCbTIFcPuaLuMcS6dvIhTOP2gTdSMcp29-4jZKdxWM
 *
 * Auth is two static headers, not OAuth — Api-Key/Secret-Key from the
 * merchant panel (Settings > API Support), not the portal login email/password.
 */
class SteadFastDriver implements CourierDriverInterface
{
    protected const BASE_URL = 'https://portal.packzy.com/api/v1';

    protected string $apiKey;
    protected string $secretKey;
    protected int $timeout;

    public function __construct(array $credentials, array $options = [])
    {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->secretKey = $credentials['secret_key'] ?? '';
        $this->timeout = $options['timeout'] ?? 30;
    }

    protected function client()
    {
        return Http::timeout($this->timeout)
            ->baseUrl(self::BASE_URL)
            ->withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ]);
    }

    public function createShipment(ShipmentRequest $request): ShipmentResponse
    {
        if ($request->type === ShipmentType::EXCHANGE) {
            // SteadFast's public docs don't currently expose a verified
            // exchange-order field/endpoint we can confirm against a live
            // test — refusing rather than guessing field names and risking
            // a wrongly-booked shipment against a real merchant account.
            throw new CourierException(
                'SteadFast exchange-parcel booking is not yet wired up — verify the exact API fields against SteadFast\'s current docs before enabling this.',
                'not_supported',
            );
        }

        try {
            $response = $this->client()->post('/create_order', [
                'invoice' => $request->invoiceNumber,
                'recipient_name' => $request->recipientName,
                'recipient_phone' => $request->recipientPhone,
                'recipient_address' => $request->recipientAddress,
                'cod_amount' => $request->codAmount,
                'note' => $request->specialInstruction,
            ]);
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($this->errorMessageFrom($response, $data), $this->rawResponseArray($response, $data));
        }

        if (! $response->successful() || ($data['status'] ?? null) !== 200) {
            throw new CourierException($this->errorMessageFrom($response, $data, 'Failed to create SteadFast shipment.'), 'shipment_create_failed', $this->rawResponseArray($response, $data));
        }

        $consignment = $data['consignment'] ?? [];

        return ShipmentResponse::success(
            courier: 'steadfast',
            shipmentId: (string) ($consignment['consignment_id'] ?? ''),
            trackingNumber: $consignment['tracking_code'] ?? null,
            consignmentId: isset($consignment['consignment_id']) ? (string) $consignment['consignment_id'] : null,
            status: $this->normalizeStatus($consignment['status'] ?? 'pending'),
            rawResponse: $data,
        );
    }

    public function cancelShipment(string $trackingNumber): CourierResponse
    {
        // SteadFast's public API has no documented cancel-shipment endpoint —
        // cancellation is done from their merchant portal. Surface that
        // clearly instead of pretending to support it.
        throw new CourierException(
            'SteadFast does not support cancelling a shipment via API — cancel it from the SteadFast merchant portal.',
            'not_supported',
        );
    }

    public function getTracking(string $trackingNumber): TrackingResponse
    {
        try {
            $response = $this->client()->get("/status_by_trackingcode/{$trackingNumber}");
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($this->errorMessageFrom($response, $data), $this->rawResponseArray($response, $data));
        }

        if (! $response->successful() || ($data['status'] ?? null) !== 200) {
            throw new CourierException($this->errorMessageFrom($response, $data, 'Failed to fetch SteadFast tracking.'), 'tracking_failed', $this->rawResponseArray($response, $data));
        }

        $rawStatus = $data['delivery_status'] ?? 'pending';
        $status = $this->normalizeStatus($rawStatus);

        return TrackingResponse::success(
            courier: 'steadfast',
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
        if ($trackingCode = ($payload['tracking_code'] ?? $payload['consignment_id'] ?? null)) {
            return ['by' => 'tracking_number', 'value' => $trackingCode];
        }

        if ($invoice = ($payload['invoice'] ?? null)) {
            return ['by' => 'order_id', 'value' => $invoice];
        }

        return null;
    }

    public function parseWebhookEvent(array $payload): ?TrackingEvent
    {
        $rawStatus = $payload['status'] ?? $payload['delivery_status'] ?? null;

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
        // SteadFast's public API has no documented rate-calculation
        // endpoint — their COD/delivery charges are fixed per plan.
        throw new CourierException(
            'SteadFast does not expose a rate-calculation API.',
            'not_supported',
        );
    }

    public function getBalance(): CourierResponse
    {
        try {
            $response = $this->client()->get('/get_balance');
        } catch (\Throwable $e) {
            throw new CourierGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        if ($response->status() === 401 || $response->status() === 403) {
            throw new CourierAuthenticationException($this->errorMessageFrom($response, $data), $this->rawResponseArray($response, $data));
        }

        if (! $response->successful() || ($data['status'] ?? null) !== 200) {
            throw new CourierException($this->errorMessageFrom($response, $data, 'Failed to fetch SteadFast balance.'), 'balance_check_failed', $this->rawResponseArray($response, $data));
        }

        return CourierResponse::success('steadfast', ['balance' => $data['current_balance'] ?? null], $data);
    }

    public function testConnection(): CourierResponse
    {
        return $this->getBalance();
    }

    public function validateCredentials(): CourierResponse
    {
        if ($this->apiKey === '' || $this->secretKey === '') {
            throw new CourierAuthenticationException('SteadFast API Key and Secret Key are required.');
        }

        return $this->getBalance();
    }

    public function getStatus(): array
    {
        try {
            $this->getBalance();

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
            CourierCapability::BALANCE->value => true,
            CourierCapability::EXCHANGE->value => false,
        ];
    }

    public static function meta(): array
    {
        return [
            'key' => 'steadfast',
            'label' => 'SteadFast Courier',
            'version' => '1.0',
            'fields' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'text', 'required' => true],
                ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password', 'required' => true],
            ],
        ];
    }

    /**
     * SteadFast returns error bodies inconsistently — sometimes JSON
     * ({"message": "..."}), sometimes a bare text/html string like
     * "Account is not active!" that $response->json() silently turns into
     * null. Falling straight back to a generic message in that case hides
     * the real, actionable reason (account not approved, bad IP allowlist,
     * etc.) from whoever's debugging a failed booking.
     */
    protected function errorMessageFrom(Response $response, array $data, string $fallback = 'SteadFast authentication failed.'): string
    {
        if (! empty($data['message'])) {
            return $data['message'];
        }

        $body = trim($response->body());

        return $body !== '' ? $body : $fallback;
    }

    /**
     * What gets stored as this call's rawResponse (and from there, the
     * courier_api_logs.response_payload row) — always includes the HTTP
     * status and raw body text, not just the JSON-decoded array, so a
     * non-JSON error response (see errorMessageFrom() above) is never
     * logged as an empty [] with no way to see what SteadFast actually sent.
     */
    protected function rawResponseArray(Response $response, array $data): array
    {
        return array_merge($data, [
            'http_status' => $response->status(),
            'raw_body' => $response->body(),
        ]);
    }

    /**
     * SteadFast's own status vocabulary (from /create_order and the
     * tracking endpoints) mapped onto our canonical CourierStatus.
     */
    protected function normalizeStatus(string $rawStatus): CourierStatus
    {
        return CourierStatusNormalizer::normalize($rawStatus, [
            'pending' => CourierStatus::PENDING,
            'in_review' => CourierStatus::PENDING,
            'delivered_approval_pending' => CourierStatus::OUT_FOR_DELIVERY,
            'partial_delivered_approval_pending' => CourierStatus::OUT_FOR_DELIVERY,
            'cancelled_approval_pending' => CourierStatus::PENDING,
            'unknown_approval_pending' => CourierStatus::PENDING,
            'delivered' => CourierStatus::DELIVERED,
            'partial_delivered' => CourierStatus::DELIVERED,
            'cancelled' => CourierStatus::CANCELLED,
            'hold' => CourierStatus::IN_TRANSIT,
            'in_transit' => CourierStatus::IN_TRANSIT,
            'unknown' => CourierStatus::FAILED,
        ]);
    }
}
