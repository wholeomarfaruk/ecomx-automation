<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Component;

/**
 * About page's bottom CTA — heading/subtitle/button labels are editable;
 * the button hrefs (route('ecomx-anyniche.track'), tel: link) stay fixed
 * since those point at real functionality, not content.
 */
class AboutCta extends Component
{
    public string $title = '';
    public string $subtitle = '';
    public string $trackLabel = '';
    public string $callLabel = '';

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('about', 'about-cta');

        $this->title = $config['title'] ?? 'Still have a question?';
        $this->subtitle = $config['subtitle'] ?? 'Our team is happy to help before or after you order.';
        $this->trackLabel = $config['trackLabel'] ?? 'Track an order';
        $this->callLabel = $config['callLabel'] ?? 'Call us';
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.about-cta');
    }
}
