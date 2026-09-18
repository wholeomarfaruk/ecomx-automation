<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Component;

/** About page's 4-card feature grid — plain title+description cards, no icon (the design has none). */
class AboutFeatures extends Component
{
    protected const DEFAULT_ITEMS = [
        ['title' => 'Nationwide delivery', 'description' => 'We partner with trusted local couriers to reach all 64 districts, with reliable 24–72 hour delivery inside major cities and a clear estimate for everywhere else.'],
        ['title' => 'Cash on delivery', 'description' => 'Pay when it arrives at your door — no upfront risk. We also accept bKash, Nagad, and card payments for customers who prefer to pay online.'],
        ['title' => 'Genuine products, real reviews', 'description' => 'Every listing is checked before it goes live, and every review on our site comes from a verified purchase — no fake ratings, no surprises.'],
        ['title' => 'Support that answers', 'description' => 'Questions about an order, a return, or a product? Our support team is reachable by phone and live chat, every day — not a bot that loops you in circles.'],
    ];

    public array $items = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('about', 'about-features');

        $this->items = ! empty($config['items'])
            ? array_values(array_filter(
                $config['items'],
                fn (array $item) => trim($item['title'] ?? '') !== ''
            ))
            : static::DEFAULT_ITEMS;
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.about-features');
    }
}
