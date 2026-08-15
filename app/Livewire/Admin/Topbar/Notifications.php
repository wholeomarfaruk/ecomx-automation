<?php

namespace App\Livewire\Admin\Topbar;

use Livewire\Component;

class Notifications extends Component
{
    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.admin.topbar.notifications', [
            'notifications' => auth()->user()->notifications()->latest()->limit(10)->get(),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
