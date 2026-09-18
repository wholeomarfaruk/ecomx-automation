<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Models\Setting;
use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Component;

/** About page hero — title + intro paragraph, admin-editable via Admin > Frontend Engine > Pages > About Us. */
class AboutHero extends Component
{
    public string $title = '';
    public string $intro = '';

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('about', 'about-hero');
        $siteName = Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

        $this->title = $config['title'] ?? "Bangladesh's online store, built for how you actually shop";
        $this->intro = $config['intro'] ?? "{$siteName} brings quality products to your doorstep — from Dhaka to every district — backed by cash on delivery, verified reviews, and a support team that actually picks up the phone.";
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.about-hero');
    }
}
