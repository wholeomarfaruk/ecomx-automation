<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GenericChannelNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $eventKey,
        public array $data = [],
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_key' => $this->eventKey,
            'data' => $this->data,
        ];
    }
}
