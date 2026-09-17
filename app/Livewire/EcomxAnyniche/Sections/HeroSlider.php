<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * JTC-style hero: an auto-rotating slider (left) + up to 2 static side
 * banners (right). Ported from juwel-trade-corporation's
 * App\Livewire\Website\Storefront\HeroSection — admin-managed via media_list
 * fields (this project has no Slide/Banner models), same pattern Hero used
 * before it was replaced.
 */
#[Lazy]
class HeroSlider extends Component
{
    protected const DEFAULT_UNSPLASH_ID = 'photo-1445205170230-053b83016050';

    public array $slides = [];

    public array $sideBanners = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'hero-slider');

        $this->slides = ! empty($config['slides'])
            ? $this->mapMedia($config['slides'])
            : [['image' => config('ecomx-anyniche.unsplash') . static::DEFAULT_UNSPLASH_ID . '?q=80&w=1800&auto=format&fit=crop', 'link' => '', 'title' => '']];

        $this->sideBanners = ! empty($config['sideBanners'])
            ? $this->mapMedia($config['sideBanners'])
            : [];
    }

    protected function mapMedia(array $items): array
    {
        return array_map(fn (array $item) => [
            'image' => $item['url'] ?? '',
            'link' => $item['link'] ?? '',
            'title' => '',
        ], $items);
    }

    public function placeholder()
    {
        return view('ecomx-anyniche.livewire.sections.skeletons.hero-slider');
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.hero-slider');
    }
}
