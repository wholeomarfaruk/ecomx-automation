<?php

namespace App\Listeners;

use App\Notifications\NotificationManager;

class NotificationEventListener
{
    public function handle(object $event): void
    {
        app(NotificationManager::class)->notify($event->eventKey, $event->user, $event->data);
    }
}
