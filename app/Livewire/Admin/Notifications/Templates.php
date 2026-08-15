<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\NotificationEvent;
use Livewire\Component;

class Templates extends Component
{
    public function render()
    {
        if (! auth()->user()->can('notification_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.notifications.templates', [
            'events' => NotificationEvent::orderBy('label')->get(),
        ])->layout('layouts.admin.admin');
    }
}
