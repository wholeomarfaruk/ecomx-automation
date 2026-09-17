<?php

namespace App\Support\EcomxAnyniche;

/**
 * File-backed (no DB) registry of the site-wide active colour palette.
 * Storage: resources/{active theme}/config/appearance.json. Mirrors
 * PageSectionRegistry/PageSettingsRegistry's read/write pattern (flock +
 * atomic rename).
 */
class PaletteRegistry
{
    protected static function path(): string
    {
        return ActiveTheme::resourcePath('config/appearance.json');
    }

    protected static function read(): array
    {
        $path = static::path();

        if (! file_exists($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        try {
            flock($handle, LOCK_SH);
            $contents = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $data = json_decode($contents, true);

        return is_array($data) ? $data : [];
    }

    protected static function write(array $data): void
    {
        $path = static::path();
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmp = $path . '.' . uniqid('', true) . '.tmp';
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        rename($tmp, $path);
    }

    /** Site-wide active palette slug, falling back to config('ecomx-anyniche.palettes')'s first entry. */
    public static function active(): string
    {
        $palettes = config('ecomx-anyniche.palettes', []);
        $stored = static::read()['active'] ?? null;

        if ($stored !== null && in_array($stored, $palettes, true)) {
            return $stored;
        }

        return $palettes[0] ?? 'terracotta';
    }

    public static function setActive(string $palette): void
    {
        if (! in_array($palette, config('ecomx-anyniche.palettes', []), true)) {
            throw new \InvalidArgumentException("Unknown palette [{$palette}].");
        }

        static::write(['active' => $palette]);
    }

    /**
     * Swatch preview colours for the admin UI — kept in sync by hand with
     * resources/ecomx-anyniche/scss/base/_palettes.scss ([data-pal="..."] rules).
     */
    public static function swatches(): array
    {
        return [
            'neutral' => ['pri' => '#17181C', 'sec' => '#F5F5F4', 'ac' => '#2F6FED'],
        ];
    }
}
