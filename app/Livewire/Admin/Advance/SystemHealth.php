<?php

namespace App\Livewire\Admin\Advance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;

class SystemHealth extends Component
{
    public function render()
    {
        if (! auth()->user()->can('system_health.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.advance.system-health', [
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'mysqlVersion' => $this->mysqlVersion(),
            'redisStatus' => $this->redisStatus(),
            'queueStatus' => $this->queueStatus(),
            'schedulerStatus' => $this->schedulerStatus(),
            'storagePermission' => $this->storagePermission(),
            'cachePermission' => $this->cachePermission(),
            'diskUsage' => $this->diskUsage(),
            'memoryUsage' => $this->memoryUsage(),
            'cpuUsage' => $this->cpuUsage(),
            'environmentHealth' => $this->environmentHealth(),
        ])->layout('layouts.admin.admin');
    }

    protected function mysqlVersion(): array
    {
        try {
            $version = DB::selectOne('select version() as version')->version ?? null;

            return ['ok' => (bool) $version, 'value' => $version ?? 'Unavailable'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'value' => 'Unavailable'];
        }
    }

    protected function redisStatus(): array
    {
        if (empty(config('database.redis.default.host'))) {
            return ['ok' => null, 'value' => 'Not configured'];
        }

        try {
            $pong = Redis::connection()->ping();

            return ['ok' => true, 'value' => 'Connected'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'value' => 'Unreachable'];
        }
    }

    protected function queueStatus(): array
    {
        $driver = config('queue.default');
        $failed = null;

        if ($driver === 'database') {
            try {
                $failed = DB::table('failed_jobs')->count();
            } catch (\Throwable $e) {
                $failed = null;
            }
        }

        return [
            'ok' => true,
            'driver' => $driver,
            'failed' => $failed,
        ];
    }

    protected function schedulerStatus(): array
    {
        $lastRan = Cache::get('scheduler_last_ran_at');

        if (! $lastRan) {
            return ['ok' => false, 'value' => 'No heartbeat recorded yet'];
        }

        $diffInMinutes = $lastRan->diffInMinutes(now());

        return [
            'ok' => $diffInMinutes <= 5,
            'value' => $lastRan->diffForHumans(),
        ];
    }

    protected function storagePermission(): array
    {
        $path = storage_path();

        return [
            'ok' => is_writable($path),
            'value' => is_writable($path) ? 'Writable' : 'Not writable',
        ];
    }

    protected function cachePermission(): array
    {
        try {
            $key = '__system_health_cache_check';
            Cache::put($key, true, 5);
            $ok = Cache::get($key) === true;
            Cache::forget($key);

            return ['ok' => $ok, 'value' => $ok ? 'Working' : 'Failed'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'value' => 'Failed'];
        }
    }

    protected function diskUsage(): array
    {
        $total = @disk_total_space(base_path());
        $free = @disk_free_space(base_path());

        if (! $total || ! $free) {
            return ['ok' => null, 'percent' => null, 'value' => 'Unavailable'];
        }

        $used = $total - $free;
        $percent = round(($used / $total) * 100, 1);

        return [
            'ok' => $percent < 90,
            'percent' => $percent,
            'value' => sprintf('%s / %s used (%s%%)', $this->formatBytes($used), $this->formatBytes($total), $percent),
        ];
    }

    protected function memoryUsage(): array
    {
        return [
            'ok' => true,
            'value' => sprintf(
                'Current: %s / Peak: %s / Limit: %s',
                $this->formatBytes(memory_get_usage()),
                $this->formatBytes(memory_get_peak_usage()),
                ini_get('memory_limit')
            ),
        ];
    }

    protected function cpuUsage(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();

            if ($load) {
                return ['ok' => true, 'value' => sprintf('%.2f, %.2f, %.2f (1/5/15 min)', ...$load)];
            }
        }

        return ['ok' => null, 'value' => 'Unavailable on this OS'];
    }

    protected function environmentHealth(): array
    {
        $checks = [
            'App key set' => (bool) config('app.key'),
            'Debug mode disabled' => ! config('app.debug') || app()->environment('local'),
            '.env not publicly readable' => ! file_exists(public_path('.env')),
        ];

        $allOk = ! in_array(false, $checks, true);

        return [
            'ok' => $allOk,
            'checks' => $checks,
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return sprintf('%s %s', round($bytes / (1024 ** $power), 2), $units[$power]);
    }
}
