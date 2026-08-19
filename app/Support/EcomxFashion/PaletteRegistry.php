<?php

namespace App\Support\EcomxFashion;

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

    /** Site-wide active palette slug, falling back to config('ecomx-fashion.palettes')'s first entry. */
    public static function active(): string
    {
        $palettes = config('ecomx-fashion.palettes', []);
        $stored = static::read()['active'] ?? null;

        if ($stored !== null && in_array($stored, $palettes, true)) {
            return $stored;
        }

        return $palettes[0] ?? 'terracotta';
    }

    public static function setActive(string $palette): void
    {
        if (! in_array($palette, config('ecomx-fashion.palettes', []), true)) {
            throw new \InvalidArgumentException("Unknown palette [{$palette}].");
        }

        static::write(['active' => $palette]);
    }

    /**
     * Swatch preview colours for the admin UI — kept in sync by hand with
     * resources/ecomx-fashion/scss/base/_palettes.scss ([data-pal="..."] rules).
     */
    public static function swatches(): array
    {
        return [
            'terracotta' => ['pri' => '#1A1310', 'sec' => '#F8F5F2', 'ac' => '#B5654A'],
            'rust'       => ['pri' => '#2A100C', 'sec' => '#FBEEE8', 'ac' => '#B04D45'],
            'midnight'   => ['pri' => '#241634', 'sec' => '#E8FBF5', 'ac' => '#2E7D78'],
            'coral'      => ['pri' => '#3E2A2A', 'sec' => '#FDF3F0', 'ac' => '#E8746F'],
            'peach'      => ['pri' => '#3B2E22', 'sec' => '#FFF8ED', 'ac' => '#E39158'],
            'blush'      => ['pri' => '#2E2333', 'sec' => '#FDF3F4', 'ac' => '#9C6E93'],
            'sage'       => ['pri' => '#232414', 'sec' => '#FBF3E2', 'ac' => '#7E8A54'],
            'olive'      => ['pri' => '#14170F', 'sec' => '#F4F6F1', 'ac' => '#6B7250'],
            'wine'       => ['pri' => '#1A0F13', 'sec' => '#F7F3F4', 'ac' => '#8E4257'],
            'slate'      => ['pri' => '#0F131A', 'sec' => '#F2F4F7', 'ac' => '#3E5171'],
        ];
    }
}
