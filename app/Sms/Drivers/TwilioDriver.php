<?php

namespace App\Sms\Drivers;

use App\Sms\Contracts\SmsGatewayInterface;
use App\Sms\DTO\SmsMessage;
use App\Sms\DTO\SmsResponse;
use App\Sms\Exceptions\SmsAuthenticationException;
use App\Sms\Exceptions\SmsGatewayUnavailableException;
use Illuminate\Support\Facades\Http;

class TwilioDriver implements SmsGatewayInterface
{
    protected string $accountSid;
    protected string $authToken;
    protected string $fromNumber;
    protected int $timeout;

    public function __construct(array $credentials, array $options = [])
    {
        $this->accountSid = $credentials['account_sid'] ?? '';
        $this->authToken = $credentials['auth_token'] ?? '';
        $this->fromNumber = $credentials['from_number'] ?? '';
        $this->timeout = $options['timeout'] ?? 30;
    }

    protected function client()
    {
        return Http::withBasicAuth($this->accountSid, $this->authToken)
            ->timeout($this->timeout)
            ->baseUrl("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}");
    }

    public function send(SmsMessage $message): SmsResponse
    {
        try {
            $response = $this->client()->asForm()->post('/Messages.json', [
                'To' => $message->to,
                'From' => $message->senderId ?: $this->fromNumber,
                'Body' => $message->message,
            ]);
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json();

        if ($response->status() === 401) {
            throw new SmsAuthenticationException($data);
        }

        if (! $response->successful()) {
            return SmsResponse::failure('twilio', (string) ($data['code'] ?? 'unknown_provider_error'), $data['message'] ?? 'Unknown Provider Error', $data);
        }

        return SmsResponse::success(
            provider: 'twilio',
            messageId: $data['sid'] ?? null,
            status: $this->mapStatus($data['status'] ?? 'queued'),
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
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->timeout($this->timeout)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Balance.json");
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        $data = $response->json();

        if (! $response->successful()) {
            return SmsResponse::failure('twilio', 'unknown_provider_error', 'Unable to fetch balance', $data);
        }

        return SmsResponse::success(
            provider: 'twilio',
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
        try {
            $response = $this->client()->get('.json');
        } catch (\Throwable $e) {
            throw new SmsGatewayUnavailableException($e->getMessage());
        }

        if ($response->status() === 401) {
            throw new SmsAuthenticationException($response->json());
        }

        return $response->successful()
            ? SmsResponse::success('twilio', status: 'valid', providerResponse: $response->json(), rawResponse: $response->json())
            : SmsResponse::failure('twilio', 'unknown_provider_error', 'Unable to validate credentials', $response->json() ?? []);
    }

    public function getStatus(): array
    {
        return ['online' => true, 'last_checked_at' => now()->toIso8601String()];
    }

    public function supportedFeatures(): array
    {
        return [
            'bulk' => true,
            'delivery_status' => true,
            'balance_check' => true,
            'sender_id' => true,
        ];
    }

    public static function meta(): array
    {
        return [
            'key' => 'twilio',
            'label' => 'Twilio',
            'version' => '1.0.0',
            'fields' => [
                ['name' => 'account_sid', 'label' => 'Account SID', 'type' => 'text', 'required' => true],
                ['name' => 'auth_token', 'label' => 'Auth Token', 'type' => 'password', 'required' => true],
                ['name' => 'from_number', 'label' => 'From Number', 'type' => 'text', 'required' => true],
            ],
        ];
    }

    protected function mapStatus(string $twilioStatus): string
    {
        return match ($twilioStatus) {
            'delivered' => 'delivered',
            'sent', 'queued', 'accepted', 'sending' => 'sent',
            'failed', 'undelivered' => 'failed',
            default => 'pending',
        };
    }
}
