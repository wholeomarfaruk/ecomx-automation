<?php

namespace App\FrontendEngine;

use Composer\Semver\Semver;
use Illuminate\Support\Facades\Route;

/**
 * Two responsibilities:
 *
 *  1. Engine discovery/loading — an "engine" (e.g. ecomxFashion) is a route
 *     file at routes/frontend/{Engine}.php that owns a set of stable route
 *     names. routes/web.php calls EngineManager::loadActive() once; it never
 *     contains theme- or engine-specific route definitions itself (see
 *     routes/frontend/ecomxFashion.php).
 *
 *  2. Theme validation — validates a theme package's manifest
 *     (resources/{slug}/theme.json) against what actually exists on disk
 *     and in the framework, before ThemeManager is allowed to activate it.
 *     Five layers, each building on the last:
 *
 *       1. Manifest      — theme.json parses, has required keys, and its
 *                          declared engine is a known, registered engine.
 *       2. Dependencies  — declared PHP/composer/npm/module/addon
 *                          constraints are satisfied (production deps
 *                          only — require-dev/devDependencies never
 *                          satisfy a theme's runtime requirement).
 *       3. Files         — every file the manifest points at exists.
 *       4. Classes       — every declared FQCN exists (autoloadable).
 *       5. Laravel       — every declared route name is registered.
 *
 * A theme that fails any check is not "ready"; see Engine::passed().
 */
class EngineManager
{
    protected const REQUIRED_MANIFEST_KEYS = ['manifest', 'pages', 'layouts', 'routes'];

    // === Engine discovery/loading ==========================================

    /**
     * Known engine slugs => absolute path of their route file. An engine is
     * simply a routes/frontend/{Slug}.php file; nothing else registers one,
     * so this list can never contain anything but a real, on-disk file —
     * closing off path traversal or arbitrary-file-inclusion via user input.
     */
    public static function engines(): array
    {
        $engines = [];

        foreach (glob(base_path('routes/frontend/*.php')) as $path) {
            $slug = basename($path, '.php');
            $engines[$slug] = $path;
        }

        return $engines;
    }

    public static function engineExists(string $engine): bool
    {
        return array_key_exists($engine, static::engines());
    }

    /** The single configured active engine. Only one engine's routes load per request. */
    public static function activeEngine(): string
    {
        return config('frontend-engine.active_engine', 'ecomxFashion');
    }

    /**
     * Loads the active engine's route file. Called once from routes/web.php.
     * Never builds the path from request input — only from the validated
     * engines() map, so this can't be tricked into including an arbitrary file.
     *
     * Alias of loadActiveThemeRoute() kept for backward compatibility with
     * existing callers (routes/web.php).
     */
    public static function loadActive(): void
    {
        static::loadActiveThemeRoute();
    }

    /**
     * Loads the route file owned by the currently active engine — i.e. the
     * engine of the currently active theme (see ThemeManager::active()).
     * Falls back to config('frontend-engine.active_engine') when the active
     * theme's manifest doesn't declare an engine, or no theme is active yet.
     * Never builds the path from request input — only from the validated
     * engines() map, so this can't be tricked into including an arbitrary file.
     */
    public static function loadActiveThemeRoute(): void
    {
        $engines = static::engines();
        $engine = static::activeThemeEngine();

        if (! array_key_exists($engine, $engines)) {
            return;
        }

        require $engines[$engine];
    }

    /** The engine declared by the currently active theme, falling back to activeEngine(). */
    public static function activeThemeEngine(): string
    {
        $theme = ThemeManager::active();
        $manifest = static::readManifest($theme);

        return $manifest['manifest']['engine'] ?? static::activeEngine();
    }

    // === Theme manifest / validation ========================================

    public static function manifestPath(string $slug): string
    {
        return resource_path($slug . '/theme.json');
    }

    public static function readManifest(string $slug): ?array
    {
        $path = static::manifestPath($slug);

        if (! file_exists($path)) {
            return null;
        }

        $json = json_decode(file_get_contents($path), true);

        return is_array($json) ? $json : null;
    }

