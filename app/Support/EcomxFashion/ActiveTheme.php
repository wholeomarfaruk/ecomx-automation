<?php

namespace App\Support\EcomxFashion;

/**
 * Helper for the currently active theme package (see ThemeRegistry, which
 * discovers installed themes and tracks which one is active). Centralizes
 * theme-relative paths so nothing hardcodes 'ecomx-fashion' directly.
 */
class ActiveTheme
{
    public static function slug(): string
    {
        return ThemeRegistry::active();
    }

    /** Absolute path to public/{theme}/... */
    public static function publicPath(string $relative = ''): string
    {
        return public_path(static::slug() . ($relative !== '' ? '/' . ltrim($relative, '/') : ''));
    }

    /** Absolute path to resources/{theme}/... */
    public static function resourcePath(string $relative = ''): string
    {
        return resource_path(static::slug() . ($relative !== '' ? '/' . ltrim($relative, '/') : ''));
    }

    /** Livewire tag namespace prefix, e.g. 'ecomx-fashion.sections'. */
    public static function livewireNamespace(string $suffix = 'sections'): string
    {
        return static::slug() . '.' . $suffix;
    }
}
