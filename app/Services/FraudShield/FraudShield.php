<?php

namespace App\Services\FraudShield;

use App\Models\FraudCheck;
use App\Models\Order;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * FraudShield (fraudshield.bd) courier fraud check — POST /api/customer/check
 * with a BD mobile number returns the customer's parcel history across
 * couriers, reviews and a risk score.
 *
 * The plan's daily limit is low, so every path guards the quota:
 *  - one stored result per phone (`fraud_checks`), re-used for
 *    FraudShieldSettings::cacheHours(); the same phone on many orders is
 *    one call;
 *  - a per-phone lock, so two tabs/admins never pay for the same number;
 *  - a forced re-check is refused within RECHECK_COOLDOWN_MINUTES;
 *  - after a 429 (limit hit) no call is made until the limit resets, and
 *    after a 401 none until the key is changed (see block()).
 */
class FraudShield
{
    public const RECHECK_COOLDOWN_MINUTES = 30;

    private const BLOCK_KEY = 'fraudshield:blocked';

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

    /** Checked so recently that a forced re-check would only waste quota. */
    public function inCooldown(?FraudCheck $check): bool
    {
        return (bool) $check?->checked_at
            && $check->checked_at->gt(now()->subMinutes(self::RECHECK_COOLDOWN_MINUTES));
    }

    /** Stored result for a phone, without calling the API. */
    public function stored(?string $phone): ?FraudCheck
    {
        $phone = self::normalizePhone($phone);

        return $phone === '' ? null : FraudCheck::where('phone', $phone)->first();
    }

    /** Why calls are paused right now (limit hit / key rejected), or null. */
    public function blockedReason(): ?string
    {
        $block = Cache::get(self::BLOCK_KEY);

        if (! is_array($block)) {
            return null;
        }

        // A 401 block only holds for the key that was rejected.
        if (($block['key'] ?? null) && $block['key'] !== $this->keyFingerprint()) {
            Cache::forget(self::BLOCK_KEY);

            return null;
        }

        return $block['reason'] ?? null;
    }

    public static function clearBlock(): void
    {
        Cache::forget(self::BLOCK_KEY);
    }

    /**
     * Stored result when still fresh, otherwise one API call. $fresh forces
     * the call, except within the re-check cooldown.
     *
     * @throws FraudShieldException
     */
    public function check(?string $phone, bool $fresh = false): FraudCheck
    {
        $phone = self::normalizePhone($phone);

        if ($phone === '') {
            throw new FraudShieldException('Not a valid Bangladeshi mobile number (01XXXXXXXXX).');
        }

        $reuse = fn (?FraudCheck $existing) => $existing
            && ($fresh ? $this->inCooldown($existing) : $this->isFresh($existing));

        $existing = FraudCheck::where('phone', $phone)->first();

        if ($reuse($existing)) {
            return $existing;
        }

        try {
            return Cache::lock("fraudshield:check:{$phone}", 30)->block(25, function () use ($phone, $reuse) {
                // Another request may have just checked this number while we waited.
                $existing = FraudCheck::where('phone', $phone)->first();

                if ($reuse($existing)) {
                    return $existing;
                }

                return $this->store($phone, $this->send('post', '/api/customer/check', ['phone' => $phone]));
            });
        } catch (LockTimeoutException) {
            throw new FraudShieldException('This number is already being checked — try again in a moment.');
        }
    }

    /**
     * Today's limit and package (GET /api/usage/daily-limit).
     *
     * @throws FraudShieldException
     */
    public function dailyLimit(): array
    {
        return $this->send('get', '/api/usage/daily-limit', [], false)['data'] ?? [];
    }

    private function store(string $phone, array $data): FraudCheck
    {
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
     * $honourBlock: the usage endpoint is still allowed while blocked, so
     * "Test connection" can show when the limit resets.
     *
     * @throws FraudShieldException
     */
    private function send(string $method, string $path, array $body = [], bool $honourBlock = true): array
    {
        if (! $this->settings->enabled()) {
            throw new FraudShieldException('Fraud Checker is turned off (Advance → Fraud Checker).');
        }

        if ($this->settings->apiKey() === '') {
            throw new FraudShieldException('No FraudShield API key saved (Advance → Fraud Checker).');
        }

        if ($honourBlock && ($reason = $this->blockedReason())) {
            throw new FraudShieldException($reason, 429, blocked: true);
        }

        try {
            $response = $method === 'post'
                ? $this->client()->post($path, $body)
                : $this->client()->get($path, $body);
        } catch (ConnectionException $e) {
            throw new FraudShieldException('Could not reach FraudShield: ' . $e->getMessage());
        }

        if (! $response->successful()) {
            $this->block($response);

            throw new FraudShieldException($this->errorMessage($response), $response->status(),
                blocked: in_array($response->status(), [401, 429], true));
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new FraudShieldException('FraudShield returned an unexpected response.');
        }

        return $json;
    }

    /** Stop calling after "limit reached" (until it resets) or "bad key" (until the key changes). */
    private function block(Response $response): void
    {
        if ($response->status() === 429) {
            // FraudShield resets the daily limit at midnight Bangladesh time.
            $resetsAt = now('Asia/Dhaka')->endOfDay();

            Cache::put(self::BLOCK_KEY, [
                'reason' => 'FraudShield daily limit reached — checks resume after midnight (' . $resetsAt->format('d M') . ').',
            ], $resetsAt);
        } elseif ($response->status() === 401) {
            Cache::put(self::BLOCK_KEY, [
                'reason' => 'FraudShield rejected the API key — save a valid key under Advance → Fraud Checker.',
                'key' => $this->keyFingerprint(),
            ], now()->addDay());
        }
    }

    private function keyFingerprint(): string
    {
        return hash('sha256', $this->settings->apiKey());
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
            429 => 'FraudShield daily limit reached (429). Checks resume after the limit resets.',
            default => 'FraudShield error (' . $response->status() . ')' . ($message ? ': ' . $message : '.'),
        };
    }
}
