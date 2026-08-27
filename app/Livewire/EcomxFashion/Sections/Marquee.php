<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Component;

/** Eager (not #[Lazy]) — sits directly under Hero, so it should appear immediately too, not pop in after. */
class Marquee extends Component
{
    protected const DEFAULT_ITEMS = [
        'Free delivery over ৳5,000',
        '⚡ Flash Sale live now',
        'New drops every week',
        'bKash · Nagad · COD',
        'Made in Bangladesh',
    ];

    public array $items = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'marquee');

        $this->items = ! empty($config['items'])
            ? array_values(array_filter(array_column($config['items'], 'text')))
            : static::DEFAULT_ITEMS;
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.marquee');
    }
}
