<?php

namespace App\Jobs;

use App\Models\SmsGatewayConfig;
use App\Sms\DTO\SmsMessage;
use App\Sms\SmsManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public SmsMessage $message,
        public string $driverKey,
    ) {
    }

    public function handle(SmsManager $smsManager): void
    {
        $config = SmsGatewayConfig::forDriver($this->driverKey);
        $this->tries = max(1, $config->retry_attempts + 1);

        $smsManager->sendNow($this->message, $this->driverKey);
    }
}
