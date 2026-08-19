<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceVisit;
use App\Models\Product;
use Closure;
use DeviceDetector\DeviceDetector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DeviceTracker
{
    public const COOKIE_NAME = 'device_fingerprint';

    /** ~10 years — effectively permanent, matches "keep the visitor identity forever". */
    protected const COOKIE_LIFETIME_MINUTES = 60 * 24 * 365 * 10;

    /** Re-touch last_active_at/IP hit counters at most this often per device, to avoid a write on every single page view. */
    protected const ACTIVITY_THROTTLE_SECONDS = 300;

    /**
     * Route name → [content_type label, model class, slug column].
     * Add an entry here whenever a new visitable content type (blog, page, …)
     * gets a real route + model — visit tracking picks it up automatically.
     */
    protected const VISITABLE_ROUTES = [
        'ecomx-fashion.product'  => ['product', Product::class, 'slug'],
        'ecomx-fashion.category' => ['category', Category::class, 'slug'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = (string) $request->userAgent();

        if ($userAgent === '') {
            return $next($request);
        }

        $fingerprint = $request->cookie(self::COOKIE_NAME) ?: (string) Str::uuid();
        $isNewCookie = ! $request->cookie(self::COOKIE_NAME);

        $device = $this->resolveDevice($fingerprint, $userAgent, $request);

        $request->attributes->set('device', $device);

        $response = $next($request);

        // Cookies are headers — they must be attached before the response is
        // sent, so this stays here. Everything DB-related (activity touch,
        // IP hit counter, visit log) is deferred to terminate() below, which
        // Laravel calls only after the response has already reached the
        // browser (via fastcgi_finish_request under PHP-FPM) — so none of it
        // adds to the page's perceived load time.
        if ($isNewCookie) {
            $response->headers->setCookie(Cookie::make(
                self::COOKIE_NAME,
                $fingerprint,
                self::COOKIE_LIFETIME_MINUTES,
                path: '/',
                secure: $request->isSecure(),
                httpOnly: false,
                raw: false,
                sameSite: 'lax',
            ));
        }

        return $response;
    }

    /**
     * Called by Laravel after the response has been sent to the client
     * (see Illuminate\Foundation\Application::handleRequest) — this is what
     * actually makes the DB writes non-blocking for the visitor, without
     * needing a queue worker.
     */
    public function terminate(Request $request, Response $response): void
    {
        /** @var Device|null $device */
        $device = $request->attributes->get('device');

        if (! $device) {
            return;
        }

        $this->touchActivity($device, $request);

        if ($this->shouldLogVisit($request)) {
            $this->recordVisit($device, $request, $response);
        }
    }

    /**
     * Single upsert instead of SELECT-then-INSERT/UPDATE — halves the query
     * count on the hot path that runs on every request.
     *
     * Only device-identity columns (UA, browser, OS, IP, …) are touched on
     * conflict. is_trusted/is_allowed are deliberately excluded from the
     * UPDATE clause below — an admin's trust/block decision on an existing
     * device must never be silently reset by the next page view. Same for
     * created_at (first-seen timestamp) and last_active_at, which is handled
     * separately by touchActivity() on a throttle so it isn't written on
     * every single request.
     */
    protected function resolveDevice(string $fingerprint, string $userAgent, Request $request): Device
    {
        $dd = new DeviceDetector($userAgent);
        $dd->parse();

        $userId = auth()->check() ? auth()->id() : null;
        $now    = now();

        DB::statement(
            'INSERT INTO devices
                (fingerprint, user_id, user_agent, sec_ch_ua, device_type, platform,
                 device_brand, device_model, operating_system, os_version,
                 browser, browser_version, language, ip_address,
                 is_trusted, is_allowed, last_active_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                user_id = COALESCE(VALUES(user_id), user_id),
                user_agent = VALUES(user_agent),
                sec_ch_ua = VALUES(sec_ch_ua),
                device_type = VALUES(device_type),
                platform = VALUES(platform),
                device_brand = VALUES(device_brand),
                device_model = VALUES(device_model),
                operating_system = VALUES(operating_system),
                os_version = VALUES(os_version),
                browser = VALUES(browser),
                browser_version = VALUES(browser_version),
                language = VALUES(language),
                ip_address = VALUES(ip_address),
                updated_at = VALUES(updated_at)',
            [
                $fingerprint,
                $userId,
                $userAgent,
                $request->header('Sec-CH-UA'),
                $dd->getDeviceName() ?: 'unknown',
                $dd->getOs('name') ?: 'unknown',
                $dd->getBrandName() ?: null,
                $dd->getModel() ?: null,
                $dd->getOs('name') ?: null,
                $dd->getOs('version') ?: null,
                $dd->getClient('name') ?: null,
                $dd->getClient('version') ?: null,
                $request->header('Accept-Language'),
                $request->ip(),
                $now,
                $now,
                $now,
            ]
        );

        return Device::where('fingerprint', $fingerprint)->first();
    }

    /**
     * Re-touches last_active_at, last_login_at and the IP-hit counter — but
     * only once per throttle window per device, so rapid page-to-page
     * navigation doesn't write to the DB on every single request.
     */
    protected function touchActivity(Device $device, Request $request): void
    {
        $cacheKey = 'device_activity_touch:' . $device->id;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, self::ACTIVITY_THROTTLE_SECONDS);

        $updates = ['last_active_at' => now()];

        if (auth()->check() && ! $device->last_login_at) {
            $updates['last_login_at'] = now();
        }

        Device::where('id', $device->id)->update($updates);

        $this->recordIpAddress($device, $request->ip());
    }

    protected function recordIpAddress(Device $device, ?string $ip): void
    {
        if (! $ip) {
            return;
        }

        $now = now();

        // Single atomic upsert: insert hits=1 on first sight, or increment
        // hits in place on conflict — one query instead of a SELECT plus a
        // conditional INSERT/UPDATE.
        DB::statement(
            'INSERT INTO device_ip_addresses (device_id, ip_address, hits, first_seen_at, last_seen_at, created_at, updated_at)
             VALUES (?, ?, 1, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen_at = VALUES(last_seen_at), updated_at = VALUES(updated_at)',
            [$device->id, $ip, $now, $now, $now, $now]
        );
    }

    protected function shouldLogVisit(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->header('X-Livewire')) {
            return false;
        }

        if (str_starts_with($request->path(), 'livewire/')) {
            return false;
        }

        return true;
    }

    protected function recordVisit(Device $device, Request $request, Response $response): void
    {
        [$visitable, $contentType, $contentSlug, $contentTitle] = $this->resolveContent($request);

        DeviceVisit::create([
            'device_id'      => $device->id,
            'url'            => Str::limit($request->fullUrl(), 2048, ''),
            'route_name'     => $request->route()?->getName(),
            'method'         => $request->method(),
            'status_code'    => $response->getStatusCode(),
            'ip_address'     => $request->ip(),
            'referer'        => $request->header('referer') ? Str::limit($request->header('referer'), 2048, '') : null,
            'visitable_type' => $visitable?->getMorphClass(),
            'visitable_id'   => $visitable?->getKey(),
            'content_type'   => $contentType,
            'content_slug'   => $contentSlug,
            'content_title'  => $contentTitle,
            'created_at'     => now(),
        ]);
    }

    /**
     * @return array{0: ?Model, 1: ?string, 2: ?string, 3: ?string}
     */
    protected function resolveContent(Request $request): array
    {
        $routeName = $request->route()?->getName();

        if (! $routeName || ! isset(self::VISITABLE_ROUTES[$routeName])) {
            return [null, null, null, null];
        }

        [$contentType, $modelClass, $slugColumn] = self::VISITABLE_ROUTES[$routeName];

        $slug = $request->route($slugColumn) ?? $request->route('slug');

        if (! $slug) {
            return [null, $contentType, null, null];
        }

        /** @var Model|null $model */
        $model = $modelClass::query()->where($slugColumn, $slug)->first();

        $title = $model?->name ?? $model?->title ?? null;

        return [$model, $contentType, (string) $slug, $title];
    }
}
