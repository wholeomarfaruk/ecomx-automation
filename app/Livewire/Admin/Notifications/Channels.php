<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\Setting;
use Livewire\Component;

class Channels extends Component
{
    public bool $email_enabled = true;
    public bool $sms_enabled = true;
    public bool $push_enabled = true;
    public bool $browser_enabled = true;
    public bool $database_enabled = true;

    public function mount(): void
    {
        $this->email_enabled = (bool) Setting::get('channel_email_enabled', true, 'notifications');
        $this->sms_enabled = (bool) Setting::get('channel_sms_enabled', true, 'notifications');
        $this->push_enabled = (bool) Setting::get('channel_push_enabled', true, 'notifications');
        $this->browser_enabled = (bool) Setting::get('channel_browser_enabled', true, 'notifications');
        $this->database_enabled = (bool) Setting::get('channel_database_enabled', true, 'notifications');
    }

    public function save(): void
    {
        if (! auth()->user()->can('notification_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        Setting::set('channel_email_enabled', $this->email_enabled, 'notifications');
        Setting::set('channel_sms_enabled', $this->sms_enabled, 'notifications');
        Setting::set('channel_push_enabled', $this->push_enabled, 'notifications');
        Setting::set('channel_browser_enabled', $this->browser_enabled, 'notifications');
        Setting::set('channel_database_enabled', $this->database_enabled, 'notifications');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Channel settings saved.']);
    }

    public function render()
    {
        if (! auth()->user()->can('notification_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.notifications.channels')->layout('layouts.admin.admin');
    }
}
