<?php

namespace App\Marketing\Events;

use Carbon\CarbonInterface;

final class Search extends MarketingEvent
{
    public function __construct(
        string $eventId,
        CarbonInterface $occurredAt,

        public readonly string $searchString,
        public readonly ?string $currency = null,
        // Results shown for the term: item_id (catalog id), item_name, price.
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
        string $searchString,
        ?string $currency = null,
        array $items = [],
        array $parameters = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            occurredAt: self::now(),
            searchString: $searchString,
            currency: $currency,
            items: $items,
            parameters: $parameters,
        );
    }

    public function eventName(): string
    {
        return 'Search';
    }

    public function data(): array
    {
        return [
            'search_string' => $this->searchString,
            'currency' => $this->currency,
            'items' => $this->items,
        ];
    }
}
