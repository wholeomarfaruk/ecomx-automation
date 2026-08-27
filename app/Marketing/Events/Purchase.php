<?php

namespace App\Marketing\Events;

use Carbon\CarbonInterface;

final class Purchase extends MarketingEvent
{
    public function __construct(
        string $eventId,
        CarbonInterface $occurredAt,

        public readonly float $value,
        public readonly string $currency,

        public readonly string|int|null $orderId = null,

        public readonly array $items = [],

        array $parameters = [],
    ) {
        parent::__construct(
            eventId: $eventId,
            occurredAt: $occurredAt,
            parameters: $parameters,
        );
    }

    public static function create(
        float $value,
        string $currency,
        string|int|null $orderId = null,
        array $items = [],
        array $parameters = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            occurredAt: self::now(),
            value: $value,
            currency: $currency,
            orderId: $orderId,
            items: $items,
            parameters: $parameters,
        );
    }

    public function eventName(): string
    {
        return 'Purchase';
    }

    public function data(): array
    {
        return [
            'value' => $this->value,
            'currency' => $this->currency,
            'order_id' => $this->orderId,
            'items' => $this->items,
        ];
    }
}
