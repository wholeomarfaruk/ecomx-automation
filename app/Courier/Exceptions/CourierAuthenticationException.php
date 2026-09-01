<?php

namespace App\Courier\Exceptions;

class CourierAuthenticationException extends CourierException
{
    public function __construct(string $message = 'Courier authentication failed.', array $rawResponse = [])
    {
        parent::__construct($message, 'authentication_failed', $rawResponse);
    }
}
