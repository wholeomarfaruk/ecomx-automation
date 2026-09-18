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
     * Seeds page-sections.json and page-seo.json for every config-declared
     * page that hasn't been visited/edited in the admin yet, so a new page
     * (e.g. one just added to config('{theme}.pages')) shows its sections
     * and SEO fields immediately instead of only after someone opens it
     * once. Never touches a page that already has saved state in either file.
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
        ])->layout('layouts.admin.admin');
    }
}
