<?php

namespace App\Sms\Exceptions;

class SmsAuthenticationException extends SmsException
{
    public function __construct(array $rawResponse = [])
    {
        parent::__construct('Authentication Failed: Invalid API Key', 'authentication_failed', $rawResponse);
    }
}
