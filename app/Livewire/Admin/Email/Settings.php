<?php

namespace App\Livewire\Admin\Email;

use App\Models\Setting;
use Livewire\Component;

class Settings extends Component
{
    public string $active_mailer = 'smtp';
    public string $from_name = '';
    public string $from_email = '';
    public string $reply_to_email = '';
    public bool $queue_emails = true;
    public bool $enabled = true;

    // SMTP credentials
    public string $host = '';
    public string $port = '';
    public string $username = '';
    public string $password = '';
    public string $encryption = 'tls';

    // API provider credentials
    public string $api_key = '';

    public function mount(): void
    {
        $this->active_mailer = Setting::get('active_mailer', 'smtp', 'email');
        $this->from_name = (string) Setting::get('from_name', config('mail.from.name'), 'email');
        $this->from_email = (string) Setting::get('from_email', config('mail.from.address'), 'email');
        $this->reply_to_email = (string) Setting::get('reply_to_email', '', 'email');
        $this->queue_emails = (bool) Setting::get('queue_emails', true, 'email');
        $this->enabled = (bool) Setting::get('enabled', true, 'email');

        $credentials = Setting::get("{$this->active_mailer}_credentials", [], 'email');

        if ($this->active_mailer === 'smtp') {
            $this->host = $credentials['host'] ?? '';
            $this->port = (string) ($credentials['port'] ?? '');
            $this->username = $credentials['username'] ?? '';
            $this->password = $credentials['password'] ?? '';
            $this->encryption = $credentials['encryption'] ?? 'tls';
        } else {
            $this->api_key = $credentials['api_key'] ?? '';
        }
    }

    public function save(): void
    {
        if (! auth()->user()->can('email_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'from_name' => ['required', 'string'],
            'from_email' => ['required', 'email'],
            'reply_to_email' => ['nullable', 'email'],
        ]);

        Setting::set('from_name', $this->from_name, 'email');
        Setting::set('from_email', $this->from_email, 'email');
        Setting::set('reply_to_email', $this->reply_to_email, 'email');
        Setting::set('queue_emails', $this->queue_emails, 'email');
        Setting::set('enabled', $this->enabled, 'email');

        if ($this->active_mailer === 'smtp') {
            Setting::set('smtp_credentials', [
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => $this->password,
                'encryption' => $this->encryption,
            ], 'email');
        } else {
            Setting::set("{$this->active_mailer}_credentials", [
                'api_key' => $this->api_key,
            ], 'email');
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Email settings saved.']);
    }

    public function render()
    {
        if (! auth()->user()->can('email_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.email.settings')->layout('layouts.admin.admin');
    }
}
