<?php

namespace App\Marketing\Contracts;

use Carbon\CarbonInterface;

interface EventContract
{
    public function eventName(): string;

    public function eventId(): string;

    public function occurredAt(): CarbonInterface;
}
