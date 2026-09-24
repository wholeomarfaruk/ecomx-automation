<?php

namespace App\Support\EcomxFashion;

/**
 * File-backed (no DB) registry of per-page section active/order state.
 * Storage: resources/{active theme}/config/page-sections.json — see ActiveTheme.
 * Kept under resources/ (never web-served) rather than public/, so the
 * directory name can never collide with the theme's own route prefix
 * under any server, including PHP's built-in dev server.
 *
 * Reads/writes use flock() so concurrent admin requests can't interleave and
 * corrupt the file; writes go to a temp file first and are renamed into place
 * (atomic on the same filesystem) so a crash mid-write never leaves a
 * half-written JSON file behind.
 */
class PageSectionRegistry
{
    protected static function path(): string
    {
        return ActiveTheme::resourcePath('config/page-sections.json');
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

    /** All sections for a page, ordered, each as ['key'=>, 'active'=>, 'order'=>]. */
    public static function forPage(string $page): array
    {
        $data = static::read();
        $sections = $data[$page] ?? [];

        usort($sections, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return $sections;
    }

    /** Active section keys for a page, in order — what the frontend renders. */
    public static function activeKeysForPage(string $page): array
    {
        return array_values(array_map(
            fn ($s) => $s['key'],
            array_filter(static::forPage($page), fn ($s) => ! empty($s['active']))
        ));
    }

    public static function pages(): array
    {
        return array_keys(static::read());
    }

    /**
     * Registers every config-declared page and section into page-sections.json
     * in one pass: a page missing from the file is seeded with all its config
     * sections (active, config order); a page already saved only gets the
     * config section keys it's missing appended at the end (active), so a new
     * section added to config('{theme}.pages') shows up without clobbering
     * the admin's existing toggles/order.
     *
     * @return string[] Page keys that were seeded or gained new sections.
     */
    public static function syncAllPages(): array
    {
        $data = static::read();
        $seeded = [];

        foreach (PageRegistry::all() as $page => $meta) {
            $configuredKeys = PageRegistry::sectionKeysForPage($page);

            if (! array_key_exists($page, $data)) {
                $data[$page] = array_map(fn (int $order, string $key) => [
                    'key' => $key,
                    'active' => true,
                    'order' => $order,
                ], array_keys($configuredKeys), $configuredKeys);
                $seeded[] = $page;

                continue;
            }

            $existingKeys = array_column($data[$page], 'key');
            $missingKeys = array_values(array_diff($configuredKeys, $existingKeys));

            if ($missingKeys === []) {
                continue;
            }

            $nextOrder = $data[$page] === [] ? 0 : max(array_column($data[$page], 'order')) + 1;

            foreach ($missingKeys as $key) {
                $data[$page][] = ['key' => $key, 'active' => true, 'order' => $nextOrder++];
            }
            $seeded[] = $page;
        }

        if ($seeded !== []) {
            static::write($data);
        }

        return $seeded;
    }

    public static function setActive(string $page, string $key, bool $active): void
    {
        $data = static::read();
        $data[$page] = $data[$page] ?? [];

        foreach ($data[$page] as &$section) {
            if ($section['key'] === $key) {
                $section['active'] = $active;
            }
        }
        unset($section);

        static::write($data);
    }

    /** @param string[] $orderedKeys Section keys in their new display order. */
    public static function reorder(string $page, array $orderedKeys): void
    {
        $data = static::read();
        $sections = $data[$page] ?? [];

        foreach ($sections as &$section) {
            $position = array_search($section['key'], $orderedKeys, true);
            if ($position !== false) {
                $section['order'] = $position;
            }
        }
        unset($section);

        $data[$page] = $sections;
        static::write($data);
    }
}
