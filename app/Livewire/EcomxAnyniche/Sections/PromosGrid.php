<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * Grid of promo banners — ported from juwel-trade-corporation's
 * PromosSection. Admin-managed via a media_list field.
 */
#[Lazy]
class PromosGrid extends Component
{
    public array $promos = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'promos-grid');

        $this->promos = array_map(fn (array $item) => [
            'image' => $item['url'] ?? '',
            'link' => $item['link'] ?? '',
        ], $config['promos'] ?? []);
    }

    public function placeholder()
    {
        return view('ecomx-anyniche.livewire.sections.skeletons.promos-grid');
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.promos-grid');
    }
}
