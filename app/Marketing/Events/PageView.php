<?php

namespace App\Marketing\Events;

use Carbon\CarbonInterface;

final class PageView extends MarketingEvent
{
    public function __construct(
        string $eventId,
        CarbonInterface $occurredAt,
        array $parameters = [],
    ) {
        parent::__construct(
            eventId: $eventId,
            occurredAt: $occurredAt,
            parameters: $parameters,
        );
    }

    public static function create(
        array $parameters = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            occurredAt: self::now(),
            parameters: $parameters,
        );
    }

    public function eventName(): string
    {
        return 'PageView';
    }
}
