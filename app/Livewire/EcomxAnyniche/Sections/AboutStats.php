<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Component;

/**
 * About page stat strip — its own stat_list, independent of the global
 * config('ecomx-anyniche.trust') array Home's trust badges also read (the
 * two used to share that same config key, so editing one silently changed
 * the other — this gives About its own editable copy instead).
 */
class AboutStats extends Component
{
    protected const DEFAULT_ITEMS = [
        ['val' => '10,000+', 'label' => 'Happy customers'],
        ['val' => '★ 4.8 / 5', 'label' => 'From verified reviews'],
        ['val' => '24–48h', 'label' => 'Fast local delivery'],
        ['val' => '100%', 'label' => 'Quality guaranteed'],
    ];

    public array $items = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('about', 'about-stats');

        $this->items = ! empty($config['items'])
            ? array_values(array_filter(
                $config['items'],
                fn (array $item) => trim($item['val'] ?? '') !== '' || trim($item['label'] ?? '') !== ''
            ))
            : static::DEFAULT_ITEMS;
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.about-stats');
    }
}
