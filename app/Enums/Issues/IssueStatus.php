<?php

namespace App\Enums\Issues;

enum IssueStatus: string
{
    case OPEN        = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED    = 'resolved';
    case CLOSED      = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN        => 'Open',
            self::IN_PROGRESS => 'In progress',
            self::RESOLVED    => 'Resolved',
            self::CLOSED      => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::OPEN        => 'bg-red-50 text-red-600',
            self::IN_PROGRESS => 'bg-amber-50 text-amber-700',
            self::RESOLVED    => 'bg-emerald-50 text-emerald-600',
            self::CLOSED      => 'bg-gray-100 text-gray-500',
        };
    }

    /** Still needs someone to act on it. */
    public function isActive(): bool
    {
        return $this === self::OPEN || $this === self::IN_PROGRESS;
    }

    /** @return list<string> */
    public static function activeValues(): array
    {
        return [self::OPEN->value, self::IN_PROGRESS->value];
    }
}
