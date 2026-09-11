<?php

namespace App\Enums\Accounts;

enum JournalEntryStatus: string
{
    case DRAFT  = 'draft';
    case POSTED = 'posted';
    case VOID   = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT  => 'Draft',
            self::POSTED => 'Posted',
            self::VOID   => 'Void',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT  => 'bg-gray-100 text-gray-600',
            self::POSTED => 'bg-emerald-50 text-emerald-600',
            self::VOID   => 'bg-red-50 text-red-500',
        };
    }
}
