<?php

namespace App\Enums\Profiles;

enum MasterProfileType: string
{
    case INDIVIDUAL   = 'individual';
    case ORGANIZATION = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL   => 'Individual',
            self::ORGANIZATION => 'Organization',
        };
    }
}
