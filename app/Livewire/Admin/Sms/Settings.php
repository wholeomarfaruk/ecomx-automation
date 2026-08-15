<?php

namespace App\Livewire\Admin\Sms;

use App\Models\Setting;
use App\Sms\SmsManager;
use Livewire\Component;

class Settings extends Component
{
    public bool $enabled = true;
    public bool $queue_sending = true;
    public bool $retry_failed = true;
    public string $default_sender = '';
    public string $default_gateway = '';
    public ?int $balance_warning = null;
    public ?int $daily_limit = null;
    public ?int $monthly_limit = null;
    public bool $debug_mode = false;

    public function mount(): void
    {
        $this->enabled = (bool) Setting::get('enabled', true, 'sms');
        $this->queue_sending = (bool) Setting::get('queue_sending', true, 'sms');
        $this->retry_failed = (bool) Setting::get('retry_failed', true, 'sms');
        $this->default_sender = (string) Setting::get('default_sender', '', 'sms');
        $this->default_gateway = (string) Setting::get('default_gateway', config('sms.default'), 'sms');
        $this->balance_warning = Setting::get('balance_warning', null, 'sms');
        $this->daily_limit = Setting::get('daily_limit', null, 'sms');
        $this->monthly_limit = Setting::get('monthly_limit', null, 'sms');
        $this->debug_mode = (bool) Setting::get('debug_mode', false, 'sms');
    }

    public function save(): void
    {
        if (! auth()->user()->can('sms_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        Setting::set('enabled', $this->enabled, 'sms');
        Setting::set('queue_sending', $this->queue_sending, 'sms');
        Setting::set('retry_failed', $this->retry_failed, 'sms');
        Setting::set('default_sender', $this->default_sender, 'sms');
        Setting::set('default_gateway', $this->default_gateway, 'sms');
        Setting::set('balance_warning', $this->balance_warning, 'sms');
        Setting::set('daily_limit', $this->daily_limit, 'sms');
        Setting::set('monthly_limit', $this->monthly_limit, 'sms');
        Setting::set('debug_mode', $this->debug_mode, 'sms');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'SMS settings saved.']);
    }

    public function render()
    {
        if (! auth()->user()->can('sms_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.sms.settings', [
            'gateways' => app(SmsManager::class)->installedGateways(),
        ])->layout('layouts.admin.admin');
    }
}