    public static function validate(string $slug): Engine
    {
        $checks = [];
        $manifest = static::readManifest($slug);

        $checks[] = [
            'group' => 'Manifest',
            'label' => "resources/{$slug}/theme.json is valid JSON",
            'ok' => $manifest !== null,
            'detail' => $manifest === null ? 'File missing or not valid JSON.' : null,
        ];

        if ($manifest === null) {
            return new Engine($slug, $checks);
        }

        foreach (static::REQUIRED_MANIFEST_KEYS as $key) {
            $checks[] = [
                'group' => 'Manifest',
                'label' => "Manifest declares \"{$key}\"",
                'ok' => array_key_exists($key, $manifest),
                'detail' => array_key_exists($key, $manifest) ? null : "Missing top-level \"{$key}\" key.",
            ];
        }

        static::checkEngine($manifest, $checks);
        static::checkDependencies($manifest, $checks);
        static::checkFiles($manifest, $checks);
        static::checkClasses($manifest, $checks);
        static::checkRoutes($manifest, $checks);

        return new Engine($slug, $checks);
    }

    protected static function checkEngine(array $manifest, array &$checks): void
    {
        $engine = $manifest['manifest']['engine'] ?? null;

        $checks[] = [
            'group' => 'Manifest',
            'label' => 'Manifest declares "manifest.engine"',
            'ok' => $engine !== null,
            'detail' => $engine !== null ? null : 'Missing "manifest.engine" — a theme must declare which engine it belongs to.',
        ];

        if ($engine === null) {
            return;
        }

        $exists = static::engineExists($engine);

        $checks[] = [
            'group' => 'Manifest',
            'label' => "Engine \"{$engine}\" exists (routes/frontend/{$engine}.php)",
            'ok' => $exists,
            'detail' => $exists ? null : "No route file at routes/frontend/{$engine}.php for the declared engine.",
        ];
    }

    protected static function checkDependencies(array $manifest, array &$checks): void
    {
        $dependencies = $manifest['dependencies'] ?? [];

        if (! empty($dependencies['php'])) {
            $constraint = $dependencies['php'];
            $satisfied = Semver::satisfies(PHP_VERSION, $constraint);

            $checks[] = [
                'group' => 'Dependencies',
                'label' => "PHP {$constraint}",
                'ok' => $satisfied,
                'detail' => $satisfied ? null : 'Installed PHP is ' . PHP_VERSION . ", theme requires {$constraint}.",
            ];
        }

        $composerVersions = static::installedComposerVersions();

        foreach ($dependencies['composer'] ?? [] as $package => $constraint) {
            $installed = $composerVersions[$package] ?? null;
            $satisfied = $installed !== null && Semver::satisfies($installed, $constraint);

            $checks[] = [
                'group' => 'Dependencies',
                'label' => "composer package \"{$package}\" {$constraint}",
                'ok' => $satisfied,
                'detail' => $installed === null
                    ? "Package \"{$package}\" is not installed (or is a dev-only dependency)."
                    : ($satisfied ? null : "Installed version {$installed} does not satisfy {$constraint}."),
            ];
        }

        $npmVersions = static::installedNpmVersions();

        foreach ($dependencies['npm'] ?? [] as $package => $constraint) {
            $installed = $npmVersions[$package] ?? null;
            $satisfied = $installed !== null && Semver::satisfies($installed, $constraint);

            $checks[] = [
                'group' => 'Dependencies',
                'label' => "npm package \"{$package}\" {$constraint}",
                'ok' => $satisfied,
                'detail' => $installed === null
                    ? "Package \"{$package}\" is not in package.json dependencies (or is a devDependency)."
                    : ($satisfied ? null : "Installed version {$installed} does not satisfy {$constraint}."),
            ];
        }

        foreach ($dependencies['modules'] ?? [] as $module) {
            static::checkPluginDependency('module', $module, $checks);
        }

        foreach ($dependencies['addons'] ?? [] as $addon) {
            static::checkPluginDependency('addon', $addon, $checks);
        }
    }

    /**
     * Modules/addons are not implemented as an installable package system in
     * this app yet — there's no registry of "installed, enabled modules" to
     * check against. Until one exists, a declared module/addon can never be
     * satisfied, so a required one always fails and an optional one always
     * reports as a warning. This is intentionally honest rather than a stub
     * that always passes.
     */
    protected static function checkPluginDependency(string $kind, array $dependency, array &$checks): void
    {
        $slug = $dependency['slug'] ?? '?';
        $version = $dependency['version'] ?? '*';
        $required = $dependency['required'] ?? true;

        $checks[] = [
            'group' => 'Dependencies',
            'label' => ($required ? "Required {$kind}" : "Optional {$kind}") . " \"{$slug}\" {$version}",
            'ok' => ! $required,
            'detail' => $required
                ? "No {$kind} registry exists yet — required {$kind} \"{$slug}\" cannot be verified as installed/enabled."
                : "Optional {$kind} \"{$slug}\" is not available (no {$kind} registry yet) — proceeding without it.",
        ];
    }

