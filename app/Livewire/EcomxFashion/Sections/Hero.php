<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Hero extends Component
{
    protected const DEFAULT_UNSPLASH_IDS = [
        'big' => [
            'photo-1483985988355-763728e1935b',
            'photo-1441986300917-64674bd600d8',
            'photo-1496747611176-843222e1e57c',
            'photo-1445205170230-053b83016050',
            'photo-1469334031218-e382a71b716b',
        ],
        'side' => [
            'photo-1509631179647-0177331693ae',
            'photo-1524504388940-b1c1722653e1',
            'photo-1515886657613-9f3515b0c78f',
            'photo-1534528741775-53994a69daeb',
            'photo-1487412720507-e7ab37603c6f',
        ],
    ];

    public array $bigSlides = [];
    public array $sideSlides = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'hero');

        $this->bigSlides = ! empty($config['bigSlides'])
            ? array_map(fn ($item) => ['url' => $item['url'], 'link' => $item['link'] ?? ''], $config['bigSlides'])
            : array_map(fn ($id) => ['url' => config('ecomx-fashion.unsplash') . $id . '?q=80&w=1600&auto=format&fit=crop', 'link' => ''], static::DEFAULT_UNSPLASH_IDS['big']);

        $this->sideSlides = ! empty($config['sideSlides'])
            ? array_map(fn ($item) => ['url' => $item['url'], 'link' => $item['link'] ?? ''], $config['sideSlides'])
            : array_map(fn ($id) => ['url' => config('ecomx-fashion.unsplash') . $id . '?q=80&w=800&auto=format&fit=crop', 'link' => ''], static::DEFAULT_UNSPLASH_IDS['side']);
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.hero');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.hero');
    }
}
