<?php

namespace App\Support\EcomxFashion;

/**
 * File-backed (no DB) registry of per-page SEO fields (meta title/description/OG image).
 * Storage: resources/{active theme}/config/page-seo.json — see ActiveTheme.
 * Mirrors PageSectionRegistry's read/write pattern (flock + atomic rename).
 */
class PageSeoRegistry
{
    protected static function path(): string
    {
        return ActiveTheme::resourcePath('config/page-seo.json');
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

    /** SEO fields for a page: meta_title, meta_description, og_image. */
    public static function forPage(string $page): array
    {
        return array_merge([
            'meta_title' => '',
            'meta_description' => '',
            'og_image' => '',
        ], static::read()[$page] ?? []);
    }

    public static function save(string $page, array $fields): void
    {
        $data = static::read();
        $data[$page] = array_merge($data[$page] ?? [], array_intersect_key(
            $fields,
            array_flip(['meta_title', 'meta_description', 'og_image'])
        ));

        static::write($data);
    }
}
