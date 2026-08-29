<?php

namespace App\Livewire\EcomxFashion;

use App\Support\EcomxFashion\ActiveTheme;
use App\Support\EcomxFashion\PageSectionRegistry;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Lazy;
use Livewire\Mechanisms\ComponentRegistry;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Home extends Component
{
    /**
     * Section keys to render — same list as PageSectionRegistry::activeKeysForPage(),
     * pre-filtered to drop any key with no registered Livewire tag or an
     * unresolvable component class, so one broken/misconfigured section
     * can't blank the whole homepage. Per-section missing *data* is a
     * separate, already-handled concern — each component's own mount()
     * falls back to its coded defaults/demo data when
     * PageSectionConfigRegistry::find() returns null (no saved file yet).
     * This only guards against the tag itself being unresolvable.
     */
    public function activeSections(): array
    {
        $tags = config(ActiveTheme::slug() . '.sections', []);

        return array_values(array_filter(
            PageSectionRegistry::activeKeysForPage('home'),
            function (string $key) use ($tags) {
                $tag = $tags[$key] ?? null;

                if ($tag === null) {
                    return false;
                }

                try {
                    app(ComponentRegistry::class)->getClass($tag);
                } catch (\Throwable $e) {
                    Log::warning("ecomx-fashion: home section [{$key}] resolves to Livewire tag [{$tag}] but its component class could not be resolved — hiding it.", ['exception' => $e->getMessage()]);

                    return false;
                }

                return true;
            }
        ));
    }

    /**
     * Whether the given section key's Livewire component is #[Lazy] — if so,
     * the home view passes it `lazy: 'on-load'` so it starts fetching as
     * soon as it mounts (right after the initial page load) instead of
     * waiting for the visitor to scroll it into view. Below-fold sections
     * then finish loading in the background while the visitor is still
     * reading the top of the page, so scrolling down finds them already
     * rendered instead of popping in skeleton-then-content.
     */
    public function sectionIsLazy(string $key): bool
    {
        $tag = config(ActiveTheme::slug() . '.sections', [])[$key] ?? null;

        if ($tag === null) {
            return false;
        }

        try {
            $class = app(ComponentRegistry::class)->getClass($tag);
        } catch (\Throwable $e) {
            return false;
        }

        return (new \ReflectionClass($class))->getAttributes(Lazy::class) !== [];
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.home', [
            'sections' => $this->activeSections(),
        ]);
    }
}
