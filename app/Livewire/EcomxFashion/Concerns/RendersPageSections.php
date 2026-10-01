<?php

namespace App\Livewire\EcomxFashion\Concerns;

use App\Support\EcomxFashion\ActiveTheme;
use App\Support\EcomxFashion\PageSectionRegistry;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Lazy;
use Livewire\Mechanisms\ComponentRegistry;

/**
 * For a page that renders its admin-managed sections (config('{theme}.pages')
 * + Admin > Page Sections) as Livewire components.
 */
trait RendersPageSections
{
    /**
     * Section keys to render — same list as PageSectionRegistry::activeKeysForPage(),
     * pre-filtered to drop any key with no registered Livewire tag or an
     * unresolvable component class, so one broken/misconfigured section
     * can't blank the whole page. Per-section missing *data* is a
     * separate, already-handled concern — each component's own mount()
     * falls back to its coded defaults/demo data when
     * PageSectionConfigRegistry::find() returns null (no saved file yet).
     * This only guards against the tag itself being unresolvable.
     */
    public function activeSections(string $page): array
    {
        $tags = config(ActiveTheme::slug() . '.sections', []);

        return array_values(array_filter(
            PageSectionRegistry::activeKeysForPage($page),
            function (string $key) use ($tags, $page) {
                $tag = $tags[$key] ?? null;

                if ($tag === null) {
                    return false;
                }

                try {
                    app(ComponentRegistry::class)->getClass($tag);
                } catch (\Throwable $e) {
                    Log::warning("ecomx-fashion: {$page} section [{$key}] resolves to Livewire tag [{$tag}] but its component class could not be resolved — hiding it.", ['exception' => $e->getMessage()]);

                    return false;
                }

                return true;
            }
        ));
    }

    /**
     * Mount parameters for a section: `lazy: 'on-load'` for a #[Lazy]
     * component, so it starts fetching as soon as it mounts (right after the
     * initial page load) instead of waiting for the visitor to scroll it
     * into view — below-fold sections then finish loading in the background
     * instead of popping in skeleton-then-content. Plus `page` for a section
     * that reads its saved content per page (a public $page property).
     */
    public function sectionParams(string $key, string $page): array
    {
        $tag = config(ActiveTheme::slug() . '.sections', [])[$key] ?? null;

        if ($tag === null) {
            return [];
        }

        try {
            $class = new \ReflectionClass(app(ComponentRegistry::class)->getClass($tag));
        } catch (\Throwable $e) {
            return [];
        }

        return array_filter([
            'lazy' => $class->getAttributes(Lazy::class) !== [] ? 'on-load' : null,
            'page' => $class->hasProperty('page') ? $page : null,
        ]);
    }
}
