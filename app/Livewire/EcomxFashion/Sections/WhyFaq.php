<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Support\EcomxFashion\Catalog;
use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class WhyFaq extends Component
{
    protected const DEFAULT_KICKER = 'Why Seldom Fashion';
    protected const DEFAULT_HEADING = 'Made rarely. Made well. Made for Bangladesh.';
    protected const DEFAULT_DESCRIPTION = "Since 2021 we've designed and finished every piece in our Dhaka atelier — Italian wool, pure silk and premium cotton, in small batches. Over 40,000 customers across all 64 districts trust us.";

    public string $kicker = self::DEFAULT_KICKER;
    public string $heading = self::DEFAULT_HEADING;
    public string $description = self::DEFAULT_DESCRIPTION;
    public array $stats = [];
    public array $faqs = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'why-faq');
        $this->kicker = $config['kicker'] ?? '' ?: static::DEFAULT_KICKER;
        $this->heading = $config['heading'] ?? '' ?: static::DEFAULT_HEADING;
        $this->description = $config['description'] ?? '' ?: static::DEFAULT_DESCRIPTION;

        $this->stats = ! empty($config['stats']) ? $config['stats'] : config('ecomx-fashion.trust');
        $this->faqs = ! empty($config['faqs']) ? $config['faqs'] : Catalog::faqs();
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.why-faq');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.why-faq', [
            'faqs' => $this->faqs,
            'trust' => $this->stats,
        ]);
    }
}
