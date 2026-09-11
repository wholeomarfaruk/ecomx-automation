<?php

namespace App\Exceptions\Accounts;

use RuntimeException;

class DuplicateJournalEntryException extends RuntimeException
{
    public static function forSource(string $sourceType, int $sourceId, string $purpose): self
    {
        return new self("A journal entry already exists for {$sourceType} #{$sourceId} (\"{$purpose}\") — refusing to post a duplicate.");
    }
}
