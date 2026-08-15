<?php

namespace App\Sms\Exceptions;

class SmsBalanceException extends SmsException
{
    public function __construct(array $rawResponse = [])
    {
        parent::__construct('Insufficient Balance', 'insufficient_balance', $rawResponse);
    }
}
