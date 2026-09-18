<?php

namespace App\Support\EcomxAnyniche;

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

    /**
     * Default section list for a page — all its config-declared keys, active,
     * in config order. Used both to render a page that has never been saved
     * (see forPage()) and to seed setActive()/reorder() the first time either
     * mutates a page, so a toggle/reorder on an unsaved page updates the
     * config-declared sections instead of writing an empty list that then
     * masks them (an empty saved array is a valid state — "actively cleared
     * to no sections" — and must be left alone, distinct from "never saved").
     */
    protected static function defaultsFromConfig(string $page): array
    {
        $configuredKeys = PageRegistry::sectionKeysForPage($page);

        return array_map(fn (int $order, string $key) => [
            'key' => $key,
            'active' => true,
            'order' => $order,
        ], array_keys($configuredKeys), $configuredKeys);
    }

    /**
     * All sections for a page, ordered, each as ['key'=>, 'active'=>, 'order'=>].
     * Falls back to defaultsFromConfig() when this page has never been saved
     * yet (no page-sections.json entry), so a fresh theme install renders its
     * sections immediately instead of needing an admin visit first to seed
     * the file.
     */
    public static function forPage(string $page): array
    {
        $data = static::read();
        $sections = $data[$page] ?? null;

        if ($sections === null) {
            return static::defaultsFromConfig($page);
        }

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
     * Writes every config-declared page's default section list into
     * page-sections.json in one pass, for any page not already present in
     * the file — so a fresh page (declared in config('{theme}.pages') but
     * never visited/toggled in the admin section editor yet) shows up
     * immediately instead of silently having "no sections registered" until
     * someone opens it once. Never touches a page that's already saved —
     * that's real admin-edited state (including a page deliberately cleared
     * to zero active sections) and must not be clobbered.
     *
     * @return string[] Page keys that were newly seeded by this call.
     */
    public static function syncAllPages(): array
    {
        $data = static::read();
        $seeded = [];

        foreach (PageRegistry::all() as $page => $meta) {
            if (array_key_exists($page, $data)) {
                continue;
            }

            $data[$page] = static::defaultsFromConfig($page);
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
        $data[$page] = $data[$page] ?? static::defaultsFromConfig($page);

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
        $sections = $data[$page] ?? static::defaultsFromConfig($page);

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
