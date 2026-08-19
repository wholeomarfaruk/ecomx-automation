<?php

namespace App\Support\EcomxFashion;

/**
 * Declarative registry of pages for the active theme (label/icon/route/sections),
 * read from config({theme}.pages). Distinct from PageSectionRegistry, which
 * tracks per-page section active/order *state* (file-backed JSON), not metadata.
 */
class PageRegistry
{
    /** All page keys => metadata for the active theme. */
    public static function all(): array
    {
        return config(ActiveTheme::slug() . '.pages', []);
    }

    /** Metadata for a single page, or null if unknown. */
    public static function find(string $page): ?array
    {
        return static::all()[$page] ?? null;
    }

    public static function exists(string $page): bool
    {
        return array_key_exists($page, static::all());
    }

    /** Ordered section keys declared for a page in config (not runtime state). */
    public static function sectionKeysForPage(string $page): array
    {
        return static::find($page)['sections'] ?? [];
    }
}
