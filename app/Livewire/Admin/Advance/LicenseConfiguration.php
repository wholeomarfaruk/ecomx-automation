<?php

namespace App\Livewire\Admin\Advance;

use App\Models\License;
use App\Models\UpdateLog;
use App\Services\LicenseService;
use Livewire\Component;

class LicenseConfiguration extends Component
{
    public string $license_key = '';
    public string $registered_domain = '';
    public string $registered_company = '';

    public ?array $updateCheckResult = null;

    public function mount(): void
    {
        $this->registered_domain = parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.url');
    }

    public function activate(): void
    {
        if (! auth()->user()->can('license_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'license_key' => ['required', 'string'],
            'registered_domain' => ['required', 'string'],
            'registered_company' => ['required', 'string'],
        ]);

        $result = app(LicenseService::class)->activate(
            $this->license_key,
            $this->registered_domain,
            $this->registered_company
        );

        $this->dispatch('toast', [
            'type' => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ]);
    }

    public function recheck(): void
    {
        if (! auth()->user()->can('license_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        app(LicenseService::class)->check();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'License status refreshed.']);
    }

    public function checkForUpdates(): void
    {
        if (! auth()->user()->can('license_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $this->updateCheckResult = app(LicenseService::class)->checkForUpdates();

        $this->dispatch('toast', [
            'type' => ($this->updateCheckResult['ok'] ?? false) ? 'success' : 'error',
            'message' => ($this->updateCheckResult['ok'] ?? false)
                ? ($this->updateCheckResult['update_available'] ? 'Update available.' : 'You are on the latest version.')
                : ($this->updateCheckResult['message'] ?? 'Unable to check for updates.'),
        ]);
    }

    public function render()
    {
        if (! auth()->user()->can('license_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.advance.license-configuration', [
            'licenseEnforced' => config('app.license_enforced'),
            'license' => License::current(),
            'currentVersion' => config('app.version'),
            'updateLogs' => UpdateLog::latest()->limit(10)->get(),
        ])->layout('layouts.admin.admin');
    }
}
