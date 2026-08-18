<?php

namespace App\Enums\Sales;

enum OrderSource: string
{
    case WEBSITE   = 'website';
    case MESSENGER = 'messenger';
    case WHATSAPP  = 'whatsapp';
    case POS       = 'pos';
    case ADMIN     = 'admin';
    case API       = 'api';

    public function label(): string
    {
        return match ($this) {
            self::WEBSITE   => 'Website',
            self::MESSENGER => 'Messenger',
            self::WHATSAPP  => 'WhatsApp',
            self::POS       => 'POS',
            self::ADMIN     => 'Admin',
            self::API       => 'API',
        };
    }
}
