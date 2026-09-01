<?php

namespace App\Courier\Exceptions;

use Exception;

class CourierException extends Exception
{
    public function __construct(
        string $message,
        public string $errorCode = 'unknown_provider_error',
        public array $rawResponse = [],
    ) {
        parent::__construct($message);
    }
}
