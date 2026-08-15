<?php

namespace App\Enums\User;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::BANNED => 'Banned',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
