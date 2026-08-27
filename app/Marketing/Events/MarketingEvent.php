<?php

namespace App\Marketing\Events;

use App\Marketing\Contracts\EventContract;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

abstract class MarketingEvent implements EventContract
{
    public function __construct(
        public readonly string $eventId,
        public readonly CarbonInterface $occurredAt,
        public readonly array $data = [],
        public readonly array $parameters = [],
    ) {}

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredAt(): CarbonInterface
    {
        return $this->occurredAt;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    protected static function generateEventId(): string
    {
        return (string) Str::uuid();
    }

    protected static function now(): CarbonInterface
    {
        return CarbonImmutable::now();
    }
}
