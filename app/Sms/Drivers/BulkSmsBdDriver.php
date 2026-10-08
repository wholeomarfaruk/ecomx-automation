<?php

namespace App\Sms\Drivers;

use App\Sms\Contracts\SmsGatewayInterface;
use App\Sms\DTO\SmsMessage;
use App\Sms\DTO\SmsResponse;
use App\Sms\Exceptions\SmsGatewayUnavailableException;
use Illuminate\Support\Facades\Http;

/**
 * BulkSMSBD (bulksmsbd.net). The API reads plain form/query fields — a JSON
 * body arrives empty and is rejected with 1003 "Please Required all fields",
 * so requests go out as form data with type=text. Numbers are sent as
 * 8801XXXXXXXXX (see normalizeNumber()). Every response carries a
 * response_code; 202 is the only success.
 */
class BulkSmsBdDriver implements SmsGatewayInterface
{
    protected string $apiKey;
    protected string $senderId;
    protected int $timeout;

    protected const BASE_URL = 'https://bulksmsbd.net/api';

    /** BulkSMSBD response codes → readable messages (their own wording is often vague). */
    protected const ERRORS = [
        '1001' => 'Invalid number.',
        '1002' => 'Sender ID is not correct or is disabled.',
        '1003' => 'Required fields missing (API key, sender ID, number or message).',
        '1005' => 'BulkSMSBD internal error.',
        '1006' => 'Balance validity not available.',
        '1007' => 'Insufficient balance.',
        '1011' => 'User ID not found — check the API key.',
        '1012' => 'Masking SMS must be sent in Bengali.',
        '1013' => 'Sender ID has no gateway for this API key.',
        '1014' => 'Sender type name not found for this sender ID.',
        '1015' => 'Sender ID has no valid gateway for this API key.',
        '1016' => 'No active price for this sender ID.',
        '1017' => 'No price info for this sender ID.',
        '1018' => 'The account owner is disabled.',
        '1019' => 'The sender type price for this account is disabled.',
        '1020' => 'The parent of this account was not found.',
        '1021' => 'The parent account\'s active price for this account was not found.',
        '1031' => 'Account not verified — contact BulkSMSBD.',
        '1032' => 'Server IP is not whitelisted in BulkSMSBD.',
    ];

    public function __construct(array $credentials, array $options = [])
    {
        $this->apiKey = trim($credentials['api_key'] ?? '');
        $this->senderId = trim($credentials['sender_id'] ?? '');
        $this->timeout = $options['timeout'] ?? 30;
    }

    protected function client()
    {
        return Http::timeout($this->timeout)->acceptJson()->asForm()->baseUrl(self::BASE_URL);
    }

    /**
     * Any local/international BD format → 8801XXXXXXXXX: 01712345678,
     * 8801712345678, +8801712345678, 1712345678, and the doubled-prefix
     * 88001712345678 (country code typed in front of the trunk 0).
     */
    public static function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '8800')) {
            return '880' . substr($digits, 4);
        }

        if (str_starts_with($digits, '880')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '88' . $digits;
        }

        return strlen($digits) === 10 ? '880' . $digits : $digits;
    }

    public function send(SmsMessage $message): SmsResponse
    {
        $payload = [
            'api_key' => $this->apiKey,
            'type' => 'text',
            'senderid' => $message->senderId ?: $this->senderId,
            'number' => self::normalizeNumber($message->to),
            'message' => $message->message,
        ];

        $loggedPayload = ['api_key' => $this->apiKey !== '' ? '••••' . substr($this->apiKey, -4) : ''] + $payload;

        try {
            $response = $this->client()->post('/smsapi', $payload);
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json() ?? ['raw' => $response->body(), 'http_status' => $response->status()];
        $code = (string) ($data['response_code'] ?? '');

        if ($code !== '202') {
            $result = SmsResponse::failure(
                'bulksmsbd',
                $code !== '' ? $code : 'http_' . $response->status(),
                self::ERRORS[$code] ?? ($data['error_message'] ?? 'Unknown provider error'),
                $data,
            );
        } else {
            $result = SmsResponse::success(
                provider: 'bulksmsbd',
                status: 'sent',
                providerResponse: $data,
                rawResponse: $data,
            );
        }

        $result->requestPayload = $loggedPayload;

        return $result;
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

        $data = $response->json() ?? ['raw' => $response->body(), 'http_status' => $response->status()];
        $code = (string) ($data['response_code'] ?? '');

        // Older responses carry only "balance"; anything with a non-202 code is an error.
        if (! isset($data['balance']) || ($code !== '' && $code !== '202')) {
            return SmsResponse::failure(
                'bulksmsbd',
                $code !== '' ? $code : 'http_' . $response->status(),
                self::ERRORS[$code] ?? ($data['error_message'] ?? 'Could not read balance — check the API key.'),
                $data,
            );
        }

        return SmsResponse::success(
            provider: 'bulksmsbd',
            status: 'balance',
            remainingBalance: (float) $data['balance'],
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
