<?php

namespace App\Courier\DTO;

use App\Enums\Sales\CourierStatus;

class TrackingResponse
{
    /**
     * @param TrackingEvent[] $events oldest first
     */
    public function __construct(
        public bool $success,
        public string $courier,
        public CourierStatus $status = CourierStatus::PENDING,
        public array $events = [],
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $rawResponse = [],
    ) {
    }

    public static function success(string $courier, CourierStatus $status, array $events = [], array $rawResponse = []): self
    {
        return new self(success: true, courier: $courier, status: $status, events: $events, rawResponse: $rawResponse);
    }

    public static function failure(string $courier, string $errorCode, string $errorMessage, array $rawResponse = []): self
    {
        return new self(
            success: false,
            courier: $courier,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse,
        );
    }
}
