<?php

namespace App\Livewire\Admin\Sms;

use App\Models\SmsGatewayConfig;
use App\Sms\SmsManager;
use Livewire\Component;

class GatewayConfiguration extends Component
{
    public string $selectedGateway = '';
    public array $credentials = [];
    public string $sender_id = '';
    public string $default_country_code = '';
    public int $timeout = 30;
    public int $retry_attempts = 2;
    public ?int $rate_limit_per_minute = null;

    public function mount(): void
    {
        $active = SmsGatewayConfig::active();
        $this->selectedGateway = $active?->driver_key ?? config('sms.default');

        $this->loadGateway();
    }

    public function updatedSelectedGateway(): void
    {
        $this->loadGateway();
    }

    protected function loadGateway(): void
    {
        $config = SmsGatewayConfig::forDriver($this->selectedGateway);

        $this->credentials = $config->credentials ?? [];
        $this->sender_id = $config->sender_id ?? '';
        $this->default_country_code = $config->default_country_code ?? '';
        $this->timeout = $config->timeout;
        $this->retry_attempts = $config->retry_attempts;
        $this->rate_limit_per_minute = $config->rate_limit_per_minute;
    }

    public function save(): void
    {
        if (! auth()->user()->can('sms_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        SmsGatewayConfig::query()->update(['is_active' => false]);

        SmsGatewayConfig::forDriver($this->selectedGateway)->update([
            'credentials' => $this->credentials,
            'sender_id' => $this->sender_id,
            'default_country_code' => $this->default_country_code,
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retry_attempts,
            'rate_limit_per_minute' => $this->rate_limit_per_minute,
            'is_active' => true,
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Gateway configuration saved.']);
    }

    public function render()
    {
        if (! auth()->user()->can('sms_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $gateways = app(SmsManager::class)->installedGateways();
        $selectedMeta = collect($gateways)->firstWhere('key', $this->selectedGateway);
        $activeKey = SmsGatewayConfig::active()?->driver_key;

        return view('livewire.admin.sms.gateway-configuration', [
            'gateways' => $gateways,
            'selectedMeta' => $selectedMeta,
            'activeKey' => $activeKey,
        ])->layout('layouts.admin.admin');
    }
}
