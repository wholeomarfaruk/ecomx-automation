<?php

namespace App\Livewire\Admin\LandingPages;

use App\LandingPageEngine\TemplateDiscoveryService;
use App\Models\LandingPageTemplate;
use Livewire\Component;

class TemplateList extends Component
{
    public function rescan(TemplateDiscoveryService $service): void
    {
        $result = $service->discover();

        $message = count($result['registered']) . ' template(s) registered';
        if (count($result['skipped']) > 0) {
            $message .= ', ' . count($result['skipped']) . ' skipped (see logs)';
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    public function render(): mixed
    {
        return view('livewire.admin.landing-pages.template-list', [
            'templates' => LandingPageTemplate::orderBy('name')->get(),
        ])->layout('layouts.admin.admin');
    }
}
