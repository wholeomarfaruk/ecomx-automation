<?php

namespace App\Http\Middleware;

use App\Marketing\Attribution\AttributionService;
use App\Marketing\Attribution\MarketingAttribution;
use App\Marketing\Context\MarketingContext;
use App\Marketing\Context\MarketingContextBuilder;
use App\Marketing\Events\PageView;
use App\Marketing\Services\MarketingEventService;
use App\Marketing\Services\MarketingSessionResolver;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Marketing\MarketingSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after DeviceTracker (which resolves $request->attributes->get('device')
 * synchronously in its own handle()).
 *
 * Response headers (attribution cookies) MUST be set in handle(), before
 * $next($request) returns — Laravel calls terminate() only after the
 * response has already been sent to the client (see
 * Illuminate\Foundation\Http\Kernel::terminate(), invoked from
 * public/index.php after $response->send()), so mutating $response->headers
 * there has no effect on what the browser receives. Resolving attribution
 * is cheap (cookie/query parsing, no DB), so doing it in handle() adds no
 * meaningful latency. All actual DB writes (session/event/item/attribution
 * persistence) stay deferred to terminate(), same pattern as DeviceTracker.
 */
class MarketingTracker
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('marketing.tracking.anonymous', true) || ! $this->shouldTrack($request)) {
            return $response;
        }

        /** @var Device|null $device */
        $device = $request->attributes->get('device');

        if (! $device) {
            return $response;
        }

        $context = app(MarketingContextBuilder::class)->build(
            deviceFingerprint: $device->fingerprint,
            customer: $this->resolveCustomer(),
        );

        $attributionService = app(AttributionService::class);
        $attribution = $attributionService->resolve($context);

        $this->attachAttributionCookies($response, $attributionService, $attribution);

        $request->attributes->set('marketing_context', $context->withAttribution($attribution));

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        /** @var Device|null $device */
        $device = $request->attributes->get('device');

        /** @var MarketingContext|null $context */
        $context = $request->attributes->get('marketing_context');

        if (! $device || ! $context) {
            return;
        }

        $customer = $context->customer;

        $session = app(MarketingSessionResolver::class)->resolve($device, $customer, $context);

        if (config('marketing.tracking.page_views', true)) {
            $this->recordPageView($device, $customer, $session, $context);
        }
    }

    private function shouldTrack(Request $request): bool
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

        if (str_starts_with($request->path(), 'admin')) {
            return false;
        }

        return true;
    }

    private function resolveCustomer(): ?Customer
    {
        if (! auth()->check()) {
            return null;
        }

        return auth()->user()->customer;
    }

    private function recordPageView(
        Device $device,
        ?Customer $customer,
        MarketingSession $session,
        MarketingContext $context,
    ): void {
        $event = PageView::create();

        app(MarketingEventService::class)->record(
            event: $event,
            context: $context,
            deviceId: $device->id,
            customerId: $customer?->id,
            sessionId: $session->id,
        );
    }

    private function attachAttributionCookies(
        Response $response,
        AttributionService $attributionService,
        MarketingAttribution $attribution,
    ): void {
        // Attribution lifetime is deliberately shorter than the device
        // fingerprint's 10-year cookie (DeviceTracker::COOKIE_LIFETIME_MINUTES)
        // — identity and "how long a marketing touch stays creditable" are
        // different concerns and shouldn't share a lifetime.
        $lifetimeMinutes = (int) config('marketing.attribution_lifetime_days', 90) * 24 * 60;

        foreach ($attributionService->cookies($attribution) as $name => $value) {
            $response->headers->setCookie(Cookie::make(
                $name,
                $value,
                $lifetimeMinutes,
                path: '/',
                httpOnly: false,
                sameSite: 'lax',
            ));
        }
    }
}
