<?php

namespace App\Services\Auth;

use App\Models\EmailTemplate;
use App\Models\SmsGatewayConfig;
use App\Models\User;
use App\Sms\Facades\Sms;
use App\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 6-digit one-time codes sent by email or SMS, used for contact
 * verification (scope "verify") and email/SMS two-factor login (scope "2fa").
 *
 * Codes live in cache — never users.otp — so they can't clash with a
 * storefront login/forgot-password OTP for the same account. Each code is
 * bound to the address/number it was sent to, so a code sent before the
 * email/phone changed can't be used afterwards.
 */
class VerificationCodes
{
    public const CODE_TTL_MINUTES = 10;
    public const RESEND_COOLDOWN = 60;
    public const MAX_SENDS_PER_HOUR = 5;
    public const MAX_VERIFY_ATTEMPTS = 5;

    /**
     * @param  'email'|'sms'  $channel
     * @return string|null  null when sent, otherwise a user-facing error
     */
    public function send(User $user, string $scope, string $channel): ?string
    {
        $target = $this->target($user, $channel);

        if ($target === null) {
            return $channel === 'email' ? 'No email address on this account.' : 'No phone number on this account.';
        }

        if ($channel === 'sms') {
            $gateway = SmsGatewayConfig::active();
            if ($gateway === null || empty($gateway->credentials)) {
                return 'No active SMS gateway. Set one up in SMS Configuration first.';
            }
        }

        $cooldownKey = $this->key('cooldown', $user, $scope, $channel);
        $hourlyKey = $this->key('hourly', $user, $scope, $channel);

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return 'Please wait ' . RateLimiter::availableIn($cooldownKey) . ' seconds before requesting another code.';
        }

        if (RateLimiter::tooManyAttempts($hourlyKey, self::MAX_SENDS_PER_HOUR)) {
            return 'Too many codes requested. Try again in ' . ceil(RateLimiter::availableIn($hourlyKey) / 60) . ' minutes.';
        }

        $code = (string) random_int(100000, 999999);

        try {
            $sent = $channel === 'email'
                ? EmailTemplate::send('otp', $user->email, [
                    'name' => $user->name,
                    'email' => $user->email,
                    'code' => $code,
                    'app_name' => config('app.name'),
                    'year' => now()->year,
                ])
                : Sms::sendOTP(PhoneNumber::local($user->phone), $code)->success;
        } catch (\Throwable $e) {
            Log::warning('Verification code send failed', ['user_id' => $user->id, 'scope' => $scope, 'channel' => $channel, 'error' => $e->getMessage()]);
            $sent = false;
        }

        if (!$sent) {
            return $channel === 'email'
                ? 'Could not send the email. Check Email Configuration and that the "otp" email template is active.'
                : 'Could not send the SMS. Check the SMS gateway and try again.';
        }

        RateLimiter::hit($cooldownKey, self::RESEND_COOLDOWN);
        RateLimiter::hit($hourlyKey, 3600);

        Cache::put($this->key('code', $user, $scope, $channel), [
            'hash' => hash('sha256', $code),
            'target' => $target,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES)->getTimestamp(),
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        return null;
    }

    /**
     * @return string|null  null when the code is correct (and consumed), otherwise a user-facing error
     */
    public function verify(User $user, string $scope, string $channel, string $code): ?string
    {
        $codeKey = $this->key('code', $user, $scope, $channel);
        $pending = Cache::get($codeKey);

        if (!$pending || $pending['target'] !== $this->target($user, $channel)) {
            return 'This code has expired. Send a new one.';
        }

        if (!hash_equals($pending['hash'], hash('sha256', trim($code)))) {
            $pending['attempts']++;

            if ($pending['attempts'] >= self::MAX_VERIFY_ATTEMPTS) {
                Cache::forget($codeKey);
                return 'Too many wrong attempts. Send a new code.';
            }

            // Keep the original expiry — wrong guesses must not extend the code's life.
            Cache::put($codeKey, $pending, Carbon::createFromTimestamp($pending['expires_at']));
            return 'Incorrect code. ' . (self::MAX_VERIFY_ATTEMPTS - $pending['attempts']) . ' attempts left.';
        }

        Cache::forget($codeKey);

        return null;
    }

    /** A still-valid code exists for the account's current email/phone. */
    public function hasPending(User $user, string $scope, string $channel): bool
    {
        $pending = Cache::get($this->key('code', $user, $scope, $channel));

        return $pending && $pending['target'] === $this->target($user, $channel);
    }

    public function forget(User $user, string $scope, string $channel): void
    {
        Cache::forget($this->key('code', $user, $scope, $channel));
    }

    /** Seconds until another code may be sent (drives resend countdowns). */
    public function resendIn(User $user, string $scope, string $channel): int
    {
        return RateLimiter::availableIn($this->key('cooldown', $user, $scope, $channel));
    }

    /** "in•••@example.com" / "•••••••4567" — for screens shown before login completes. */
    public static function mask(User $user, string $channel): string
    {
        if ($channel === 'email') {
            [$local, $domain] = array_pad(explode('@', (string) $user->email, 2), 2, '');

            return mb_substr($local, 0, 2) . '•••@' . $domain;
        }

        return '•••••' . substr((string) $user->phone, -4);
    }

    protected function target(User $user, string $channel): ?string
    {
        if ($channel === 'email') {
            return $user->email ? strtolower($user->email) : null;
        }

        return $user->phone ? $user->country_code . $user->phone : null;
    }

    protected function key(string $kind, User $user, string $scope, string $channel): string
    {
        return "vcode-{$kind}:{$scope}:{$user->id}:{$channel}";
    }
}
