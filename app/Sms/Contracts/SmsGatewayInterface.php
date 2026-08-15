<?php

namespace App\Sms\Contracts;

use App\Sms\DTO\SmsMessage;
use App\Sms\DTO\SmsResponse;

interface SmsGatewayInterface
{
    public function __construct(array $credentials, array $options = []);

    public function send(SmsMessage $message): SmsResponse;

    /**
     * @param SmsMessage[] $messages
     * @return SmsResponse[]
     */
    public function sendBulk(array $messages): array;

    public function getBalance(): SmsResponse;

    public function testConnection(): SmsResponse;

    public function validateCredentials(): SmsResponse;

    /**
     * @return array{online: bool, last_checked_at: ?string}
     */
    public function getStatus(): array;

    /**
     * @return array<string, bool>
     */
    public function supportedFeatures(): array;

    /**
     * Static metadata used for auto-discovery in the admin panel.
     *
     * @return array{key: string, label: string, version: string, fields: array}
     */
    public static function meta(): array;
}
