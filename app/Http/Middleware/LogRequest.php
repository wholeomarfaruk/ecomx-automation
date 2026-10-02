<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Logs every web request (page loads, Livewire actions, AJAX) to
 * request_logs for admin → Visits Url: status code, response time, query
 * count, and the exception behind any error response.
 *
 * handle() only measures; the INSERT happens in terminate(), which Laravel
 * runs after the response has reached the browser (fastcgi_finish_request
 * under PHP-FPM) — so logging adds nothing to what the visitor waits for.
 * Runs after DeviceTracker, which sets the `device` request attribute.
 */
class LogRequest
{
    /** Livewire component whose own requests (filters, live refresh) are never logged. */
    private const SELF_COMPONENT = 'admin.visits.visits';

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('request-logs.enabled')) {
            return $next($request);
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $response = $next($request);

        // Measured here, when the response is ready — not in terminate(),
        // which runs after it has been sent and would include that time.
        $start = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT', microtime(true));
        $request->attributes->set('request_log', [
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            'query_count' => $queries,
        ]);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $measured = $request->attributes->get('request_log');

        if (! $measured || $this->shouldSkip($request)) {
            return;
        }

        try {
            $livewireAction = $this->livewireAction($request);

            if ($livewireAction === false) {
                return;
            }

            /** @var Device|null $device */
            $device = $request->attributes->get('device');
            $exception = $response->exception ?? null;

            RequestLog::create([
                'device_id'          => $device?->id,
                'user_id'            => $request->user()?->id,
                'method'             => $request->method(),
                'url'                => Str::limit($request->fullUrl(), 2048, ''),
                'path'               => Str::limit('/' . ltrim($request->path(), '/'), 512, ''),
                'route_name'         => $request->route()?->getName(),
                'area'               => $this->area($request, $livewireAction),
                'type'               => $this->type($request),
                'livewire_action'    => $livewireAction ? Str::limit($livewireAction, 500, '…') : null,
                'status_code'        => $response->getStatusCode(),
                'duration_ms'        => min($measured['duration_ms'], 4294967295),
                'query_count'        => min($measured['query_count'], 65535),
                'memory_mb'          => (int) round(memory_get_peak_usage(true) / 1048576),
                'ip_address'         => $request->ip(),
                'referer'            => $request->header('referer') ? Str::limit($request->header('referer'), 2048, '') : null,
                'user_agent'         => $request->userAgent() ? Str::limit($request->userAgent(), 512, '') : null,
                'exception_class'    => $exception instanceof Throwable ? get_class($exception) : null,
                'exception_message'  => $exception instanceof Throwable ? Str::limit($exception->getMessage(), 2000) : null,
                'exception_location' => $exception instanceof Throwable
                    ? Str::limit(str_replace(base_path() . DIRECTORY_SEPARATOR, '', $exception->getFile()) . ':' . $exception->getLine(), 512, '')
                    : null,
                'created_at'         => now(),
            ]);
        } catch (Throwable $e) {
            // Logging must never break anything — e.g. before the migration runs.
            Log::warning('Request log write failed: ' . $e->getMessage());
        }
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        return $path === 'up'
            // Livewire's own JS bundle / source map
            || ($request->isMethod('GET') && str_starts_with($path, 'livewire/'));
    }

    /**
     * "component@method" for each component in a Livewire update request
     * (events show as "component@event:name", bare property syncs as
     * "component@set:prop"); null for non-Livewire requests; false when the
     * request only touches the Visits page itself, which is skipped so its
     * filters and live refresh don't flood the log they display.
     */
    private function livewireAction(Request $request): string|false|null
    {
        if (! $request->hasHeader('X-Livewire')) {
            return null;
        }

        $parts = [];
        $onlySelf = true;

        foreach ((array) $request->input('components', []) as $component) {
            $snapshot = json_decode($component['snapshot'] ?? '', true);
            $name = $snapshot['memo']['name'] ?? '?';

            if ($name !== self::SELF_COMPONENT) {
                $onlySelf = false;
            }

            $actions = [];
            foreach ((array) ($component['calls'] ?? []) as $call) {
                $method = $call['method'] ?? '';
                $actions[] = $method === '__dispatch'
                    ? 'event:' . ($call['params'][0] ?? '?')
                    : $method;
            }

            if (! $actions && ! empty($component['updates'])) {
                $actions[] = 'set:' . implode(',', array_keys((array) $component['updates']));
            }

            $parts[] = $name . ($actions ? '@' . implode(',', $actions) : '');
        }

        if ($parts && $onlySelf) {
            return false;
        }

        return $parts ? implode(' | ', $parts) : null;
    }

    private function area(Request $request, ?string $livewireAction): string
    {
        $path = $request->path();

        if ($path === 'admin' || str_starts_with($path, 'admin/') || str_starts_with((string) $livewireAction, 'admin.')) {
            return 'admin';
        }

        if ($path === 'api' || str_starts_with($path, 'api/')) {
            return 'api';
        }

        return 'storefront';
    }

    private function type(Request $request): string
    {
        if ($request->hasHeader('X-Livewire')) {
            return 'livewire';
        }

        if ($request->ajax() || $request->expectsJson()) {
            return 'ajax';
        }

        return $request->isMethod('GET') ? 'page' : 'other';
    }
}
