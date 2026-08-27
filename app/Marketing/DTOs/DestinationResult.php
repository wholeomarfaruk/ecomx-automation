<?php

namespace App\Marketing\DTOs;

final readonly class DestinationResult
{
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $externalEventId = null,
        public ?int $httpStatus = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public bool $retryable = false,
    ) {}

    public static function success(?string $externalEventId = null, ?int $httpStatus = null): self
    {
        return new self(
            success: true,
            status: 'success',
            externalEventId: $externalEventId,
            httpStatus: $httpStatus,
        );
    }

    public static function failed(
        ?int $httpStatus = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        bool $retryable = false,
    ): self {
        return new self(
            success: false,
            status: 'failed',
            httpStatus: $httpStatus,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            retryable: $retryable,
        );
    }

    public static function skipped(string $reason): self
    {
        return new self(
            success: false,
            status: 'skipped',
            errorMessage: $reason,
        );
    }
}
