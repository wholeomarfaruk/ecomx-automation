<?php

namespace App\Courier\DTO;

class RateResponse
{
    public function __construct(
        public bool $success,
        public string $courier,
        public ?float $deliveryCharge = null,
        public ?float $codCharge = null,
        public ?float $totalCharge = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $rawResponse = [],
    ) {
    }

    public static function success(string $courier, ?float $deliveryCharge = null, ?float $codCharge = null, ?float $totalCharge = null, array $rawResponse = []): self
    {
        return new self(
            success: true,
            courier: $courier,
            deliveryCharge: $deliveryCharge,
            codCharge: $codCharge,
            totalCharge: $totalCharge ?? (($deliveryCharge ?? 0) + ($codCharge ?? 0)),
            rawResponse: $rawResponse,
        );
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
