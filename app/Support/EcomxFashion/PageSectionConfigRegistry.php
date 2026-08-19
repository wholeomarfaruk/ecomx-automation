<?php

namespace App\Support\EcomxFashion;

/**
 * File-backed (no DB) registry of per-section field config (arbitrary JSON,
 * shaped per section's field schema — see SectionSchema). Storage:
 * resources/{active theme}/config/section-config.json, keyed by "{page}.{section}".
 * Mirrors PageSectionRegistry's read/write pattern (flock + atomic rename).
 */
class PageSectionConfigRegistry
{
    protected static function path(): string
    {
        return ActiveTheme::resourcePath('config/section-config.json');
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

    protected static function storageKey(string $page, string $section): string
    {
        return $page . '.' . $section;
    }

    /** Saved config for a page's section, or null if never configured. */
    public static function find(string $page, string $section): ?array
    {
        return static::read()[static::storageKey($page, $section)] ?? null;
    }

    public static function save(string $page, string $section, array $config): void
    {
        $data = static::read();
        $data[static::storageKey($page, $section)] = $config;

        static::write($data);
    }
}
