<?php

namespace App\Http\Middleware;

use App\Marketing\Attribution\AttributionService;
use App\Marketing\Attribution\MarketingAttribution;
use App\Marketing\Browser\BrowserEventPayloadBuilder;
use App\Marketing\Context\MarketingContext;
use App\Marketing\Context\MarketingContextBuilder;
use App\Marketing\Data\MarketingEventData;
use App\Marketing\Destinations\Meta\MetaBrowserCookies;
use App\Marketing\Events\PageView;
use App\Marketing\Identity\MarketingIdentity;
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
        if (! config('marketing.tracking.anonymous', true) || ! $this->shouldTrack($request)) {
            return $next($request);
        }

        // Before $next: events recorded while the page renders (ViewContent
        // in a Livewire mount) must already see a fresh _fbc from ?fbclid.
        $metaCookies = app(MetaBrowserCookies::class);
        $newMetaCookies = $metaCookies->prepare($request);

        /** @var Device|null $device */
        $device = $request->attributes->get('device');

        if ($device) {
            $this->prepareTracking($request, $device);
        }

        $response = $next($request);

        $metaCookies->attach($response, $newMetaCookies);

        /** @var MarketingContext|null $context */
        $context = $request->attributes->get('marketing_context');

        if ($context?->attribution) {
            $this->attachAttributionCookies($response, app(AttributionService::class), $context->attribution);
        }

        return $response;
    }

    /**
     * Context and the PageView are built BEFORE the page renders so the
     * layout can push this PageView into the dataLayer
     * (<x-marketing.page-view />) with the same event_id terminate() sends
     * to Conversions API — a browser Pixel tag and the server event then
     * deduplicate instead of counting the view twice.
     */
    private function prepareTracking(Request $request, Device $device): void
    {
        $context = app(MarketingContextBuilder::class)->build(
            deviceFingerprint: $device->fingerprint,
            customer: $this->resolveCustomer(),
        );

        $attribution = app(AttributionService::class)->resolve($context);
        $context = $context->withAttribution($attribution);

        $request->attributes->set('marketing_context', $context);

        if (! config('marketing.tracking.page_views', true)) {
            return;
        }

        $pageView = PageView::create();

        $request->attributes->set('marketing_page_view', $pageView);
        $request->attributes->set('marketing_page_view_payload', app(BrowserEventPayloadBuilder::class)->build(
            new MarketingEventData(
                event: $pageView,
                context: $context,
                // The browser payload never carries identity — no need to resolve it.
                identity: new MarketingIdentity(),
                attribution: $attribution,
            ),
        ));
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

        /** @var PageView|null $pageView */
        $pageView = $request->attributes->get('marketing_page_view');

        if ($pageView) {
            $this->recordPageView($pageView, $device, $customer, $session, $context);
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
        PageView $event,
        Device $device,
        ?Customer $customer,
        MarketingSession $session,
        MarketingContext $context,
    ): void {
        $service = app(MarketingEventService::class);

        $service->record(
            event: $event,
            context: $context,
            deviceId: $device->id,
            customerId: $customer?->id,
            sessionId: $session->id,
        );

        $service->dispatchDestinations($event, $context);
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
