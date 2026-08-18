<?php

namespace App\Enums\Product;

enum ProductType: string
{
    case SIMPLE   = 'simple';
    case VARIABLE = 'variable';
    case COMBO    = 'combo';

    public function label(): string
    {
        return match ($this) {
            self::SIMPLE   => 'Simple',
            self::VARIABLE => 'Variable',
            self::COMBO    => 'Combo',
        };
    }
}
