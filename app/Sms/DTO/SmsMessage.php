<?php

namespace App\Sms\DTO;

class SmsMessage
{
    public function __construct(
        public string $to,
        public string $message,
        public ?string $senderId = null,
        public string $context = 'custom',
        public array $meta = [],
    ) {
    }
}
