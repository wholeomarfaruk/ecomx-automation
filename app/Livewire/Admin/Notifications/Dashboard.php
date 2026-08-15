<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        if (! auth()->user()->can('notification_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $total = NotificationLog::count();
        $sentToday = NotificationLog::whereDate('created_at', today())->count();
        $pending = NotificationLog::where('status', 'pending')->count();
        $failed = NotificationLog::where('status', 'failed')->count();

        $queuePending = null;

        if (config('queue.default') === 'database') {
            try {
                $queuePending = DB::table('jobs')->where('payload', 'like', '%SendNotificationChannelJob%')->count();
            } catch (\Throwable $e) {
                $queuePending = null;
            }
        }

        return view('livewire.admin.notifications.dashboard', [
            'total' => $total,
            'sentToday' => $sentToday,
            'pending' => $pending,
            'failed' => $failed,
            'queuePending' => $queuePending,
        ])->layout('layouts.admin.admin');
    }
}
