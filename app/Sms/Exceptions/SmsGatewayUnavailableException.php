<?php

namespace App\Sms\Exceptions;

class SmsGatewayUnavailableException extends SmsException
{
    public function __construct(string $message = 'Gateway Timeout', array $rawResponse = [])
    {
        parent::__construct($message, 'gateway_unavailable', $rawResponse);
    }
}
