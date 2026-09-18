<?php

namespace App\Livewire\EcomxAnyniche;

use App\Support\EcomxAnyniche\ActiveTheme;
use App\Support\EcomxAnyniche\PageSectionRegistry;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * "Privacy policy" page — hero is static (site name/date), the article body
 * is a single admin-editable section ('privacy-content', a rich_text field —
 * see PrivacyContent + SectionSchema) rendered through the same
 * PageSectionRegistry toggle/order system Home's sections use, via
 * Admin > Frontend Engine > Pages > Privacy Policy, instead of being a
 * hardcoded, non-editable Blade file.
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class PrivacyPolicy extends Component
{
    /** Active section keys for this page, pre-filtered to drop any unresolvable Livewire tag. */
    public function activeSections(): array
    {
        $tags = config(ActiveTheme::slug() . '.sections', []);

        return array_values(array_filter(
            PageSectionRegistry::activeKeysForPage('privacy-policy'),
            fn (string $key) => isset($tags[$key])
        ));
    }

    public function render()
    {
        $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

        return view('ecomx-anyniche.livewire.privacy-policy', [
            'sections' => $this->activeSections(),
        ])->layout('ecomx-anyniche.layouts.ecomx_anyniche', [
            'title' => "Privacy policy — {$siteName}",
            'metaDescription' => "How {$siteName} collects, uses, and protects your personal information.",
        ]);
    }
}
