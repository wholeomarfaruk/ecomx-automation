<?php

namespace App\Support;

use App\Models\Country;

/**
 * Normalizes the phone formats customers actually type (01761234567,
 * 8801761234567, +8801761234567, 008801761234567, ...) into a single
 * canonical (country_code, national number) pair, so the same person
 * matches on login regardless of which format they used to register.
 * Country codes are looked up from the `countries` table (phone_code
 * column, e.g. "+880") — nothing here is hardcoded to one country.
 * National number never carries a leading 0 or the country code —
 * that's what's stored in `users.phone` / `customers.phone`.
 */
class PhoneNumber
{
    /**
     * @return array{country_code: string, phone: string} e.g. ['country_code' => '+880', 'phone' => '1761234567']
     */
    public static function normalize(string $raw, ?string $defaultCountry = null): array
    {
        $trimmed = trim($raw);
        $digits = preg_replace('/\D/', '', $trimmed) ?? '';
        $defaultCode = $defaultCountry ? self::phoneCodeFor($defaultCountry) : self::defaultPhoneCode();
        $defaultPlainCode = ltrim($defaultCode, '+');

        // "00..." (dialing-out prefix) always carries an explicit country
        // code next, e.g. 008801761234567 -> 880 1761234567.
        if (str_starts_with($digits, '00')) {
            $internationalDigits = substr($digits, 2);

            foreach (self::candidatePhoneCodes($defaultCode) as $phoneCode) {
                $plainCode = ltrim($phoneCode, '+');

                if (str_starts_with($internationalDigits, $plainCode)) {
                    return [
                        'country_code' => $phoneCode,
                        'phone' => ltrim(substr($internationalDigits, strlen($plainCode)), '0'),
                    ];
                }
            }
        }

        // "+..." is explicit too, e.g. +8801761234567 -> 880 1761234567.
        if (str_starts_with($trimmed, '+')) {
            foreach (self::candidatePhoneCodes($defaultCode) as $phoneCode) {
                $plainCode = ltrim($phoneCode, '+');

                if (str_starts_with($digits, $plainCode)) {
                    return [
                        'country_code' => $phoneCode,
                        'phone' => ltrim(substr($digits, strlen($plainCode)), '0'),
                    ];
                }
            }
        }

        // Bare digits carrying the default country's own code with no local
        // trunk 0, e.g. 8801761234567 -> 880 1761234567. Only checked against
        // the site's own default country — other countries' short dialing
        // codes are too ambiguous to guess from a bare digit string.
        if ($digits !== '' && ! str_starts_with($digits, '0') && str_starts_with($digits, $defaultPlainCode)) {
            return [
                'country_code' => $defaultCode,
                'phone' => ltrim(substr($digits, strlen($defaultPlainCode)), '0'),
            ];
        }

        // Local dialing format (01761234567 -> 1761234567) for the default country.
        return [
            'country_code' => $defaultCode,
            'phone' => ltrim($digits, '0'),
        ];
    }

    /** Just the national number, for matching against a stored `phone` column. */
    public static function national(string $raw, ?string $defaultCountry = null): string
    {
        return self::normalize($raw, $defaultCountry)['phone'];
    }

    /** For display: +8801761234567. */
    public static function display(?string $phone, ?string $countryCode = null): string
    {
        if (! $phone) {
            return '';
        }

        return ($countryCode ?: self::defaultPhoneCode()) . $phone;
    }

    /** Local dialing format (trunk 0 + national number): 01761234567. */
    public static function local(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        return '0' . $phone;
    }

    /** Every active country's phone_code, longest first so "+1" doesn't shadow "+1876". */
    protected static function candidatePhoneCodes(string $defaultCode): array
    {
        $codes = Country::query()
            ->active()
            ->whereNotNull('phone_code')
            ->pluck('phone_code')
            ->unique()
            ->all();

        usort($codes, fn ($a, $b) => strlen($b) <=> strlen($a));

        // Always try the default country's code first, even if it isn't
        // marked active — a plain local number should still match it.
        return array_unique(array_merge([$defaultCode], $codes));
    }

    protected static function phoneCodeFor(string $isoCode): string
    {
        return Country::query()->where('code', $isoCode)->value('phone_code') ?? '+880';
    }

    /**
     * The site's primary country for phone parsing — the active,
     * registration-allowed country the seeder ranks first (lowest
     * sort_order). Falls back to Bangladesh only if the countries table
     * is empty (e.g. a fresh install before seeding has run).
     */
    protected static function defaultPhoneCode(): string
    {
        return Country::query()
            ->active()
            ->where('is_register_allowed', true)
            ->whereNotNull('phone_code')
            ->orderBy('sort_order')
            ->value('phone_code') ?? '+880';
    }
}
