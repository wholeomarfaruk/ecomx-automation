<?php

namespace App\Courier\Contracts;

use App\Courier\DTO\CourierResponse;
use App\Courier\DTO\RateRequest;
use App\Courier\DTO\RateResponse;
use App\Courier\DTO\ShipmentRequest;
use App\Courier\DTO\ShipmentResponse;
use App\Courier\DTO\TrackingEvent;
use App\Courier\DTO\TrackingResponse;

/**
 * Every courier driver (Pathao, SteadFast, RedX, ...) implements this same
 * shape so CourierManager and the admin UI never need to know which
 * courier they're actually talking to — see CourierCapability for how a
 * driver opts out of methods its provider's API doesn't support instead of
 * being forced to fake them.
 */
interface CourierDriverInterface
{
    public function __construct(array $credentials, array $options = []);

    public function createShipment(ShipmentRequest $request): ShipmentResponse;

    public function cancelShipment(string $trackingNumber): CourierResponse;

    public function getTracking(string $trackingNumber): TrackingResponse;

    /**
     * Given an inbound webhook's raw decoded payload, identifies which
     * shipment it refers to, so CourierWebhookController stays
     * courier-agnostic instead of branching per driver_key.
     *
     * @return array{by: 'tracking_number'|'order_id', value: string}|null null if the payload carries no identifier this driver recognizes
     */
    public function webhookIdentifier(array $payload): ?array;

    /**
     * Given the same raw payload, builds the normalized TrackingEvent it
     * represents — null if the payload has no recognizable status.
     */
    public function parseWebhookEvent(array $payload): ?TrackingEvent;

    public function calculateRate(RateRequest $request): RateResponse;

    public function getBalance(): CourierResponse;

    public function testConnection(): CourierResponse;

    public function validateCredentials(): CourierResponse;

    /**
     * @return array{online: bool, last_checked_at: ?string}
     */
    public function getStatus(): array;

    /**
     * Which CourierCapability values this driver actually implements —
     * drives which admin-UI actions are shown/enabled for this courier.
     *
     * @return array<string, bool>
     */
    public function capabilities(): array;

    /**
     * Static metadata used for auto-discovery in the admin panel.
     *
     * @return array{key: string, label: string, version: string, fields: array}
     */
    public static function meta(): array;
}
