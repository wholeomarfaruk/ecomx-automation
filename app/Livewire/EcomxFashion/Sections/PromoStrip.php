<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class PromoStrip extends Component
{
    protected const DEFAULT_UNSPLASH_ID = 'photo-1445205170230-053b83016050';

    public string $bannerUrl;

    public string $bannerLink;

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'promo-strip');
        $banner = $config['banner'][0] ?? null;

        $this->bannerUrl = $banner['url'] ?? (config('ecomx-fashion.unsplash') . static::DEFAULT_UNSPLASH_ID . '?q=80&w=1800&auto=format&fit=crop');
        $this->bannerLink = $banner['link'] ?? '';
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.promo-strip');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.promo-strip');
    }
}
