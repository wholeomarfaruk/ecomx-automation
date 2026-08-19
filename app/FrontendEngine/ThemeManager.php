<?php

namespace App\FrontendEngine;

use App\Support\EcomxFashion\ThemeRegistry;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Gates ThemeRegistry::setActive() behind EngineManager::validate() so a
 * theme with missing files/classes/routes, or one that belongs to the wrong
 * engine, can never become the active theme. ThemeRegistry itself stays the
 * source of truth for discovery and for where "active" is persisted
 * (storage/app/theme.json) — this class adds the validation + engine
 * compatibility gate in front of activation, and clears theme-derived cache
 * once activation actually succeeds. Nothing here is transactional in the
 * database sense (ThemeRegistry's store is a flat JSON file, not a DB row);
 * "transactional" means validate-then-write, never write-then-validate.
 */
class ThemeManager
{
    /**
     * @throws RuntimeException if the theme fails validation, or belongs to
     *         a different engine than the one currently active.
     */
    public static function activate(string $slug): Engine
    {
        $report = EngineManager::validate($slug);

        if (! $report->passed()) {
            throw new RuntimeException(
                "Cannot activate theme [{$slug}]: failed " . count($report->failures()) . ' validation check(s). '
                . 'Active theme unchanged.'
            );
        }

        $manifest = EngineManager::readManifest($slug);
        $themeEngine = $manifest['manifest']['engine'] ?? null;
        $activeEngine = EngineManager::activeEngine();

        if ($themeEngine !== $activeEngine) {
            throw new RuntimeException(
                "Cannot activate theme [{$slug}]: it belongs to engine [{$themeEngine}], "
                . "but the active engine is [{$activeEngine}]. Active theme unchanged."
            );
        }

        // Validation passed and engine matches — only now do we write.
        ThemeRegistry::setActive($slug);

        static::clearCache();

        return $report;
    }

    public static function validate(string $slug): Engine
    {
        return EngineManager::validate($slug);
    }

    public static function active(): string
    {
        return ThemeRegistry::active();
    }

    /** All themes for the given engine (or the active engine if omitted). */
    public static function all(?string $engine = null): array
    {
        $engine ??= EngineManager::activeEngine();
        $themes = [];

        foreach (ThemeRegistry::all() as $slug => $meta) {
            $manifest = EngineManager::readManifest($slug);

            if (($manifest['manifest']['engine'] ?? null) === $engine) {
                $themes[$slug] = $meta;
            }
        }

        return $themes;
    }

    protected static function clearCache(): void
    {
        try {
            Cache::tags(['frontend-engine'])->flush();
        } catch (\BadMethodCallException) {
            // Active cache store doesn't support tags (e.g. file/database driver) —
            // nothing theme-derived is cached outside the tag-scoped store today.
        }
    }
}
