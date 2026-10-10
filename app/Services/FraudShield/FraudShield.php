<?php

namespace App\Services\FraudShield;

use App\Models\FraudCheck;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * FraudShield (fraudshield.bd) courier fraud check — POST /api/customer/check
 * with a BD mobile number returns the customer's parcel history across
 * couriers, reviews and a risk score. Results are kept in `fraud_checks`
 * (one row per phone) and re-used for FraudShieldSettings::cacheHours(),
 * so opening the modal again doesn't spend the daily quota.
 */
class FraudShield
{
    public function __construct(private FraudShieldSettings $settings)
    {
    }

    /** Any stored phone format (1712…, 01712…, +8801712…) → 01XXXXXXXXX, or '' when it isn't a BD mobile. */
    public static function normalizePhone(?string $raw): string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        return preg_match('/^01[3-9]\d{8}$/', $digits) ? $digits : '';
    }

    /** The number an order is checked by — shipping phone first, then the customer's. */
    public static function orderPhone(Order $order): string
    {
        return self::normalizePhone($order->shippingAddress?->phone ?: $order->customer?->phone);
    }

    /** Stored result is young enough to re-use instead of calling the API. */
    public function isFresh(?FraudCheck $check): bool
    {
        return (bool) $check?->checked_at
            && $check->checked_at->gt(now()->subHours($this->settings->cacheHours()));
    }

    /** Stored result for a phone, without calling the API. */
    public function stored(?string $phone): ?FraudCheck
    {
        $phone = self::normalizePhone($phone);

        return $phone === '' ? null : FraudCheck::where('phone', $phone)->first();
    }

    /**
     * Stored result when still fresh, otherwise a new API call.
     *
     * @throws FraudShieldException
     */
    public function check(?string $phone, bool $fresh = false): FraudCheck
    {
        $phone = self::normalizePhone($phone);

        if ($phone === '') {
            throw new FraudShieldException('Not a valid Bangladeshi mobile number (01XXXXXXXXX).');
        }

        $existing = FraudCheck::where('phone', $phone)->first();

        if (! $fresh && $this->isFresh($existing)) {
            return $existing;
        }

        $data = $this->send('post', '/api/customer/check', ['phone' => $phone]);

        $summary = $data['courierData']['summary'] ?? [];
        $risk = $data['fraudRiskScore'] ?? [];

        return FraudCheck::updateOrCreate(['phone' => $phone], [
            'score' => isset($risk['score']) ? (int) $risk['score'] : null,
            'level' => $risk['level'] ?? null,
            'label' => $risk['label'] ?? null,
            'total_parcel' => (int) ($summary['total_parcel'] ?? 0),
            'success_parcel' => (int) ($summary['success_parcel'] ?? 0),
            'cancelled_parcel' => (int) ($summary['cancelled_parcel'] ?? 0),
            'success_ratio' => (float) ($summary['success_ratio'] ?? 0),
            'review_count' => is_array($data['reviews'] ?? null) ? count($data['reviews']) : 0,
            'payload' => $data,
            'checked_by' => auth()->id(),
            'checked_at' => now(),
        ]);
    }

    /**
     * Today's limit and package (GET /api/usage/daily-limit).
     *
     * @throws FraudShieldException
     */
    public function dailyLimit(): array
    {
        return $this->send('get', '/api/usage/daily-limit')['data'] ?? [];
    }

    /** @throws FraudShieldException */
    private function send(string $method, string $path, array $body = []): array
    {
        if (! $this->settings->enabled()) {
            throw new FraudShieldException('Fraud Checker is turned off (Advance → Fraud Checker).');
        }

        if ($this->settings->apiKey() === '') {
            throw new FraudShieldException('No FraudShield API key saved (Advance → Fraud Checker).');
        }

        try {
            $response = $method === 'post'
                ? $this->client()->post($path, $body)
                : $this->client()->get($path, $body);
        } catch (ConnectionException $e) {
            throw new FraudShieldException('Could not reach FraudShield: ' . $e->getMessage());
        }

        if (! $response->successful()) {
            throw new FraudShieldException($this->errorMessage($response));
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new FraudShieldException('FraudShield returned an unexpected response.');
        }

        return $json;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->settings->baseUrl())
            ->withToken($this->settings->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout($this->settings->timeout());
    }

    private function errorMessage(Response $response): string
    {
        $message = $response->json('message') ?: $response->json('error');

        return match ($response->status()) {
            401 => 'FraudShield rejected the API key (401). Check it under Advance → Fraud Checker.',
            429 => 'FraudShield daily limit reached (429). Try again after the limit resets.',
            default => 'FraudShield error (' . $response->status() . ')' . ($message ? ': ' . $message : '.'),
        };
    }
}
