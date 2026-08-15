<?php

namespace App\Livewire\Admin\ActivityLog;

use App\Models\ActivityLog as ActivityLogModel;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLog extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterEvent = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $filterCauser = '';

    #[Url]
    public string $filterSubjectType = '';

    #[Url]
    public string $filterLogName = '';

    public function updatingSearch(): void          { $this->resetPage(); }
    public function updatingFilterEvent(): void     { $this->resetPage(); }
    public function updatingDateFrom(): void        { $this->resetPage(); }
    public function updatingDateTo(): void          { $this->resetPage(); }
    public function updatingFilterCauser(): void    { $this->resetPage(); }
    public function updatingFilterSubjectType(): void { $this->resetPage(); }
    public function updatingFilterLogName(): void   { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterEvent', 'dateFrom', 'dateTo', 'filterCauser', 'filterSubjectType', 'filterLogName']);
        $this->resetPage();
    }

    public function purgeOldLogs(int $days): void
    {
        if (! auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        $deleted = ActivityLogModel::where('created_at', '<', now()->subDays($days))->delete();
        $this->dispatch('toast', type: 'success', message: "Purged {$deleted} log " . ($deleted === 1 ? 'entry' : 'entries') . " older than {$days} days.");
    }

    public function export(): StreamedResponse
    {
        $filterCauser      = $this->filterCauser;
        $filterSubjectType = $this->filterSubjectType;

        $logs = ActivityLogModel::with('causer')
            ->when($this->search, fn ($q) => $q
                ->where('description', 'like', "%{$this->search}%")
                ->orWhere('subject_type', 'like', "%{$this->search}%")
            )
            ->when($this->filterEvent,       fn ($q) => $q->where('event', $this->filterEvent))
            ->when($this->filterLogName,     fn ($q) => $q->where('log_name', $this->filterLogName))
            ->when($filterSubjectType,       fn ($q) => $q->where('subject_type', $filterSubjectType))
            ->when($filterCauser,            fn ($q) => $q->whereHasMorph('causer', [\App\Models\User::class], fn ($uq) => $uq
                ->where('name', 'like', "%{$filterCauser}%")
                ->orWhere('email', 'like', "%{$filterCauser}%")
            ))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->get();

        $filename = 'activity-log-' . local_time(now())->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Channel', 'Event', 'Description', 'Subject Type', 'Subject ID', 'Causer Name', 'Causer Email', 'IP Address', 'User Agent', 'Date']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->log_name,
                    $log->event,
                    $log->description,
                    $log->subject_type ? class_basename($log->subject_type) : '',
                    $log->subject_id,
                    $log->causer?->name,
                    $log->causer?->email,
                    $log->ip_address,
                    $log->user_agent,
                    local_time($log->created_at)?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $filterCauser      = $this->filterCauser;
        $filterSubjectType = $this->filterSubjectType;

        $logs = ActivityLogModel::with('causer')
            ->when($this->search, fn ($q) => $q
                ->where('description', 'like', "%{$this->search}%")
                ->orWhere('subject_type', 'like', "%{$this->search}%")
            )
            ->when($this->filterEvent,       fn ($q) => $q->where('event', $this->filterEvent))
            ->when($this->filterLogName,     fn ($q) => $q->where('log_name', $this->filterLogName))
            ->when($filterSubjectType,       fn ($q) => $q->where('subject_type', $filterSubjectType))
            ->when($filterCauser,            fn ($q) => $q->whereHasMorph('causer', [\App\Models\User::class], fn ($uq) => $uq
                ->where('name', 'like', "%{$filterCauser}%")
                ->orWhere('email', 'like', "%{$filterCauser}%")
            ))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(20);

        // One query for all stats
        $stats = ActivityLogModel::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN event = 'created' THEN 1 ELSE 0 END) as created_count,
            SUM(CASE WHEN event = 'updated' THEN 1 ELSE 0 END) as updated_count,
            SUM(CASE WHEN event = 'deleted' THEN 1 ELSE 0 END) as deleted_count,
            SUM(CASE WHEN event = 'login'   THEN 1 ELSE 0 END) as login_count
        ")->first();

        $subjectTypes = ActivityLogModel::distinct()->whereNotNull('subject_type')->pluck('subject_type');
        $logNames     = ActivityLogModel::distinct()->whereNotNull('log_name')->pluck('log_name');

        return view('livewire.admin.activity-log.activity-log', compact('logs', 'stats', 'subjectTypes', 'logNames'))
            ->layout('layouts.admin.admin');
    }
}
