<?php

namespace App\Livewire\Admin\Advance;

use App\Models\FraudCheck;
use App\Models\Setting;
use App\Services\FraudShield\FraudShield;
use App\Services\FraudShield\FraudShieldException;
use App\Services\FraudShield\FraudShieldSettings;
use Livewire\Component;

/**
 * Advance → Fraud Checker: FraudShield API key and options, a connection
 * test (today's usage/package) and a manual phone lookup.
 */
class FraudChecker extends Component
{
    public bool $enabled = false;
    public bool $autoCheckList = true;
    public string $apiKey = '';
    public string $baseUrl = '';
    public int $cacheHours = 72;
    public int $timeout = 20;

    public ?array $usage = null;
    public string $lookupPhone = '';
    public ?int $lookupId = null;

    protected function authorizeManage(): void
    {
        $user = auth()->user();

        if (! $user?->hasRole('superadmin') && ! $user?->can('fraud_checker.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function mount(FraudShieldSettings $settings): void
    {
        $this->authorizeManage();

        $this->enabled = $settings->enabled();
        $this->autoCheckList = $settings->autoCheckList();
        $this->baseUrl = $settings->baseUrl();
        $this->cacheHours = $settings->cacheHours();
        $this->timeout = $settings->timeout();
    }

    public function save(): void
    {
        $this->authorizeManage();

        $this->validate([
            'apiKey' => 'nullable|string|max:255',
            'baseUrl' => 'required|url|max:255',
            'cacheHours' => 'required|integer|min:1|max:720',
            'timeout' => 'required|integer|min:5|max:60',
        ]);

        $group = FraudShieldSettings::GROUP;
        Setting::set('enabled', $this->enabled ? '1' : '0', $group);
        Setting::set('auto_check_list', $this->autoCheckList ? '1' : '0', $group);
        Setting::set('base_url', rtrim($this->baseUrl, '/'), $group);
        Setting::set('cache_hours', (string) $this->cacheHours, $group);
        Setting::set('timeout', (string) $this->timeout, $group);

        if (trim($this->apiKey) !== '') {
            FraudShieldSettings::storeApiKey(trim($this->apiKey));
            FraudShield::clearBlock();
            $this->apiKey = '';
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Fraud Checker settings saved.']);
    }

    public function removeKey(): void
    {
        $this->authorizeManage();

        FraudShieldSettings::storeApiKey(null);
        $this->usage = null;

        $this->dispatch('toast', ['type' => 'success', 'message' => 'API key removed.']);
    }

    public function testConnection(FraudShield $fraudShield): void
    {
        $this->authorizeManage();

        try {
            $this->usage = $fraudShield->dailyLimit();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Connected to FraudShield.']);
        } catch (FraudShieldException $e) {
            $this->usage = null;
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function lookup(FraudShield $fraudShield): void
    {
        $this->authorizeManage();
        $this->validate(['lookupPhone' => 'required|string|max:20']);

        try {
            // Re-uses a stored result like everywhere else — the quota is low.
            $this->lookupId = $fraudShield->check($this->lookupPhone)->id;
        } catch (FraudShieldException $e) {
            $this->lookupId = null;
            $this->addError('lookupPhone', $e->getMessage());
        }
    }

    public function render(FraudShieldSettings $settings)
    {
        return view('livewire.admin.advance.fraud-checker', [
            'maskedKey' => $settings->maskedKey(),
            'ready' => $settings->ready(),
            'blockedReason' => app(FraudShield::class)->blockedReason(),
            'lookupResult' => $this->lookupId ? FraudCheck::find($this->lookupId) : null,
            'recentChecks' => FraudCheck::latest('checked_at')->limit(10)->get(),
        ])->layout('layouts.admin.admin');
    }
}
