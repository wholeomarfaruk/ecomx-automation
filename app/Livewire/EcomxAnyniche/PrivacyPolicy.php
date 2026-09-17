<?php

namespace App\Livewire\EcomxAnyniche;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Static "Privacy policy" page — no reactive state, kept as a Livewire
 * component only to match this theme's routing convention (every
 * ecomx-anyniche page is a Livewire component under the same layout).
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class PrivacyPolicy extends Component
{
    public function render()
    {
        $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

        return view('ecomx-anyniche.livewire.privacy-policy')
            ->layout('ecomx-anyniche.layouts.ecomx_anyniche', [
                'title' => "Privacy policy — {$siteName}",
                'metaDescription' => "How {$siteName} collects, uses, and protects your personal information.",
            ]);
    }
}
