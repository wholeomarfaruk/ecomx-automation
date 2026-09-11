<?php

namespace App\Exceptions\Accounts;

use RuntimeException;

class LockedFiscalPeriodException extends RuntimeException
{
    public static function forDate(string $date): self
    {
        return new self("Cannot post a journal entry dated {$date} — its accounting period is locked.");
    }
}
