<?php

namespace App\Livewire\Admin\Visits;

use App\Models\RequestLog;
use App\Support\DeviceActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Visits Url: every logged web request (App\Http\Middleware\LogRequest)
 * with status/visitor/type filters, error + slow-request insights, and a
 * detail drawer. This component's own requests are never logged (see
 * LogRequest::SELF_COMPONENT), so live refresh doesn't feed itself.
 */
class Visits extends Component
{
    use WithPagination;

    /** requests | errors | slow | pages */
    #[Url]
    public string $tab = 'requests';

    /** 1h | 24h | 7d | 30d | custom */
    #[Url]
    public string $period = '24h';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $search = '';

    /** '' | 2xx | 3xx | 4xx | 5xx | errors */
    #[Url]
    public string $statusGroup = '';

    /** Exact code, e.g. 404 — overrides statusGroup when set. */
    #[Url]
    public string $statusCode = '';

    /** '' | active | inactive */
    #[Url]
    public string $visitor = '';

    #[Url]
    public string $method = '';

    /** '' | storefront | admin | api */
    #[Url]
    public string $area = '';

    /** '' | page | livewire | ajax | other */
    #[Url]
    public string $type = '';

    #[Url]
    public bool $slowOnly = false;

    #[Url]
    public string $deviceId = '';

    #[Url]
    public string $ip = '';

    public bool $live = false;

    public ?int $viewLogId = null;

