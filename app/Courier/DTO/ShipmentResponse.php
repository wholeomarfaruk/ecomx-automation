<?php

namespace App\Courier\DTO;

use App\Enums\Sales\CourierStatus;

class ShipmentResponse
{
    public function __construct(
        public bool $success,
        public string $courier,
        public ?string $shipmentId = null,
        public ?string $trackingNumber = null,
        public ?string $consignmentId = null,
        public CourierStatus $status = CourierStatus::PENDING,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $rawResponse = [],
    ) {
    }

    public static function success(
        string $courier,
        ?string $shipmentId = null,
        ?string $trackingNumber = null,
        ?string $consignmentId = null,
        CourierStatus $status = CourierStatus::PENDING,
        array $rawResponse = [],
    ): self {
        return new self(
            success: true,
            courier: $courier,
            shipmentId: $shipmentId,
            trackingNumber: $trackingNumber,
            consignmentId: $consignmentId,
            status: $status,
            rawResponse: $rawResponse,
        );
    }

    public static function failure(string $courier, string $errorCode, string $errorMessage, array $rawResponse = []): self
    {
        return new self(
            success: false,
            courier: $courier,
            status: CourierStatus::FAILED,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'courier' => $this->courier,
            'shipment_id' => $this->shipmentId,
            'tracking_number' => $this->trackingNumber,
            'consignment_id' => $this->consignmentId,
            'status' => $this->status->value,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'raw_response' => $this->rawResponse,
        ];
    }
}
