<?php

namespace App\Courier\DTO;

/**
 * Generic success/failure envelope every driver method returns — mirrors
 * App\Sms\DTO\SmsResponse so the two gateway systems stay familiar to work
 * with side by side.
 */
class CourierResponse
{
    public function __construct(
        public bool $success,
        public string $courier,
        public array $data = [],
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $rawResponse = [],
    ) {
    }

    public static function success(string $courier, array $data = [], array $rawResponse = []): self
    {
        return new self(success: true, courier: $courier, data: $data, rawResponse: $rawResponse);
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

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'courier' => $this->courier,
            'data' => $this->data,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'raw_response' => $this->rawResponse,
        ];
    }
}
