<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Guest order lookup for the storefront track pages (both themes), from the
 * form or from ?order_id=…&phone=… in the URL. Input is whitelisted, not
 * cleaned: anything that isn't a plain order number / phone number — arrays,
 * markup, quotes, overlong strings — is rejected outright, and only
 * verified values ever get written back into the URL. Failed attempts are
 * rate-limited per IP so the order-ID + phone pair can't be brute-forced.
 */
class OrderTrackLookup
{
    public const MAX_FAILED_ATTEMPTS = 10;

    /** "128" or "#128" → 128; anything else → null. */
    public static function orderId(mixed $raw): ?int
    {
        if (! is_string($raw) && ! is_int($raw)) {
            return null;
        }

        $value = ltrim(trim((string) $raw), '#');

        if (! preg_match('/^\d{1,10}$/', $value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }

    /** Digits with an optional leading + and spaces/dashes → national number (no trunk 0); anything else → null. */
    public static function phone(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $value = trim($raw);

        if (strlen($value) > 20 || ! preg_match('/^\+?[\d\s\-]+$/', $value)) {
            return null;
        }

        $national = PhoneNumber::national($value);

        return preg_match('/^\d{6,15}$/', $national) ? $national : null;
    }

    /**
     * Order whose customer's phone matches, or null. Throws nothing — the
     * caller shows tooManyAttempts() / a generic not-found message.
     */
    public static function find(mixed $rawOrderId, mixed $rawPhone): ?Order
    {
        $orderId = self::orderId($rawOrderId);
        $phone = self::phone($rawPhone);

        $order = $orderId && $phone
            ? Order::whereKey($orderId)
                ->whereHas('customer', fn ($q) => $q->where('phone', $phone))
                ->first()
            : null;

        if (! $order) {
            RateLimiter::hit(self::key(), 60);
        }

        return $order;
    }

    public static function tooManyAttempts(): bool
    {
        return RateLimiter::tooManyAttempts(self::key(), self::MAX_FAILED_ATTEMPTS);
    }

    /** Query params for a verified lookup — always rebuilt from the order, never echoed from input. */
    public static function queryFor(Order $order, string $nationalPhone): array
    {
        return ['order_id' => $order->id, 'phone' => PhoneNumber::local($nationalPhone)];
    }

    protected static function key(): string
    {
        return 'order-track:' . request()->ip();
    }
}
