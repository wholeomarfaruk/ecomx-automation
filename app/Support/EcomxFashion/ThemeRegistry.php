<?php

namespace App\Support\EcomxFashion;

/**
 * Discovers installed theme packages by scanning resources/{slug}/theme.json
 * (manifest.id/name/description — see resources/ecomx-fashion/theme.json) and
 * tracks which one is active. Active theme is persisted file-backed (same
 * flock + atomic-rename pattern as PageSectionRegistry/PaletteRegistry) at
 * storage/app/theme.json, independent of any single theme's own config file
 * so switching doesn't require editing config/ecomx-fashion.php by hand.
 */
class ThemeRegistry
{
    protected static function statePath(): string
    {
        return storage_path('app/theme.json');
    }

    protected static function readState(): array
    {
        $path = static::statePath();

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

    protected static function writeState(array $data): void
    {
        $path = static::statePath();
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmp = $path . '.' . uniqid('', true) . '.tmp';
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        rename($tmp, $path);
    }

    /** All installed theme packages: slug => ['name', 'description', 'version']. */
    public static function all(): array
    {
        $themes = [];

        foreach (glob(resource_path('*/theme.json')) as $manifestPath) {
            $slug = basename(dirname($manifestPath));
            $json = json_decode(file_get_contents($manifestPath), true) ?? [];
            $manifest = $json['manifest'] ?? [];
            $preview = $json['preview'] ?? [];

            $themes[$slug] = [
                'name' => $manifest['name'] ?? ucfirst($slug),
                'description' => $manifest['description'] ?? '',
                'version' => $manifest['version'] ?? null,
                'cover' => $preview['cover'] ?? null,
                'thumbnail' => $preview['thumbnail'] ?? null,
            ];
        }

        return $themes;
    }

    /** Active theme slug, falling back to config('ecomx-fashion.active_theme') for the very first read. */
    public static function active(): string
    {
        $installed = static::all();
        $stored = static::readState()['active'] ?? null;

        if ($stored !== null && array_key_exists($stored, $installed)) {
            return $stored;
        }

        $fallback = config('ecomx-fashion.active_theme', 'ecomx-fashion');

        return array_key_exists($fallback, $installed) ? $fallback : (array_key_first($installed) ?? $fallback);
    }

    public static function setActive(string $slug): void
    {
        if (! array_key_exists($slug, static::all())) {
            throw new \InvalidArgumentException("Unknown theme [{$slug}].");
        }

        static::writeState(['active' => $slug]);
    }
}
