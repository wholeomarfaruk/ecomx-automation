<?php

namespace App\OrderIntake;

/**
 * Finds Bangladesh mobile numbers in free text — 01XXXXXXXXX, +8801…,
 * 8801…, 008801…, with spaces/dashes/dots/brackets inside and Bangla
 * digits — and normalizes them the way customers.phone stores them (no
 * country code, no trunk 0 — what App\Support\PhoneNumber::national()
 * gives for a BD number, minus its countries-table lookup, so the parser
 * stays DB-free; PlaceBulkOrder still runs the canonical one on placing).
 */
final class PhoneExtractor
{
    /**
     * A BD mobile number as people type it: optional +88/0088/88 (maybe in
     * brackets), optional trunk 0, then 1[3-9] and 8 more digits — one
     * space/dash/dot allowed between digits, never a newline, so two numbers
     * on separate lines or a number followed by an amount stay apart.
     */
    public const PATTERN = '/(?<![\d])(?:\(?\s*(?:\+|00)?\s*88\s*\)?[\s\-\.]*)?0?[ \t\-\.]?1[3-9](?:[ \t\-\.]?\d){8}(?![\d])/u';

    /**
     * Valid numbers in the order they appear, duplicates kept.
     *
     * @return list<array{raw: string, national: string}>
     */
    public static function find(string $text): array
    {
        if (! preg_match_all(self::PATTERN, Text::asciiDigits($text), $matches)) {
            return [];
        }

        $found = [];

        foreach ($matches[0] as $raw) {
            $national = self::national($raw);

            if (self::isValid($national)) {
                $found[] = ['raw' => trim($raw), 'national' => $national];
            }
        }

        return $found;
    }

    /** The text with every phone number replaced by a space. */
    public static function strip(string $text): string
    {
        return preg_replace(self::PATTERN, ' ', Text::asciiDigits($text)) ?? $text;
    }

    public static function national(string $raw): string
    {
        $digits = preg_replace('/\D/', '', Text::asciiDigits($raw)) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '880') && strlen($digits) >= 12) {
            $digits = substr($digits, 3);
        }

        return ltrim($digits, '0');
    }

    /** A Bangladesh mobile number in national form: 1[3-9] + 8 digits. */
    public static function isValid(string $national): bool
    {
        return (bool) preg_match('/^1[3-9]\d{8}$/', $national);
    }

    /** The way the sheet and people write it: 01XXXXXXXXX. */
    public static function local(string $national): string
    {
        return self::isValid($national) ? '0' . $national : $national;
    }
}
