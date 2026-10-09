<?php

namespace App\FrontendEngine;

use App\Support\EcomxFashion\ThemeRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Gates ThemeRegistry::setActive() behind EngineManager::validate() so a
 * theme with missing files/classes/routes can never become the active theme.
 * Any valid theme can be activated, whatever its engine — the active theme's
 * manifest.engine decides which route file loads (see
 * EngineManager::loadActiveThemeRoute()), so switching theme switches engine. ThemeRegistry itself stays the
 * source of truth for discovery and for where "active" is persisted
 * (storage/app/theme.json) — this class adds the validation gate in front
 * of activation, and clears theme-derived cache
 * once activation actually succeeds. Nothing here is transactional in the
 * database sense (ThemeRegistry's store is a flat JSON file, not a DB row);
 * "transactional" means validate-then-write, never write-then-validate.
 */
class ThemeManager
{
    /**
     * @throws RuntimeException if the theme fails validation.
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

        // Validation passed (incl. its engine's route file existing) — only now do we write.
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
        $engine ??= EngineManager::activeThemeEngine();
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

        // The route file loaded depends on the active theme's engine, so a
        // cached route table would keep serving the previous engine's routes.
        if (app()->routesAreCached()) {
            Artisan::call('route:clear');
        }
    }
}