    public function updating($property): void
    {
        if (! in_array($property, ['live', 'viewLogId'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusGroup', 'statusCode', 'visitor', 'method', 'area', 'type', 'slowOnly', 'deviceId', 'ip', 'dateFrom', 'dateTo']);
        $this->period = '24h';
        $this->resetPage();
    }

    /** Clicking a code/IP/device/path in a row narrows the list to it. */
    public function filterBy(string $field, string $value): void
    {
        match ($field) {
            'status' => $this->statusCode = $value,
            'ip'     => $this->ip = $value,
            'device' => $this->deviceId = $value,
            'path'   => $this->search = $value,
            default  => null,
        };

        $this->tab = 'requests';
        $this->viewLogId = null;
        $this->resetPage();
    }

    /** The Active now / 5xx / 4xx / Slow stat cards: jump to the request list with that filter. */
    public function quickFilter(string $filter): void
    {
        match ($filter) {
            'active'     => $this->visitor = 'active',
            '5xx', '4xx' => [$this->statusGroup = $filter, $this->statusCode = ''],
            'slow'       => $this->slowOnly = true,
            default      => null,
        };

        $this->tab = 'requests';
        $this->resetPage();
    }

    public function viewLog(int $id): void
    {
        $this->viewLogId = $id;
    }

    public function closeDrawer(): void
    {
        $this->viewLogId = null;
    }

    public function purgeOlderThan(int $days): void
    {
        if (! auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        $deleted = RequestLog::where('created_at', '<', now()->subDays(max(0, $days)))->delete();

        $this->dispatch('toast', type: 'success', message: 'Deleted ' . number_format($deleted) . ' request log ' . ($deleted === 1 ? 'entry' : 'entries') . '.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'visits-' . local_time(now())->format('Y-m-d-His') . '.csv';
        $query = $this->filteredQuery()->with('user:id,name')->latest('id')->limit(10000);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Time', 'Method', 'Status', 'URL', 'Livewire action', 'Area', 'Type', 'Duration (ms)', 'Queries', 'IP', 'Device ID', 'User', 'Exception']);

            foreach ($query->cursor() as $log) {
                fputcsv($handle, [
                    local_time($log->created_at)?->format('Y-m-d H:i:s'),
                    $log->method,
                    $log->status_code,
                    $log->url,
                    $log->livewire_action,
                    $log->area,
                    $log->type,
                    $log->duration_ms,
                    $log->query_count,
                    $log->ip_address,
                    $log->device_id,
                    $log->user?->name,
                    $log->exception_class ? $log->exception_class . ': ' . $log->exception_message : '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Period only — the base for stats and the insight tabs. */
    private function periodQuery(): Builder
    {
        $query = RequestLog::query();

        if ($this->period === 'custom') {
            return $query
                ->when($this->siteDayToUtc($this->dateFrom), fn ($q, $from) => $q->where('created_at', '>=', $from))
                ->when($this->siteDayToUtc($this->dateTo, endOfDay: true), fn ($q, $to) => $q->where('created_at', '<=', $to));
        }

        $since = match ($this->period) {
            '1h'  => now()->subHour(),
            '7d'  => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        return $query->where('created_at', '>=', $since);
    }

    /** A Y-m-d date picked in the site's timezone → that day's UTC start/end; null if blank or invalid. */
    private function siteDayToUtc(string $date, bool $endOfDay = false): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        $day = Carbon::createFromFormat('Y-m-d', $date, site_timezone());

        return ($endOfDay ? $day->endOfDay() : $day->startOfDay())->utc();
    }

    /** Period + every filter — the request list and CSV export. */
    private function filteredQuery(): Builder
    {
        $query = $this->periodQuery();
        $search = trim($this->search);

        $query->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
            ->where('path', 'like', "%{$search}%")
            ->orWhere('url', 'like', "%{$search}%")
            ->orWhere('route_name', 'like', "%{$search}%")
            ->orWhere('livewire_action', 'like', "%{$search}%")
            ->orWhere('exception_message', 'like', "%{$search}%")
        ));

        if (ctype_digit(trim($this->statusCode))) {
            $query->where('status_code', (int) trim($this->statusCode));
        } else {
            match ($this->statusGroup) {
                '2xx', '3xx', '4xx', '5xx' => $query->whereBetween('status_code', [(int) $this->statusGroup[0] * 100, (int) $this->statusGroup[0] * 100 + 99]),
                'errors' => $query->where('status_code', '>=', 400),
                default  => null,
            };
        }

        if ($this->visitor !== '') {
            $activeDevices = RequestLog::select('device_id')
                ->whereNotNull('device_id')
                ->where('created_at', '>=', DeviceActivity::threshold());

            $this->visitor === 'active'
                ? $query->whereIn('device_id', $activeDevices)
                : $query->where(fn ($w) => $w->whereNull('device_id')->orWhereNotIn('device_id', $activeDevices));
        }

        return $query
            ->when($this->method, fn ($q) => $q->where('method', $this->method))
            ->when($this->area, fn ($q) => $q->where('area', $this->area))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->slowOnly, fn ($q) => $q->where('duration_ms', '>=', config('request-logs.slow_ms')))
            ->when(ctype_digit($this->deviceId), fn ($q) => $q->where('device_id', (int) $this->deviceId))
            ->when($this->ip, fn ($q) => $q->where('ip_address', $this->ip));
    }

    public function render()
    {
        $slowMs = config('request-logs.slow_ms');

        $stats = $this->periodQuery()->selectRaw('
            COUNT(*) as total,
            COUNT(DISTINCT device_id) as visitors,
            SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as server_errors,
            SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) as client_errors,
            SUM(CASE WHEN duration_ms >= ? THEN 1 ELSE 0 END) as slow,
            AVG(duration_ms) as avg_ms
        ', [$slowMs])->first();

        // Devices with a request in the last 5 minutes — the "active now"
        // count and the green dot on each row.
        $activeDeviceIds = RequestLog::where('created_at', '>=', DeviceActivity::threshold())
            ->whereNotNull('device_id')
            ->distinct()
            ->pluck('device_id')
            ->flip();

        $logs = $errors = $slow = $pages = null;

        match ($this->tab) {
            'errors' => $errors = $this->periodQuery()
                ->where('status_code', '>=', 400)
                ->selectRaw('path, status_code, COUNT(*) as hits, COUNT(DISTINCT device_id) as visitors, MAX(created_at) as last_seen, MAX(exception_class) as exception_class, MAX(exception_message) as exception_message')
                ->groupBy('path', 'status_code')
                ->orderByDesc('status_code')
                ->orderByDesc('hits')
                ->limit(100)
                ->get(),
            'slow' => $slow = $this->periodQuery()
                ->selectRaw("COALESCE(livewire_action, path) as target, type, COUNT(*) as hits, ROUND(AVG(duration_ms)) as avg_ms, MAX(duration_ms) as max_ms, ROUND(AVG(query_count)) as avg_queries, SUM(CASE WHEN duration_ms >= ? THEN 1 ELSE 0 END) as slow_hits", [$slowMs])
                ->groupBy('target', 'type')
                ->orderByDesc('avg_ms')
                ->limit(100)
                ->get(),
            'pages' => $pages = $this->periodQuery()
                ->where('type', 'page')
                ->where('area', 'storefront')
                ->selectRaw('path, COUNT(*) as hits, COUNT(DISTINCT device_id) as visitors, ROUND(AVG(duration_ms)) as avg_ms, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors')
                ->groupBy('path')
                ->orderByDesc('hits')
                ->limit(100)
                ->get(),
            default => $logs = $this->filteredQuery()
                ->with(['device:id,browser,operating_system,device_type', 'user:id,name'])
                ->latest('id')
                ->paginate(30),
        };

        $viewing = $this->viewLogId
            ? RequestLog::with(['device', 'user:id,name,email'])->find($this->viewLogId)
            : null;

        return view('livewire.admin.visits.visits', [
            'stats'           => $stats,
            'activeNow'       => $activeDeviceIds->count(),
            'activeDeviceIds' => $activeDeviceIds,
            'logs'            => $logs,
            'errors'          => $errors,
            'slow'            => $slow,
            'pages'           => $pages,
            'viewing'         => $viewing,
            'slowMs'          => $slowMs,
            'loggingEnabled'  => config('request-logs.enabled'),
        ])->layout('layouts.admin.admin');
    }
}
