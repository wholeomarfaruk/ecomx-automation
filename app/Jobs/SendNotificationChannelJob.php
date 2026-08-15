<?php

namespace App\Jobs;

use App\Models\EmailTemplate;
use App\Models\NotificationEvent;
use App\Models\NotificationLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Notifications\GenericChannelNotification;
use App\Push\PushManager;
use App\Sms\SmsManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotificationChannelJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $eventKey,
        public string $channel,
        public User $recipient,
        public array $data = [],
    ) {
    }

    public function handle(): void
    {
        $log = NotificationLog::create([
            'event_key' => $this->eventKey,
            'channel' => $this->channel,
            'recipient' => $this->recipientLabel(),
            'status' => 'pending',
        ]);

        try {
            $provider = match ($this->channel) {
                'email' => $this->sendEmail(),
                'sms' => $this->sendSms(),
                'database', 'browser' => $this->sendDatabaseOrBrowser(),
                'push' => $this->sendPush(),
                default => null,
            };

            $log->update([
                'status' => 'sent',
                'provider' => $provider,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $log->retry_count + 1,
            ]);

            throw $e;
        }
    }

    protected function recipientLabel(): string
    {
        return match ($this->channel) {
            'sms' => (string) ($this->recipient->phone ?? $this->recipient->email),
            default => $this->recipient->email,
        };
    }

    protected function sendEmail(): ?string
    {
        $event = NotificationEvent::where('event_key', $this->eventKey)->first();
        $templateKey = $event?->email_template_key ?? $this->eventKey;

        EmailTemplate::send($templateKey, $this->recipient->email, array_merge([
            'name' => $this->recipient->name,
            'email' => $this->recipient->email,
            'app_name' => config('app.name'),
            'year' => now()->year,
        ], $this->data));

        return 'mail';
    }

    protected function sendSms(): ?string
    {
        if (! $this->recipient->phone) {
            return null;
        }

        $event = NotificationEvent::where('event_key', $this->eventKey)->first();
        $templateKey = $event?->sms_template_key ?? $this->eventKey;

        $template = SmsTemplate::where('key', $templateKey)->where('is_active', true)->first();
        $message = $template ? $template->render($this->data) : '';

        if ($message === '') {
            return null;
        }

        $response = app(SmsManager::class)->send($this->recipient->phone, $message, $this->eventKey);

        return $response->provider;
    }

    protected function sendDatabaseOrBrowser(): ?string
    {
        $this->recipient->notify(new GenericChannelNotification($this->eventKey, $this->data));

        return 'database';
    }

    protected function sendPush(): ?string
    {
        return app(PushManager::class)->sendToUser($this->recipient, $this->eventKey, $this->data);
    }
}
