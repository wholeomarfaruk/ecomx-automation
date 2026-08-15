<?php

namespace App\Livewire\Admin\Email;

use App\Models\EmailLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Testing extends Component
{
    public string $testEmail = '';
    public ?array $lastResult = null;

    protected function guardManage(): void
    {
        if (! auth()->user()->can('email_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function sendTestEmail(): void
    {
        $this->guardManage();

        $this->validate(['testEmail' => ['required', 'email']]);

        $mailer = Setting::get('active_mailer', 'smtp', 'email');
        $subject = 'Test Email from ' . config('app.name');

        try {
            Mail::raw('This is a test email sent from your Email Configuration settings.', function ($message) use ($subject) {
                $message->to($this->testEmail)->subject($subject);
            });

            EmailLog::create([
                'mailer' => $mailer,
                'to' => $this->testEmail,
                'subject' => $subject,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $this->lastResult = ['success' => true, 'message' => 'Test email sent successfully.'];
        } catch (\Throwable $e) {
            EmailLog::create([
                'mailer' => $mailer,
                'to' => $this->testEmail,
                'subject' => $subject,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->lastResult = ['success' => false, 'message' => $e->getMessage()];
        }

        $this->dispatch('toast', [
            'type' => $this->lastResult['success'] ? 'success' : 'error',
            'message' => $this->lastResult['message'],
        ]);
    }

    public function testConnection(): void
    {
        $this->guardManage();

        $mailer = Setting::get('active_mailer', 'smtp', 'email');

        try {
            $transport = Mail::mailer($mailer)->getSymfonyTransport();

            if (method_exists($transport, 'start')) {
                $transport->start();
                $transport->stop();
            }

            $this->lastResult = ['success' => true, 'message' => "Connection to {$mailer} succeeded."];
        } catch (\Throwable $e) {
            $this->lastResult = ['success' => false, 'message' => $e->getMessage()];
        }

        $this->dispatch('toast', [
            'type' => $this->lastResult['success'] ? 'success' : 'error',
            'message' => $this->lastResult['message'],
        ]);
    }

    public function render()
    {
        if (! auth()->user()->can('email_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.email.testing')->layout('layouts.admin.admin');
    }
}
