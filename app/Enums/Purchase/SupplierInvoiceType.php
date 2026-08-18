<?php

namespace App\Enums\Purchase;

enum SupplierInvoiceType: string
{
    case PURCHASE = 'purchase';
    case ADVANCE  = 'advance';
    case PAYMENT  = 'payment';
    case RETURN   = 'return';

    /**
     * Purchase/return increase what is owed to the supplier (debit);
     * advance/payment reduce it (credit).
     */
    public function isDebit(): bool
    {
        return match ($this) {
            self::PURCHASE, self::RETURN => true,
            self::ADVANCE, self::PAYMENT => false,
        };
    }

    public function isCredit(): bool
    {
        return ! $this->isDebit();
    }

    public function signedAmount(float $amount): float
    {
        return $this->isDebit() ? $amount : -$amount;
    }

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Purchase',
            self::ADVANCE  => 'Advance',
            self::PAYMENT  => 'Payment',
            self::RETURN   => 'Return',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PURCHASE => 'bg-indigo-50 text-indigo-600',
            self::ADVANCE  => 'bg-blue-50 text-blue-600',
            self::PAYMENT  => 'bg-emerald-50 text-emerald-600',
            self::RETURN   => 'bg-red-50 text-red-500',
        };
    }
}
