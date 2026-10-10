<?php

namespace App\Services\FraudShield;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Advance → Fraud Checker (settings group "fraud_checker"). The API key is
 * stored encrypted and never sent to the browser.
 */
class FraudShieldSettings
{
    public const GROUP = 'fraud_checker';

    public const DEFAULT_BASE_URL = 'https://fraudshield.bd';

    public function enabled(): bool
    {
        return (bool) Setting::get('enabled', '0', self::GROUP);
    }

    /** Switched on and a key saved. */
    public function ready(): bool
    {
        return $this->enabled() && $this->apiKey() !== '';
    }

    public function apiKey(): string
    {
        $stored = (string) Setting::get('api_key', '', self::GROUP);

        if ($stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return '';
        }
    }

    public static function storeApiKey(?string $key): void
    {
        Setting::set('api_key', $key ? Crypt::encryptString($key) : '', self::GROUP);
    }

    /** Last four characters, for "saved: ••••abcd". */
    public function maskedKey(): string
    {
        $key = $this->apiKey();

        return $key === '' ? '' : '••••' . substr($key, -4);
    }

    public function baseUrl(): string
    {
        return rtrim((string) (Setting::get('base_url', '', self::GROUP) ?: self::DEFAULT_BASE_URL), '/');
    }

    /** Orders list checks unchecked rows one by one after the page has loaded. */
    public function autoCheckList(): bool
    {
        return (bool) Setting::get('auto_check_list', '1', self::GROUP);
    }

    /** How long a stored result is re-used before the API is called again. */
    public function cacheHours(): int
    {
        return max(0, min(720, (int) Setting::get('cache_hours', '24', self::GROUP)));
    }

    public function timeout(): int
    {
        return max(5, min(60, (int) Setting::get('timeout', '20', self::GROUP)));
    }
}
