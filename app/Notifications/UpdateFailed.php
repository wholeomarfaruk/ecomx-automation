<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdateFailed extends Notification
{
    use Queueable;

    public function __construct(
        public string $fromVersion,
        public string $toVersion,
        public string $reason,
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
            ->subject('Automatic update failed — rolled back')
            ->line("An automatic update from v{$this->fromVersion} to v{$this->toVersion} failed and was rolled back.")
            ->line("Reason: {$this->reason}")
            ->line('The application has been restored to its previous working state.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'from_version' => $this->fromVersion,
            'to_version' => $this->toVersion,
            'reason' => $this->reason,
        ];
    }
}
