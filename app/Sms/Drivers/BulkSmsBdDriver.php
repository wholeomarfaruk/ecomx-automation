<?php

namespace App\Sms\Drivers;

use App\Sms\Contracts\SmsGatewayInterface;
use App\Sms\DTO\SmsMessage;
use App\Sms\DTO\SmsResponse;
use App\Sms\Exceptions\SmsAuthenticationException;
use App\Sms\Exceptions\SmsGatewayUnavailableException;
use Illuminate\Support\Facades\Http;

/**
 * Template-quality driver for BulkSMSBD — copy this file's structure when
 * wiring up any new REST-based SMS gateway. Swap the endpoint/payload shape
 * to match the target provider's actual API documentation before use.
 */
class BulkSmsBdDriver implements SmsGatewayInterface
{
    protected string $apiKey;
    protected string $senderId;
    protected int $timeout;

    protected const BASE_URL = 'https://bulksmsbd.net/api';

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
            $response = $this->client()->post('/smsapi', [
                'api_key' => $this->apiKey,
                'senderid' => $message->senderId ?: $this->senderId,
                'number' => $message->to,
                'message' => $message->message,
            ]);
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? ['raw' => $response->body()];

        if (($data['response_code'] ?? null) == 1001) {
            throw new SmsAuthenticationException($data);
        }

        if (($data['response_code'] ?? null) != 202) {
            return SmsResponse::failure('bulksmsbd', (string) ($data['response_code'] ?? 'unknown_provider_error'), $data['error_message'] ?? 'Unknown Provider Error', $data);
        }

        return SmsResponse::success(
            provider: 'bulksmsbd',
            status: 'sent',
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
            $response = $this->client()->get('/getBalanceApi', ['api_key' => $this->apiKey]);
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? ['raw' => $response->body()];

        return SmsResponse::success(
            provider: 'bulksmsbd',
            status: 'balance',
            remainingBalance: isset($data['balance']) ? (float) $data['balance'] : null,
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
        return $this->getBalance();
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
            'key' => 'bulksmsbd',
            'label' => 'BulkSMSBD',
            'version' => '1.0.0',
            'fields' => [
                ['name' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['name' => 'sender_id', 'label' => 'Sender ID', 'type' => 'text', 'required' => true],
            ],
        ];
    }
}
