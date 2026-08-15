<?php

namespace App\Sms\Drivers;

use App\Sms\Contracts\SmsGatewayInterface;
use App\Sms\DTO\SmsMessage;
use App\Sms\DTO\SmsResponse;
use App\Sms\Exceptions\SmsAuthenticationException;
use App\Sms\Exceptions\SmsBalanceException;
use App\Sms\Exceptions\SmsGatewayUnavailableException;
use Illuminate\Support\Facades\Http;

class AlphaSmsDriver implements SmsGatewayInterface
{
    protected string $apiKey;
    protected string $senderId;
    protected int $timeout;

    protected const BASE_URL = 'https://api.sms.net.bd/v3';

    public function __construct(array $credentials, array $options = [])
    {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->senderId = $credentials['sender_id'] ?? '';
        $this->timeout = $options['timeout'] ?? 30;
    }

    protected function client()
    {
        return Http::timeout($this->timeout)->baseUrl(self::BASE_URL);
    }

    public function send(SmsMessage $message): SmsResponse
    {
        try {
            $response = $this->client()->post('/sms/send', [
                'api_key' => $this->apiKey,
                'msisdn' => $message->to,
                'sms' => $message->message,
                'sender_id' => $message->senderId ?: $this->senderId,
            ]);
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        $this->guardCommonErrors($data);

        if (($data['error'] ?? 1) != 0) {
            return SmsResponse::failure('alpha_sms', (string) ($data['error'] ?? 'unknown_provider_error'), $data['msg'] ?? 'Unknown Provider Error', $data);
        }

        return SmsResponse::success(
            provider: 'alpha_sms',
            messageId: $data['data']['request_id'] ?? null,
            status: 'sent',
            cost: isset($data['data']['sms_count']) ? (float) $data['data']['sms_count'] : null,
            providerResponse: $data,
            rawResponse: $data,
        );
    }

    public function sendBulk(array $messages): array
    {
        return array_map(fn (SmsMessage $message) => $this->send($message), $messages);
    }

    public function getBalance(): SmsResponse
    {
        try {
            $response = $this->client()->get('/account/balance', ['api_key' => $this->apiKey]);
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];

        $this->guardCommonErrors($data);

        return SmsResponse::success(
            provider: 'alpha_sms',
            status: 'balance',
            remainingBalance: isset($data['data']['balance']) ? (float) $data['data']['balance'] : null,
            providerResponse: $data,
            rawResponse: $data,
        );
    }

    public function testConnection(): SmsResponse
    {
        return $this->validateCredentials();
    }

    public function validateCredentials(): SmsResponse
    {
        try {
            $response = $this->client()->get('/account/balance', ['api_key' => $this->apiKey]);
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? [];
        $this->guardCommonErrors($data);

        return SmsResponse::success('alpha_sms', status: 'valid', providerResponse: $data, rawResponse: $data);
    }

    public function getStatus(): array
    {
        return ['online' => true, 'last_checked_at' => now()->toIso8601String()];
    }

    public function supportedFeatures(): array
    {
        return [
            'bulk' => true,
            'delivery_status' => false,
            'balance_check' => true,
            'sender_id' => true,
        ];
    }

    public static function meta(): array
    {
        return [
            'key' => 'alpha_sms',
            'label' => 'Alpha SMS',
            'version' => '1.0.0',
            'fields' => [
                ['name' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['name' => 'sender_id', 'label' => 'Sender ID', 'type' => 'text', 'required' => true],
            ],
        ];
    }

    protected function guardCommonErrors(array $data): void
    {
        $code = (string) ($data['error'] ?? '');

        if ($code === '1001' || $code === '1002') {
            throw new SmsAuthenticationException($data);
        }

        if ($code === '1010') {
            throw new SmsBalanceException($data);
        }
    }
}
