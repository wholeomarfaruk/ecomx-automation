<?php

namespace App\Livewire\EcomxAnyniche;

use App\Support\EcomxAnyniche\ActiveTheme;
use App\Support\EcomxAnyniche\PageSectionRegistry;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * "About us" page — split into 5 admin-editable sections (about-hero,
 * about-stats, about-story, about-features, about-cta — see SectionSchema)
 * rendered through the same PageSectionRegistry toggle/order system Home's
 * sections use, via Admin > Frontend Engine > Pages > About Us, instead of
 * being a hardcoded, non-editable Blade file.
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class About extends Component
{
    /** Active section keys for this page, pre-filtered to drop any unresolvable Livewire tag. */
    public function activeSections(): array
    {
        $tags = config(ActiveTheme::slug() . '.sections', []);

        return array_values(array_filter(
            PageSectionRegistry::activeKeysForPage('about'),
            fn (string $key) => isset($tags[$key])
        ));
    }

    public function render()
    {
        $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

        return view('ecomx-anyniche.livewire.about', [
            'sections' => $this->activeSections(),
        ])->layout('ecomx-anyniche.layouts.ecomx_anyniche', [
            'title' => "About us — {$siteName}",
            'metaDescription' => "Learn about {$siteName} — who we are, how we deliver across Bangladesh, and why customers trust us.",
        ]);
    }
}
