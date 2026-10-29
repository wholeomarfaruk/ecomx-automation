<?php

namespace App\Enums\Issues;

enum IssuePriority: string
{
    case LOW    = 'low';
    case NORMAL = 'normal';
    case HIGH   = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW    => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH   => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW    => 'bg-gray-100 text-gray-500',
            self::NORMAL => 'bg-sky-50 text-sky-600',
            self::HIGH   => 'bg-orange-50 text-orange-600',
            self::URGENT => 'bg-red-100 text-red-700',
        };
    }
}
