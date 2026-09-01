<?php

namespace App\Courier\DTO;

use App\Courier\Enums\ShipmentType;

/**
 * Everything a driver needs to book a shipment, assembled once from the
 * Order + its shipping address so drivers never touch Eloquent models
 * directly — keeps drivers testable and decoupled from the Order schema.
 *
 * `type: exchange` books an exchange parcel — the courier collects the
 * item(s) described in $exchangeItemDescription from the customer and
 * delivers the new item in the same trip, instead of a plain drop-off. A
 * driver whose provider doesn't support exchange orders should throw
 * CourierException('not_supported') rather than silently booking a normal one.
 */
class ShipmentRequest
{
    public function __construct(
        public string $orderId,
        public string $invoiceNumber,
        public string $recipientName,
        public string $recipientPhone,
        public string $recipientAddress,
        public ?string $recipientCity = null,
        public ?string $recipientZone = null,
        public ?string $recipientArea = null,
        public float $codAmount = 0,
        public ?float $itemWeight = null,
        public ?int $itemQuantity = null,
        public ?string $itemDescription = null,
        public ?string $specialInstruction = null,
        public ShipmentType $type = ShipmentType::NORMAL,
        public ?string $exchangeItemDescription = null,
        public array $meta = [],
    ) {
    }
}
