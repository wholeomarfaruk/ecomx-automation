<?php

namespace App\Exceptions\Accounts;

use RuntimeException;

class JournalEntryImmutableException extends RuntimeException
{
    public static function alreadyPosted(): self
    {
        return new self('Posted journal entries cannot be edited or deleted — reverse the entry and post a corrected one instead.');
    }

    public static function alreadyVoided(): self
    {
        return new self('This journal entry is already void.');
    }

    public static function notPosted(): self
    {
        return new self('Only posted journal entries can be reversed or voided.');
    }
}
