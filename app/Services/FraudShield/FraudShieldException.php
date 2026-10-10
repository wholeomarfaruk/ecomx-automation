<?php

namespace App\Services\FraudShield;

use RuntimeException;

class FraudShieldException extends RuntimeException
{
    /** $blocked: limit hit / key rejected — every other number would fail too, so stop the queue. */
    public function __construct(string $message = '', int $code = 0, public readonly bool $blocked = false)
    {
        parent::__construct($message, $code);
    }
}
