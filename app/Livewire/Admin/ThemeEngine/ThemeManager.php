<?php

namespace App\Livewire\Admin\ThemeEngine;

use App\FrontendEngine\ThemeManager as FrontendThemeManager;
use App\Support\EcomxFashion\ThemeRegistry;
use Livewire\Component;

class ThemeManager extends Component
{
    public ?string $reportSlug = null;

    /** @var array<int, array{group: string, label: string, ok: bool, detail: ?string}>|null */
    public ?array $reportChecks = null;

    public ?bool $reportPassed = null;

    public function validate_(string $slug): void
    {
        $report = FrontendThemeManager::validate($slug);
        $this->reportSlug = $slug;
        $this->reportChecks = $report->checks;
        $this->reportPassed = $report->passed();
    }

    public function activate(string $slug): void
    {
        try {
            $report = FrontendThemeManager::activate($slug);
            $this->reportSlug = $slug;
            $this->reportChecks = $report->checks;
            $this->reportPassed = $report->passed();
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

    /** Checks grouped by their 'group' key, in encounter order — for rendering a report list. */
    protected function groupedChecks(): array
    {
        $groups = [];

        foreach ($this->reportChecks ?? [] as $check) {
            $groups[$check['group']][] = $check;
        }

        return $groups;
    }

    public function render()
    {
        return view('livewire.admin.theme-engine.theme-manager', [
            'themes' => ThemeRegistry::all(),
            'active' => ThemeRegistry::active(),
            'reportGrouped' => $this->groupedChecks(),
        ]);
    }
}
