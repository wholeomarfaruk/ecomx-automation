<?php

namespace App\Livewire\Admin\Email;

use App\Models\Setting;
use Livewire\Component;

class Providers extends Component
{
    public string $category = 'smtp';
    public string $apiVendor = 'resend';

    protected array $apiVendors = [
        'resend' => 'Resend',
        'mailgun' => 'Mailgun',
        'ses' => 'Amazon SES',
        'postmark' => 'Postmark',
    ];

    public function mount(): void
    {
        $activeMailer = Setting::get('active_mailer', 'smtp', 'email');

        if (array_key_exists($activeMailer, $this->apiVendors)) {
            $this->category = 'api';
            $this->apiVendor = $activeMailer;
        } elseif ($activeMailer === 'custom') {
            $this->category = 'custom';
        } else {
            $this->category = 'smtp';
        }
    }

    public function selectCategory(string $category): void
    {
        if (! auth()->user()->can('email_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $this->category = $category;

        $mailer = match ($category) {
            'api' => $this->apiVendor,
            'custom' => 'custom',
            default => 'smtp',
        };

        Setting::set('active_mailer', $mailer, 'email');
        Setting::set('provider_category', $category, 'email');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Provider category updated.']);
    }

    public function selectApiVendor(string $vendor): void
    {
        if (! auth()->user()->can('email_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $this->apiVendor = $vendor;
        $this->category = 'api';

        Setting::set('active_mailer', $vendor, 'email');
        Setting::set('provider_category', 'api', 'email');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Provider updated to ' . $this->apiVendors[$vendor] . '.']);
    }

    public function render()
    {
        if (! auth()->user()->can('email_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.email.providers', [
            'apiVendors' => $this->apiVendors,
        ])->layout('layouts.admin.admin');
    }
}
