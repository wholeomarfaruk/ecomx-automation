<?php

namespace App\OrderIntake;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Settings → AI Order (settings group "ai_order"), with the defaults the
 * feature runs on before anyone opens that tab. The OpenRouter key is
 * stored encrypted and never leaves the server; OPENROUTER_API_KEY in .env
 * is used when none is saved.
 */
class IntakeSettings
{
    public const GROUP = 'ai_order';

    public const DEFAULT_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/plain'];

    public function enabled(): bool
    {
        return (bool) Setting::get('enabled', '0', self::GROUP);
    }

    /** AI fallback can actually run: switched on and a key available. */
    public function aiAvailable(): bool
    {
        return $this->enabled() && $this->apiKey() !== '';
    }

    public function apiKey(): string
    {
        $stored = (string) Setting::get('api_key', '', self::GROUP);

        if ($stored !== '') {
            try {
                return Crypt::decryptString($stored);
            } catch (DecryptException) {
                return '';
            }
        }

        return (string) config('services.openrouter.api_key', '');
    }

    public function hasStoredKey(): bool
    {
        return (string) Setting::get('api_key', '', self::GROUP) !== '';
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

    public function keySource(): string
    {
        return $this->hasStoredKey() ? 'settings' : ((string) config('services.openrouter.api_key', '') !== '' ? 'env' : 'none');
    }

    public function model(): string
    {
        return (string) (Setting::get('model', '', self::GROUP) ?: config('services.openrouter.model', 'openrouter/free'));
    }

    public function fallbackModel(): string
    {
        return (string) Setting::get('fallback_model', '', self::GROUP);
    }

    public function temperature(): float
    {
        return (float) Setting::get('temperature', '0', self::GROUP);
    }

    public function timeout(): int
    {
        return max(5, min(120, (int) Setting::get('timeout', '45', self::GROUP)));
    }

    public function maxOutputTokens(): int
    {
        return max(500, min(16000, (int) Setting::get('max_tokens', '3000', self::GROUP)));
    }

    /** AI calls allowed per day across the store (0 = no limit). */
    public function dailyLimit(): int
    {
        return max(0, (int) Setting::get('daily_limit', '200', self::GROUP));
    }

    /** AI calls one admin may make per minute. */
    public function perMinute(): int
    {
        return max(1, (int) Setting::get('per_minute', '6', self::GROUP));
    }

    public function maxFiles(): int
    {
        return max(1, min(10, (int) Setting::get('max_files', '4', self::GROUP)));
    }

    public function maxFileKb(): int
    {
        return max(100, min(20480, (int) Setting::get('max_file_kb', '4096', self::GROUP)));
    }

    /** @return list<string> */
    public function allowedMimes(): array
    {
        $mimes = Setting::get('allowed_mimes', null, self::GROUP);
        $list = is_array($mimes) ? $mimes : array_filter(array_map('trim', explode(',', (string) $mimes)));

        return $list ? array_values($list) : self::DEFAULT_MIMES;
    }

    /** How many catalogue products the AI is shown at most. */
    public function maxCandidates(): int
    {
        return max(10, min(200, (int) Setting::get('max_candidates', '40', self::GROUP)));
    }

    /** Store-specific instructions appended to the system prompt. */
    public function extraPrompt(): string
    {
        return mb_substr((string) Setting::get('extra_prompt', '', self::GROUP), 0, 2000);
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
    }
}
