<?php

namespace App\Courier\Exceptions;

class CourierGatewayUnavailableException extends CourierException
{
    public function __construct(string $message = 'Courier gateway is unavailable.', array $rawResponse = [])
    {
        parent::__construct($message, 'gateway_unavailable', $rawResponse);
    }
}
