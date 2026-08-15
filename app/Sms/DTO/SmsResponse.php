<?php

namespace App\Sms\DTO;

class SmsResponse
{
    public function __construct(
        public bool $success,
        public string $provider,
        public ?string $messageId = null,
        public string $status = 'failed',
        public ?float $cost = null,
        public ?float $remainingBalance = null,
        public array $providerResponse = [],
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $rawResponse = [],
    ) {
    }

    public static function success(
        string $provider,
        ?string $messageId = null,
        string $status = 'sent',
        ?float $cost = null,
        ?float $remainingBalance = null,
        array $providerResponse = [],
        array $rawResponse = [],
    ): self {
        return new self(
            success: true,
            provider: $provider,
            messageId: $messageId,
            status: $status,
            cost: $cost,
            remainingBalance: $remainingBalance,
            providerResponse: $providerResponse,
            rawResponse: $rawResponse,
        );
    }

    public static function failure(
        string $provider,
        string $errorCode,
        string $errorMessage,
        array $rawResponse = [],
    ): self {
        return new self(
            success: false,
            provider: $provider,
            status: 'failed',
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'provider' => $this->provider,
            'message_id' => $this->messageId,
            'status' => $this->status,
            'cost' => $this->cost,
            'remaining_balance' => $this->remainingBalance,
            'provider_response' => $this->providerResponse,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'raw_response' => $this->rawResponse,
        ];
    }
}
