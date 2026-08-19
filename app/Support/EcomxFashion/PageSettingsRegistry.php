<?php

namespace App\Support\EcomxFashion;

/**
 * File-backed (no DB) registry of per-page "Others" settings (currently just
 * published/draft visibility). Storage: resources/{active theme}/config/page-settings.json.
 * Mirrors PageSectionRegistry's read/write pattern (flock + atomic rename).
 */
class PageSettingsRegistry
{
    protected static function path(): string
    {
        return ActiveTheme::resourcePath('config/page-settings.json');
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

    public static function isPublished(string $page): bool
    {
        return static::read()[$page]['published'] ?? true;
    }

    public static function setPublished(string $page, bool $published): void
    {
        $data = static::read();
        $data[$page]['published'] = $published;

        static::write($data);
    }
}
