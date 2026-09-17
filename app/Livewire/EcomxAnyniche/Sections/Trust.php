<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Component;

/** Eager (not #[Lazy]) — small, text-only trust badges near the top of the page. */
class Trust extends Component
{
    /** Known icon identifiers the frontend view knows how to render inline. */
    public const ICONS = ['truck', 'shield', 'return', 'support'];

    protected const DEFAULT_ITEMS = [
        ['icon' => 'truck', 'title' => 'Nationwide delivery', 'description' => 'Doorstep delivery across Bangladesh'],
        ['icon' => 'shield', 'title' => 'Secure payment', 'description' => 'Popular & protected methods'],
        ['icon' => 'return', 'title' => 'Easy returns', 'description' => 'Customer-friendly return policy'],
        ['icon' => 'support', 'title' => 'Online support', 'description' => 'We reply as soon as possible'],
    ];

    public array $items = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'trust');

        $this->items = ! empty($config['items'])
            ? array_values(array_filter(
                $config['items'],
                fn (array $item) => trim($item['title'] ?? '') !== ''
            ))
            : static::DEFAULT_ITEMS;
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.trust');
    }
}
