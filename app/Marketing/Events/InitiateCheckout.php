<?php

namespace App\Marketing\Events;

use Carbon\CarbonInterface;

final class InitiateCheckout extends MarketingEvent
{
    public function __construct(
        string $eventId,
        CarbonInterface $occurredAt,

        public readonly ?float $value = null,
        public readonly ?string $currency = null,
        public readonly array $items = [],
        public readonly ?int $itemCount = null,

        array $parameters = [],
    ) {
        parent::__construct(
            eventId: $eventId,
            occurredAt: $occurredAt,
            parameters: $parameters,
        );
    }

    public static function create(
        ?float $value = null,
        ?string $currency = null,
        array $items = [],
        ?int $itemCount = null,
        array $parameters = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            occurredAt: self::now(),
            value: $value,
            currency: $currency,
            items: $items,
            itemCount: $itemCount,
            parameters: $parameters,
        );
    }

    public function eventName(): string
    {
        return 'InitiateCheckout';
    }

    public function data(): array
    {
        return [
            'value' => $this->value,
            'currency' => $this->currency,
            'items' => $this->items,
            'item_count' => $this->itemCount,
        ];
    }
}
