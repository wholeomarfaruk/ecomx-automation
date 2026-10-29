<?php

namespace App\Enums\Issues;

enum IssueType: string
{
    case COURIER_LOST       = 'courier_lost';
    case COURIER_DAMAGED    = 'courier_damaged';
    case WRONG_ITEM         = 'wrong_item';
    case CUSTOMER_COMPLAINT = 'customer_complaint';
    case SUPPLIER_SHORT     = 'supplier_short';
    case PAYMENT            = 'payment';
    case OTHER              = 'other';

    public function label(): string
    {
        return match ($this) {
            self::COURIER_LOST       => 'Lost by courier',
            self::COURIER_DAMAGED    => 'Damaged in transit',
            self::WRONG_ITEM         => 'Wrong item sent',
            self::CUSTOMER_COMPLAINT => 'Customer complaint',
            self::SUPPLIER_SHORT     => 'Supplier short / faulty',
            self::PAYMENT            => 'Payment problem',
            self::OTHER              => 'Other',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::COURIER_LOST, self::COURIER_DAMAGED => 'bg-rose-50 text-rose-600',
            self::WRONG_ITEM, self::SUPPLIER_SHORT     => 'bg-amber-50 text-amber-700',
            self::CUSTOMER_COMPLAINT                   => 'bg-sky-50 text-sky-600',
            self::PAYMENT                              => 'bg-violet-50 text-violet-600',
            self::OTHER                                => 'bg-gray-100 text-gray-600',
        };
    }
}
