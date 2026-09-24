<?php

namespace App\Livewire\Admin\Frontend;

use App\Support\EcomxFashion\ThemeRegistry;
use Livewire\Component;

class Pages extends Component
{
    /** Studly-cased theme namespace, e.g. 'ecomx-anyniche' -> 'EcomxAnyniche'. Matches PageSectionManager's resolution. */
    protected static function themeNamespace(): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', ThemeRegistry::active())));
    }

    protected static function pageRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageRegistry';
    }

    protected static function pageSectionRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageSectionRegistry';
    }

    protected static function pageSeoRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageSeoRegistry';
    }

    /**
     * Generates/updates the active theme's page-sections.json and
     * page-seo.json (under storage/, see ActiveTheme::storagePath()) from the
     * theme's config schema: seeds every config-declared page not saved yet,
     * and appends config sections missing from already-saved pages. Never
     * overwrites existing admin-edited toggles/order/SEO.
     */
    public function syncAllPages(): void
    {
        $sections = static::pageSectionRegistry()::syncAllPages();
        $seo = static::pageSeoRegistry()::syncAllPages();
        $seeded = array_unique([...$sections, ...$seo]);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $seeded === []
                ? 'All pages already have their sections and SEO registered.'
                : 'Registered: ' . implode(', ', $seeded) . '.',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.frontend.pages', [
            'pages' => static::pageRegistry()::all(),
            'needsSync' => ! static::pageSectionRegistry()::isGenerated() || ! static::pageSeoRegistry()::isGenerated(),
        ])->layout('layouts.admin.admin');
    }
}
