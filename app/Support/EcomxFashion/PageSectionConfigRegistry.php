<?php

namespace App\Support\EcomxFashion;

/**
 * File-backed (no DB) registry of per-section field *data* (arbitrary JSON,
 * shaped per section's field schema — see SectionSchema, which stays under
 * resources/{theme}/config/ as it's code-level field definitions, not data).
 *
 * Storage: one file per "{page}.{section}" under
 * public/storage/frontend/{active theme}/{page}.{section}.json — i.e.
 * storage/app/public/frontend/{theme}/... on disk, symlinked to public/storage
 * by `php artisan storage:link`. Deliberately public/ (not resources/) and
 * split per-section (not one combined file) so content edits are visible
 * assets independent of the codebase and never require a project deploy to
 * take effect, and a re-save of one section can't race a concurrent save of
 * another.
 *
 * Reads/writes use flock() so concurrent admin requests can't interleave and
 * corrupt a file; writes go to a temp file first and are renamed into place
 * (atomic on the same filesystem) so a crash mid-write never leaves a
 * half-written JSON file behind.
 */
class PageSectionConfigRegistry
{
    protected static function directory(): string
    {
        return storage_path('app/public/frontend/' . ActiveTheme::slug());
    }

    protected static function storageKey(string $page, string $section): string
    {
        return $page . '.' . $section;
    }

    protected static function path(string $page, string $section): string
    {
        return static::directory() . '/' . static::storageKey($page, $section) . '.json';
    }

    protected static function readFile(string $path): ?array
    {
        if (! file_exists($path)) {
            return null;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return null;
        }

        try {
            flock($handle, LOCK_SH);
            $contents = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $data = json_decode($contents, true);

        return is_array($data) ? $data : null;
    }

    protected static function writeFile(string $path, array $data): void
    {
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmp = $path . '.' . uniqid('', true) . '.tmp';
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        rename($tmp, $path);
    }

    /** Saved config for a page's section, or null if never configured. */
    public static function find(string $page, string $section): ?array
    {
        return static::readFile(static::path($page, $section));
    }

    public static function save(string $page, string $section, array $config): void
    {
        static::writeFile(static::path($page, $section), $config);
    }
}
