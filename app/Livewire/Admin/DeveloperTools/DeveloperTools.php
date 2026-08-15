<?php

namespace App\Livewire\Admin\DeveloperTools;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DeveloperTools extends Component
{
    public function render()
    {
        if (! auth()->user()->can('developer_tools.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.developer-tools.developer-tools', [
            'phpInfo' => $this->phpInfo(),
            'laravelInfo' => $this->laravelInfo(),
            'installedPackages' => $this->installedPackages(),
            'composerVersion' => $this->composerVersion(),
            'phpExtensions' => $this->phpExtensions(),
            'serverInfo' => $this->serverInfo(),
            'databaseInfo' => $this->databaseInfo(),
            'queueInfo' => $this->queueInfo(),
            'cacheInfo' => $this->cacheInfo(),
        ])->layout('layouts.admin.admin');
    }

    protected function phpInfo(): array
    {
        return [
            'PHP Version' => PHP_VERSION,
            'Memory Limit' => ini_get('memory_limit'),
            'Upload Max Filesize' => ini_get('upload_max_filesize'),
            'Post Max Size' => ini_get('post_max_size'),
            'Max Execution Time' => ini_get('max_execution_time') . 's',
            'Max Input Time' => ini_get('max_input_time') . 's',
            'Display Errors' => ini_get('display_errors') ? 'On' : 'Off',
            'Default Timezone' => date_default_timezone_get(),
            'OPcache Enabled' => function_exists('opcache_get_status') && ini_get('opcache.enable') ? 'On' : 'Off',
        ];
    }

    protected function laravelInfo(): array
    {
        return [
            'Laravel Version' => app()->version(),
            'Environment' => app()->environment(),
            'Debug Mode' => config('app.debug') ? 'On' : 'Off',
            'Timezone' => config('app.timezone'),
            'Locale' => config('app.locale'),
            'App URL' => config('app.url'),
            'Livewire Version' => $this->packageVersion('livewire/livewire'),
        ];
    }

    protected function installedPackages(): array
    {
        $lockFile = base_path('composer.lock');

        if (! file_exists($lockFile)) {
            return [];
        }

        $lock = json_decode(file_get_contents($lockFile), true);
        $packages = collect($lock['packages'] ?? [])
            ->merge($lock['packages-dev'] ?? [])
            ->map(fn ($package) => [
                'name' => $package['name'],
                'version' => $package['version'],
            ])
            ->sortBy('name')
            ->values()
            ->all();

        return $packages;
    }

    protected function composerVersion(): string
    {
        if (! function_exists('shell_exec')) {
            return 'Unavailable';
        }

        $output = @shell_exec('composer --version 2>&1');

        return $output ? trim($output) : 'Unavailable';
    }

    protected function phpExtensions(): array
    {
        $extensions = get_loaded_extensions();
        sort($extensions);

        return $extensions;
    }

    protected function serverInfo(): array
    {
        return [
            'Operating System' => PHP_OS,
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'SAPI' => php_sapi_name(),
            'Hostname' => gethostname() ?: 'Unknown',
        ];
    }

    protected function databaseInfo(): array
    {
        $connectionName = config('database.default');
        $config = config("database.connections.{$connectionName}", []);

        $connected = false;
        try {
            DB::connection()->getPdo();
            $connected = true;
        } catch (\Throwable $e) {
            $connected = false;
        }

        return [
            'driver' => $connectionName,
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'database' => $config['database'] ?? null,
            'connected' => $connected,
        ];
    }

    protected function queueInfo(): array
    {
        $connectionName = config('queue.default');

        $pending = null;
        $failed = null;

        if ($connectionName === 'database') {
            try {
                $pending = DB::table('jobs')->count();
                $failed = DB::table('failed_jobs')->count();
            } catch (\Throwable $e) {
                $pending = null;
                $failed = null;
            }
        }

        return [
            'driver' => $connectionName,
            'pending' => $pending,
            'failed' => $failed,
        ];
    }

    protected function cacheInfo(): array
    {
        $store = config('cache.default');

        $working = false;
        try {
            $key = '__developer_tools_cache_check';
            Cache::put($key, true, 5);
            $working = Cache::get($key) === true;
            Cache::forget($key);
        } catch (\Throwable $e) {
            $working = false;
        }

        return [
            'store' => $store,
            'working' => $working,
        ];
    }

    protected function packageVersion(string $package): string
    {
        try {
            return \Composer\InstalledVersions::getVersion($package) ?? 'Unknown';
        } catch (\Throwable $e) {
            return 'Unknown';
        }
    }
}
