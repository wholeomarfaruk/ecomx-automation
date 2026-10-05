<?php

namespace App\OrderIntake;

/**
 * String helpers shared by the AI Order parser and matchers — the PHP side
 * of the bulk sheet's asciiDigits()/norm()/words() (bulk-order-create.blade.php),
 * so text is compared the same way on both sides.
 */
final class Text
{
    private const BN_DIGITS = ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'];

    /** Bangla digits → ASCII digits. */
    public static function asciiDigits(string $s): string
    {
        return strtr($s, self::BN_DIGITS);
    }

    /** Lowercased, ASCII digits, single spaces. */
    public static function norm(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower(self::asciiDigits($s))) ?? '');
    }

    /** Lowercased letters/digits only — "SF-0156" and "sf 0156" both become "sf0156". */
    public static function compact(string $s): string
    {
        return preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '', self::norm($s)) ?? '';
    }

    /**
     * Words of the text. \p{M} keeps Bangla vowel signs (combining marks,
     * not letters) inside their word — "দুইটা" stays one word.
     *
     * @return list<string>
     */
    public static function words(string $s): array
    {
        return array_values(array_filter(preg_split('/[^\p{L}\p{M}\p{N}]+/u', self::norm($s)) ?: [], fn ($w) => $w !== ''));
    }

    /** First number in the text ("1,250.50 tk" → 1250.5), Bangla digits included. */
    public static function number(?string $s): ?float
    {
        if ($s === null) {
            return null;
        }

        $s = str_replace(',', '', self::asciiDigits($s));

        return preg_match('/-?\d+(?:\.\d+)?/', $s, $m) ? (float) $m[0] : null;
    }
}
