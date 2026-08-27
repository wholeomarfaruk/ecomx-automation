<?php

namespace App\LandingPageEngine;

use App\Models\LandingPageTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Registers each template's Livewire component(s):
 *
 * - The MANDATORY root component — filename declared in the template's
 *   config.json ("root_component"), defaulting to
 *   {StudlyTemplateKey}.php (e.g. BasicPromo.php for basic-promo) when not
 *   declared. See App\Models\LandingPageTemplate::rootComponentFilename().
 *   This is the component App\Livewire\LandingPageEngine\LandingPageRenderer
 *   dynamically @livewire()s for the whole page (see
 *   resources/views/livewire/landingpage-engine/landingpage-renderer.blade.php)
 *   — its own render() returns the template's blade view as its view.
 *   TemplateDiscoveryService already refuses to register a template in
 *   the DB at all if this file is missing, so by the time this class runs
 *   (only over LandingPageTemplate::active() rows), the file is
 *   guaranteed to exist — but the class_exists guard stays as a defensive
 *   check against a class-name/namespace mismatch inside the file itself.
 *
 * - Any other optional *.php file in the template's folder — child
 *   components mounted from inside template.blade.php via @livewire()
 *   (e.g. ProductOrderForm.php, one instance per product block). These
 *   are NOT the page's root component; a template can have zero, one, or
 *   many of them.
 *
 * Template packages live under resources/landingpage-templates/{key} and
 * storage/app/public/landingpage-templates/{key} — neither is in
 * composer.json's PSR-4 autoload map (only app/ is), so these *.php files
 * can't be class-autoloaded the normal way. This require()s each one
 * directly (once per request, guarded by class_exists) and registers it
 * with Livewire under a stable alias so template.blade.php can mount
 * child components as @livewire('landingpage.{key}.{component-slug}')
 * without needing to know the component's real FQCN, and so
 * App\Livewire\LandingPageEngine\LandingPageRenderer can dynamically
 * resolve+mount the root component's alias
 * ("landingpage.{key}.root", via rootComponentAlias() below) the same way
 * the storefront theme's Home component resolves a section's Livewire tag
 * from config() — see that class's docblock.
 */
class TemplateComponentRegistrar
{
    public function registerAll(): void
    {
        foreach (LandingPageTemplate::active()->get() as $template) {
            $this->registerRootComponent($template);
            $this->registerChildComponents($template);
        }
    }

    /**
     * FQCN convention: App\Livewire\LandingPageEngine\{StudlyKey}\{StudlyKey}
     * — under the same App\Livewire\LandingPageEngine namespace as
     * App\Livewire\LandingPageEngine\LandingPageRenderer (the public entry
     * point), rather than App\LandingPageEngine\Templates\* (the child-
     * component namespace, see registerChildComponents() below) — so a
     * template's one root component and its any-number-of child
     * components are never ambiguous by namespace alone.
     */
    public function rootComponentFqcn(LandingPageTemplate $template): string
    {
        $studlyKey = Str::studly($template->key);

        return "App\\Livewire\\LandingPageEngine\\{$studlyKey}\\{$studlyKey}";
    }

    public function rootComponentAlias(LandingPageTemplate $template): string
    {
        return "landingpage.{$template->key}.root";
    }

    protected function registerRootComponent(LandingPageTemplate $template): void
    {
        $filename = $template->rootComponentFilename();
        $path = $template->rootComponentPath();
        $fqcn = $this->rootComponentFqcn($template);

        if (! File::exists($path)) {
            // TemplateDiscoveryService should have already excluded this
            // template from the DB entirely — reaching here means the
            // file was deleted after discovery ran. Log and skip rather
            // than fatal the whole request.
            Log::error("[LandingPageEngine] Template '{$template->key}' is missing its mandatory root component ({$filename}) — was it removed after the last discovery run?");
            return;
        }

        if (! class_exists($fqcn)) {
            require_once $path;
        }

        if (! class_exists($fqcn)) {
            Log::error("[LandingPageEngine] Template '{$template->key}''s root component file ({$filename}) does not define the expected class {$fqcn}.");
            return;
        }

        Livewire::component($this->rootComponentAlias($template), $fqcn);
    }

    protected function registerChildComponents(LandingPageTemplate $template): void
    {
        $base = $template->basePath();
        $studlyKey = Str::studly($template->key);
        $rootComponentFile = $template->rootComponentFilename();

        if (! File::isDirectory($base)) {
            return;
        }

        foreach (File::files($base) as $file) {
            $filename = $file->getFilename();

            // getExtension()/getFilenameWithoutExtension() only strip the
            // last segment, so template.blade.php would otherwise match
            // (extension "php", basename "template.blade") and get
            // require_once'd as if it were a plain PHP class file — it
            // isn't compiled, so PHP just echoes its raw Blade markup as
            // literal output. Reject anything not ending in ".php" alone.
            if (! str_ends_with($filename, '.php') || str_ends_with($filename, '.blade.php')) {
                continue;
            }

            // The root component is registered separately above, under
            // its own fixed alias/namespace — skip it here so it isn't
            // double-registered under the generic child-component scheme.
            if ($filename === $rootComponentFile) {
                continue;
            }

            $className = $file->getFilenameWithoutExtension();
            $fqcn = "App\\LandingPageEngine\\Templates\\{$studlyKey}\\{$className}";

            if (! class_exists($fqcn)) {
                require_once $file->getPathname();
            }

            if (! class_exists($fqcn)) {
                continue;
            }

            $slug = Str::kebab($className);
            Livewire::component("landingpage.{$template->key}.{$slug}", $fqcn);
        }
    }
}
