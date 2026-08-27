<div>
    {{-- $componentTag is the template's mandatory root Livewire component's
         alias (App\LandingPageEngine\TemplateComponentRegistrar::rootComponentAlias()),
         e.g. "landingpage.basic-promo.root" — resolved from the page's
         slug by App\Livewire\LandingPageEngine\LandingPageRenderer, which
         also carries #[Layout('layouts.landingpage.landingpage')] — this
         view is injected into that layout's {{ '{{ $slot }}' }}
         automatically, no @extends/@include of the layout needed here.
         Mirrors the same shape as the storefront theme's own section
         loader (@livewire(config(ActiveTheme::slug().".sections.$section"), ...)
         in ecomx-fashion/livewire/home.blade.php) — just resolved from our
         own template registry instead of a config file. --}}
    @livewire($componentTag, ['landingPageId' => $landingPageId], key('landingpage-root-' . $landingPageId))
</div>
