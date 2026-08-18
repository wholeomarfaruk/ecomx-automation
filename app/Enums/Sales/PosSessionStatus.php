<?php

namespace App\Enums\Sales;

enum PosSessionStatus: string
{
    case OPEN   = 'open';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN   => 'Open',
            self::CLOSED => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::OPEN   => 'bg-emerald-50 text-emerald-600',
            self::CLOSED => 'bg-gray-100 text-gray-500',
        };
    }
}