    /** @return array<string, string> composer package name => installed version, production (non-dev) only. */
    protected static function installedComposerVersions(): array
    {
        $path = base_path('composer.lock');

        if (! file_exists($path)) {
            return [];
        }

        $lock = json_decode(file_get_contents($path), true) ?? [];
        $versions = [];

        foreach ($lock['packages'] ?? [] as $package) {
            $versions[$package['name']] = ltrim($package['version'], 'v');
        }

        return $versions;
    }

    /** @return array<string, string> npm package name => installed version, production (non-dev) only. */
    protected static function installedNpmVersions(): array
    {
        $path = base_path('package.json');

        if (! file_exists($path)) {
            return [];
        }

        $json = json_decode(file_get_contents($path), true) ?? [];
        $declared = $json['dependencies'] ?? [];

        $versions = [];

        foreach (array_keys($declared) as $name) {
            $installed = static::resolveNpmInstalledVersion($name);

            if ($installed !== null) {
                $versions[$name] = $installed;
            }
        }

        return $versions;
    }

    protected static function resolveNpmInstalledVersion(string $name): ?string
    {
        $pkgPath = base_path('node_modules/' . $name . '/package.json');

        if (! file_exists($pkgPath)) {
            return null;
        }

        $json = json_decode(file_get_contents($pkgPath), true) ?? [];

        return $json['version'] ?? null;
    }

    protected static function checkFiles(array $manifest, array &$checks): void
    {
        foreach (static::declaredViewPaths($manifest) as $label => $relativePath) {
            $absolute = base_path($relativePath);

            $checks[] = [
                'group' => 'Files',
                'label' => $label,
                'ok' => file_exists($absolute),
                'detail' => file_exists($absolute) ? null : "Missing file: {$relativePath}",
            ];
        }
    }

    protected static function checkClasses(array $manifest, array &$checks): void
    {
        foreach (static::declaredClasses($manifest) as $label => $class) {
            $exists = class_exists($class);

            $checks[] = [
                'group' => 'Classes',
                'label' => $label,
                'ok' => $exists,
                'detail' => $exists ? null : "Class not found: {$class}",
            ];
        }
    }

    protected static function checkRoutes(array $manifest, array &$checks): void
    {
        $routeFile = $manifest['routes']['file'] ?? null;

        if ($routeFile !== null) {
            $exists = file_exists(base_path($routeFile));

            $checks[] = [
                'group' => 'Routes',
                'label' => "Route file \"{$routeFile}\" exists",
                'ok' => $exists,
                'detail' => $exists ? null : "Missing route file: {$routeFile}",
            ];
        }

        foreach ($manifest['pages'] ?? [] as $key => $page) {
            $name = $page['route'] ?? null;

            if ($name === null) {
                continue;
            }

            $exists = Route::has($name);

            $checks[] = [
                'group' => 'Routes',
                'label' => "Route \"{$name}\" is registered",
                'ok' => $exists,
                'detail' => $exists ? null : "No route named \"{$name}\" found in the router.",
            ];
        }
    }

    /** @return array<string, string> label => path relative to base_path() */
    protected static function declaredViewPaths(array $manifest): array
    {
        $paths = [];

        foreach ($manifest['pages'] ?? [] as $key => $page) {
            if (! empty($page['view'])) {
                $paths["Page \"{$key}\" view"] = $page['view'];
            }
        }

        foreach ($manifest['layouts'] ?? [] as $key => $layout) {
            if (! empty($layout['view'])) {
                $paths["Layout \"{$key}\" view"] = $layout['view'];
            }
        }

        return $paths;
    }

    /** @return array<string, string> label => FQCN */
    protected static function declaredClasses(array $manifest): array
    {
        $classes = [];

        foreach ($manifest['pages'] ?? [] as $key => $page) {
            if (! empty($page['livewire'])) {
                $classes["Page \"{$key}\" Livewire component"] = $page['livewire'];
            }
        }

        foreach ($manifest['layouts'] ?? [] as $key => $layout) {
            if (! empty($layout['livewire'])) {
                $classes["Layout \"{$key}\" Livewire component"] = $layout['livewire'];
            }
        }

        foreach ($manifest['sectionComponents']['registry'] ?? [] as $class) {
            $classes["Section component {$class}"] = $class;
        }

        return $classes;
    }
}
