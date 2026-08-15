<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdateApplied extends Notification
{
    use Queueable;

    public function __construct(
        public string $fromVersion,
        public string $toVersion,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Application updated to v{$this->toVersion}")
            ->line("The application was automatically updated from v{$this->fromVersion} to v{$this->toVersion}.")
            ->line('The update completed successfully and passed all health checks.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'from_version' => $this->fromVersion,
            'to_version' => $this->toVersion,
        ];
    }
}
