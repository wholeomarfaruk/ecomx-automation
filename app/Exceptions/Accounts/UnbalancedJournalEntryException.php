<?php

namespace App\Exceptions\Accounts;

use RuntimeException;

class UnbalancedJournalEntryException extends RuntimeException
{
    public static function make(float $totalDebit, float $totalCredit): self
    {
        return new self(sprintf(
            'Journal entry is not balanced: total debit %s does not equal total credit %s.',
            number_format($totalDebit, 2),
            number_format($totalCredit, 2),
        ));
    }

    public static function invalidLine(int $index): self
    {
        return new self("Journal entry line #{$index} must have exactly one of debit or credit set, and it must be greater than zero.");
    }

    public static function noLines(): self
    {
        return new self('A journal entry needs at least two lines.');
    }
}
