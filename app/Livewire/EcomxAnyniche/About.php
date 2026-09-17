<?php

namespace App\Livewire\EcomxAnyniche;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Static "About us" page — no reactive state, kept as a Livewire component
 * only to match this theme's routing convention (every ecomx-anyniche page
 * is a Livewire component under the same layout).
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class About extends Component
{
    public function render()
    {
        $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

        return view('ecomx-anyniche.livewire.about')
            ->layout('ecomx-anyniche.layouts.ecomx_anyniche', [
                'title' => "About us — {$siteName}",
                'metaDescription' => "Learn about {$siteName} — who we are, how we deliver across Bangladesh, and why customers trust us.",
            ]);
    }
}
