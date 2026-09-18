<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Models\Setting;
use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Component;

/** About page "Our story" narrative block — heading + rich text body. */
class AboutStory extends Component
{
    public string $title = '';
    public string $body = '';

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('about', 'about-story');
        $siteName = Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

        $this->title = $config['title'] ?? 'Our story';
        $this->body = $config['body'] ?? static::defaultBody($siteName);
    }

    protected static function defaultBody(string $siteName): string
    {
        return <<<HTML
            <p>
                {$siteName} started with a simple frustration shared by online shoppers across Bangladesh: too many stores promise fast delivery and genuine products, but too few actually deliver on it.
                We set out to fix that — sourcing directly, checking every order before it ships, and keeping cash on delivery available everywhere we operate, because trust has to be earned before it's asked for.
            </p>
            <p>
                Today we serve customers in every division of the country, from Dhaka and Chattogram to smaller upazilas that larger platforms often skip. Every order — big or small — gets the same care.
            </p>
            HTML;
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.about-story');
    }
}
