<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * Single full-width promo banner strip — ported from
 * juwel-trade-corporation's PromoStripSection. Admin-managed via a
 * media_list field (this project has no Banner model).
 */
#[Lazy]
class PromoStripBanner extends Component
{
    public ?string $image = null;

    public string $link = '';

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'promo-strip-banner');
        $banner = $config['banner'][0] ?? null;

        $this->image = $banner['url'] ?? null;
        $this->link = $banner['link'] ?? '';
    }

    public function placeholder()
    {
        return view('ecomx-anyniche.livewire.sections.skeletons.promo-strip-banner');
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.promo-strip-banner');
    }
}
