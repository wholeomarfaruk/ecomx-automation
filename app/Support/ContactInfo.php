<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Storefront contact channels, managed in Admin > Site Settings > Contacts
 * and shared by every theme (support modal, product page order buttons,
 * auth/track "call us" fallbacks, ...).
 *
 * Each accessor returns null when the channel isn't configured (or holds
 * nothing usable) so views can hide it instead of showing a dead link.
 * Phone falls back to the company phone; WhatsApp falls back to the phone.
 */
class ContactInfo
{
    public const GROUP = 'contact';

    /** Support phone for display, as entered (e.g. "+880 1700-000000"). */
    public static function phone(): ?string
    {
        foreach ([['support_phone', self::GROUP], ['company_phone', 'company']] as [$key, $group]) {
            $value = self::setting($key, $group);

            if ($value !== null && self::internationalDigits($value) !== null) {
                return $value;
            }
        }

        return null;
    }

    public static function telHref(): ?string
    {
        $digits = self::internationalDigits(self::phone());

        return $digits !== null ? 'tel:+' . $digits : null;
    }

    public static function whatsappUrl(?string $text = null): ?string
    {
        $digits = self::internationalDigits(self::setting('support_whatsapp'))
            ?? self::internationalDigits(self::phone());

        if ($digits === null) {
            return null;
        }

        return 'https://wa.me/' . $digits . ($text !== null && $text !== '' ? '?text=' . rawurlencode($text) : '');
    }

    /** Accepts a full m.me / facebook URL or just the page username. */
    public static function messengerUrl(): ?string
    {
        $value = self::setting('support_messenger');

        if ($value === null) {
            return null;
        }

        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }

        $username = trim($value, " \t@/");

        return $username !== '' ? 'https://m.me/' . $username : null;
    }

    /** Free-text opening hours, e.g. "9am–11pm, 7 days a week". */
    public static function hours(): ?string
    {
        return self::setting('support_hours');
    }

    /** Trimmed string value, or null when missing/empty/non-scalar — never throws. */
    private static function setting(string $key, string $group = self::GROUP): ?string
    {
        try {
            $value = Setting::get($key, '', $group);
        } catch (\Throwable $e) {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /** Digits with country code (a local BD mobile "01XXXXXXXXX" gets 88 prefixed), or null if none. */
    private static function internationalDigits(?string $number): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $number);

        if ($digits === null || $digits === '') {
            return null;
        }

        return (strlen($digits) === 11 && str_starts_with($digits, '01')) ? '88' . $digits : $digits;
    }
}
