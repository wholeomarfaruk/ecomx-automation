<?php

namespace App\Marketing\Events;

use Carbon\CarbonInterface;

final class Lead extends MarketingEvent
{
    public function __construct(
        string $eventId,
        CarbonInterface $occurredAt,

        public readonly ?string $leadId = null,
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
        ?string $leadId = null,
        ?float $value = null,
        ?string $currency = null,
        array $parameters = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            occurredAt: self::now(),
            leadId: $leadId,
            value: $value,
            currency: $currency,
            parameters: $parameters,
        );
    }

    public function eventName(): string
    {
        return 'Lead';
    }

    public function data(): array
    {
        return [
            'lead_id' => $this->leadId,
            'value' => $this->value,
            'currency' => $this->currency,
        ];
    }
}
