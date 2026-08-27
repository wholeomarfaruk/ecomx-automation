<?php

namespace App\Marketing\Events;

use Carbon\CarbonInterface;

final class ViewContent extends MarketingEvent
{
    public function __construct(
        string $eventId,
        CarbonInterface $occurredAt,

        public readonly string|int|null $contentId = null,
        public readonly ?string $contentName = null,
        public readonly ?string $contentType = null,
        public readonly ?float $value = null,
        public readonly ?string $currency = null,

        array $parameters = [],
    ) {
        parent::__construct(
            eventId: $eventId,
            occurredAt: $occurredAt,
            parameters: $parameters,
        );
    }

    public static function create(
        string|int|null $contentId = null,
        ?string $contentName = null,
        ?string $contentType = null,
        ?float $value = null,
        ?string $currency = null,
        array $parameters = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            occurredAt: self::now(),
            contentId: $contentId,
            contentName: $contentName,
            contentType: $contentType,
            value: $value,
            currency: $currency,
            parameters: $parameters,
        );
    }

    public function eventName(): string
    {
        return 'ViewContent';
    }

    public function data(): array
    {
        return [
            'content_id' => $this->contentId,
            'content_name' => $this->contentName,
            'content_type' => $this->contentType,
            'value' => $this->value,
            'currency' => $this->currency,
        ];
    }
}
