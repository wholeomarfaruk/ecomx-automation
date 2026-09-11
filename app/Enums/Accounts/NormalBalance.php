<?php

namespace App\Enums\Accounts;

enum NormalBalance: string
{
    case DEBIT  = 'debit';
    case CREDIT = 'credit';

    /**
     * Signed effect of a debit/credit pair on an account with this normal
     * balance — mirrors SupplierInvoiceType::signedAmount(), generalized to
     * a real debit+credit pair instead of a single signed amount.
     */
    public function signedDelta(float $debit, float $credit): float
    {
        return $this === self::DEBIT ? $debit - $credit : $credit - $debit;
    }

    public function label(): string
    {
        return match ($this) {
            self::DEBIT  => 'Debit',
            self::CREDIT => 'Credit',
        };
    }
}
