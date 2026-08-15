<?php

namespace App\Notifications;

use App\Jobs\SendNotificationChannelJob;
use App\Models\NotificationEvent;
use App\Models\Setting;
use App\Models\User;

class NotificationManager
{
    public function notify(string $eventKey, User $recipient, array $data = []): void
    {
        $event = NotificationEvent::where('event_key', $eventKey)->first();

        if (! $event) {
            return;
        }

        foreach ($event->enabledChannels() as $channel) {
            if (! $this->channelGloballyEnabled($channel)) {
                continue;
            }

            SendNotificationChannelJob::dispatch($eventKey, $channel, $recipient, $data);
        }
    }

    protected function channelGloballyEnabled(string $channel): bool
    {
        return (bool) Setting::get("channel_{$channel}_enabled", true, 'notifications');
    }
}
