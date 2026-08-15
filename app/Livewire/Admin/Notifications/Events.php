<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\EmailTemplate;
use App\Models\NotificationEvent;
use App\Models\SmsTemplate;
use Livewire\Component;

class Events extends Component
{
    protected function guardManage(): void
    {
        if (! auth()->user()->can('notification_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function toggleChannel(int $eventId, string $channel): void
    {
        $this->guardManage();

        $event = NotificationEvent::findOrFail($eventId);
        $column = "channel_{$channel}";

        if (! in_array($channel, ['email', 'sms', 'push', 'browser', 'database'], true)) {
            return;
        }

        $event->update([$column => ! $event->{$column}]);
    }

    public function updateTemplate(int $eventId, string $field, string $value): void
    {
        $this->guardManage();

        if (! in_array($field, ['email_template_key', 'sms_template_key'], true)) {
            return;
        }

        NotificationEvent::findOrFail($eventId)->update([$field => $value ?: null]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Template mapping updated.']);
    }

    public function render()
    {
        if (! auth()->user()->can('notification_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.notifications.events', [
            'events' => NotificationEvent::orderBy('label')->get(),
            'emailTemplateKeys' => EmailTemplate::orderBy('key')->pluck('key'),
            'smsTemplateKeys' => SmsTemplate::orderBy('key')->pluck('key'),
        ])->layout('layouts.admin.admin');
    }
}
