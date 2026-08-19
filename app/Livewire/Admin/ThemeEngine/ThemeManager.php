<?php

namespace App\Livewire\Admin\ThemeEngine;

use App\FrontendEngine\Engine;
use App\FrontendEngine\ThemeManager as FrontendThemeManager;
use App\Support\EcomxFashion\ThemeRegistry;
use Livewire\Component;

class ThemeManager extends Component
{
    public ?string $reportSlug = null;

    public ?Engine $report = null;

    public function validate_(string $slug): void
    {
        $this->reportSlug = $slug;
        $this->report = FrontendThemeManager::validate($slug);
    }

    public function activate(string $slug): void
    {
        try {
            $this->report = FrontendThemeManager::activate($slug);
            $this->reportSlug = $slug;
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Theme switched — the storefront now runs on ' . (ThemeRegistry::all()[$slug]['name'] ?? $slug) . '.',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.theme-engine.theme-manager', [
            'themes' => ThemeRegistry::all(),
            'active' => ThemeRegistry::active(),
        ]);
    }
}
