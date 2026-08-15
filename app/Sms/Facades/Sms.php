<?php

namespace App\Sms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Sms\DTO\SmsResponse send(string $to, string $message, string $context = 'custom')
 * @method static \App\Sms\DTO\SmsResponse sendOTP(string $to, string $code)
 * @method static \App\Sms\DTO\SmsResponse sendOrder(string $to, array $data)
 * @method static \App\Sms\DTO\SmsResponse sendInvoice(string $to, array $data)
 * @method static \App\Sms\DTO\SmsResponse sendNotification(string $to, string $message)
 * @method static \App\Sms\DTO\SmsResponse sendMarketing(string $to, string $message)
 * @method static \App\Sms\DTO\SmsResponse[] sendBulk(array $recipients, string $message)
 * @method static \App\Sms\DTO\SmsResponse balance()
 * @method static array status()
 * @method static \App\Sms\DTO\SmsResponse test()
 * @method static array installedGateways()
 * @method static \App\Sms\Contracts\SmsGatewayInterface driverFor(string $key)
 *
 * @see \App\Sms\SmsManager
 */
class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sms.manager';
    }
}
