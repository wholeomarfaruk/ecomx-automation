<?php

namespace App\Livewire\LandingPageEngine;

use App\LandingPageEngine\TemplateComponentRegistrar;
use App\Models\LandingPage;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * App\Livewire\LandingPageEngine\LandingPageRenderer — single public
 * entry point for every landing page — catches the slug, identifies the
 * page's template, and resolves that template's mandatory root
 * component's Livewire tag, then hands it to
 * resources/views/livewire/landingpage-engine/landingpage-renderer.blade.php
 * to @livewire() it dynamically. Mirrors the same shape as the storefront
 * theme's own section loader (App\Livewire\EcomxFashion\Home +
 * ecomx-fashion/livewire/home.blade.php's
 * @livewire(config(ActiveTheme::slug().".sections.$section"), ...) —
 * "identify + resolve a tag, then let @livewire() mount it dynamically" —
 * except the tag source here is
 * TemplateComponentRegistrar::rootComponentAlias() (a predictable, computed
 * string keyed by the template's own key), not a config file, since a
 * landing page's template isn't a fixed, finite set of named sections the
 * way a theme's homepage sections are.
 *
 * #[Layout('layouts.landingpage.landingpage')] is set here on the
 * component itself, matching how App\Livewire\EcomxFashion\Home sets
 * #[Layout('ecomx-fashion.layouts.ecomx_fashion')] — that layout owns
 * <html>/<head>/<body>, title/meta, GTM, and
 * @livewireStyles/@livewireScripts. The #[Layout] attribute's own
 * constructor params are static literals only (no per-request variable
 * interpolation), so this page's dynamic title/meta are attached instead
 * via ->layoutData() on the view render() returns — the macro Livewire
 * registers specifically for passing runtime values into a #[Layout] view
 * (see vendor/livewire/livewire/src/Features/SupportPageComponents.php).
 */
#[Layout('layouts.landingpage.landingpage')]
class LandingPageRenderer extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        LandingPage::published()->where('slug', $slug)->firstOrFail();

        $this->slug = $slug;
    }

    public function render(TemplateComponentRegistrar $registrar): mixed
    {
        $landingPage = LandingPage::published()->where('slug', $this->slug)->firstOrFail();
        $template = $landingPage->template;

        return view('livewire.landingpage-engine.landingpage-renderer', [
            'componentTag' => $registrar->rootComponentAlias($template),
            'landingPageId' => $landingPage->id,
        ])->layoutData([
            'title' => $landingPage->seo['meta_title'] ?? $landingPage->title,
            'metaDescription' => $landingPage->seo['meta_description'] ?? null,
            'metaImage' => $landingPage->seo['meta_image'] ?? null,
        ]);
    }
}
