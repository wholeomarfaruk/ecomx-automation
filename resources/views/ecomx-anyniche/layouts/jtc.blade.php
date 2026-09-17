<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', $site['site_name'] ?? 'Juwel Trade Corporation')</title>
    @yield('meta_data')
    @stack('meta')

    {{-- Favicon --}}
    @if (!empty($site['favicon']))
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $site['favicon']) }}">
    @else
        <link rel="icon" type="image/jpeg" href="{{ asset('images/jtc-logo.jpeg') }}">
    @endif

    {{-- Design fonts (Sora + Inter) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">

    {{-- =====================================================================
         External libraries (shared with legacy pages: shop, cart, product…).
         Loaded BEFORE the JTC storefront styles so the design's CSS wins the
         cascade and the JTC shell stays pixel-perfect.
         ===================================================================== --}}
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css"
        integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- Bootstrap CSS --}}
    <link href="{{ asset('frontend/library/bootstrap/bootstrap.min.css') }}" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    {{-- Swiper CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/library/swiper/swiper-bundle.min.css') }}">
    {{-- Fancybox CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/library/fancybox/fancybox.css') }}">
    {{-- notyf CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- Plyr CSS --}}
    <link rel="stylesheet" href="{{ asset('lib/plyr/plyr.css') }}" />

    <style>
        @font-face {
            font-family: 'SolaimanLipi';
            src: url("{{ asset('fonts/SolaimanLipi.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }
    </style>

    {{-- Legacy custom CSS (used by shop/product/cart Bootstrap markup) --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css' . '?v=1.0.5') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/style_new.css' . '?v=1.0.1') }}">

    {{-- JTC storefront design styles — loaded LAST so it overrides Bootstrap.
         (Alpine component registered via Livewire.) --}}
    @vite(['resources/sass/storefront/storefront.scss', 'resources/js/storefront/app.js'])

    @livewireStyles
    @stack('styles')

    @php
        $fbclid = request()->get('fbclid') ?? '';
        $fbclid_generated = '';
        try {
            $fbclid_generated = App\Helper\MetaHelper::format_new_fbc($fbclid);
        } catch (\Throwable $e) {
            // MetaHelper unavailable (e.g. no DB in local demo) — tracking degrades gracefully.
        }
    @endphp
    @push('scripts')
        <script>
            var custom_fbc = @json($fbclid_generated);
            if (custom_fbc) {
                localStorage.setItem('custom_fbc', custom_fbc);
            }
        </script>
    @endpush
    @php
        $sections = View::getSections();
        $segment = $sections['segment'] ?? 'default';
    @endphp

    @if (app()->environment('production'))
        {{-- TikTok Pixel Code Start --}}
        <script>
            ! function(w, d, t) {
                w.TiktokAnalyticsObject = t;
                var ttq = w[t] = w[t] || [];
                ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias",
                    "group", "enableCookie", "disableCookie", "holdConsent", "revokeConsent", "grantConsent"
                ], ttq.setAndDefer = function(t, e) {
                    t[e] = function() {
                        t.push([e].concat(Array.prototype.slice.call(arguments, 0)))
                    }
                };
                for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]);
                ttq.instance = function(t) {
                    for (
                        var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]);
                    return e
                }, ttq.load = function(e, n) {
                    var r = "https://analytics.tiktok.com/i18n/pixel/events.js",
                        o = n && n.partner;
                    ttq._i = ttq._i || {}, ttq._i[e] = [], ttq._i[e]._u = r, ttq._t = ttq._t || {}, ttq._t[e] = +new Date,
                        ttq._o = ttq._o || {}, ttq._o[e] = n || {};
                    n = document.createElement("script");
                    n.type = "text/javascript", n.async = !0, n.src = r + "?sdkid=" + e + "&lib=" + t;
                    e = document.getElementsByTagName("script")[0];
                    e.parentNode.insertBefore(n, e)
                };
                ttq.load('{{ config('conversionapi.tiktok_pixel_id') }}');
                ttq.page();
            }(window, document, 'ttq');
        </script>
        {{-- TikTok Pixel Code End --}}

        {{-- Google Tag Manager --}}
        <script>
            (function(w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({
                    'gtm.start': new Date().getTime(),
                    event: 'gtm.js'
                });
                var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s),
                    dl = l != 'dataLayer' ? '&l=' + l : '';
                j.async = true;
                j.src =
                    'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', '{{ config('conversionapi.gtm_id') }}');
        </script>
        {{-- End Google Tag Manager --}}
    @endif
</head>

<body>
    @if (app()->environment('production'))
        {{-- Google Tag Manager (noscript) --}}
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('conversionapi.gtm_id') }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        {{-- End Google Tag Manager (noscript) --}}
    @endif

    @php
        // Shared shell data. Homepage passes the full storefront payload; other
        // pages fall back to demo categories + empty product/slide lists so the
        // JTC header, off-canvas menu, cart drawer and footer still work.
        $categories = $categories ?? \App\Support\StorefrontData::categories();
        $productsJson = $productsJson ?? [];
        $slides = $slides ?? [];
        $heroBanners = $heroBanners ?? [];

        // "Shop by category" carousel (Alpine `categories`/`catItems`) only —
        // scoped to homepage_category = true, decorated to the {name, image}
        // shape the carousel markup expects. Falls back to the full $categories
        // list (already in that shape) when the homepage set isn't provided.
$carouselCategories = isset($homepageCategories)
    ? $homepageCategories
        ->map(
            fn($cat) => [
                'name' => \Illuminate\Support\Str::limit($cat->name, 28),
                'slug' => $cat->slug,
                'url' => route('category.show', $cat->slug),
                'image' => $cat->getImageUrl() ?? asset('images/no-thumbnail.png'),
            ],
        )
        ->values()
        ->all()
    : $categories;

// Seed the header badge from the real DB cart (not the `_cart_count`
// mirror cookie, which is only refreshed as a side effect of cart
// mutations and is stale/absent on a plain page load).
$initialCartDevice = \App\Models\Device::where('device_id', request()->cookie('_sfdid'))->first();
$initialCartCount =
    \App\Models\Cart::where('customer_id', null)
        ->where('device_id', $initialCartDevice?->id)
        ->first()
        ?->totalItems() ?? 0;

// Seed logged-in customer state (role = 'user') so a returning
// session shows the right header state immediately, not just after
// a fresh login/signup action within the same page load.
$initialAuthUser =
    auth()->check() && auth()->user()->role === 'user'
        ? ['id' => auth()->id(), 'name' => auth()->user()->name, 'email' => auth()->user()->email]
                : null;
    @endphp
    <div class="jtc" x-data="storefront(@js([
    'products' => $productsJson,
    'slides' => $slides,
    'heroBanners' => $heroBanners,
    'categories' => $carouselCategories,
    'cartCount' => $initialCartCount,
    'user' => $initialAuthUser,
]))" @toast.window="showToast(($event.detail[0] ?? $event.detail).message)">
        @include('storefront.partials.topbar')
        @include('storefront.partials.header')

        <main>
            @yield('content')
        </main>

        @include('storefront.partials.trust')
        @include('storefront.partials.footer')

        {{-- Overlays --}}
        @livewire('website.cart-manager')
        @livewire('website.storefront.search-modal')
        @include('storefront.partials.offcanvas')
        @include('storefront.partials.auth-modal')
        @include('storefront.partials.support')
        @include('storefront.partials.toast')
    </div>

    {{-- =====================================================================
         External library scripts (shared with legacy pages: shop, cart, product…).
         Loaded here so page markup using Bootstrap/Swiper/Fancybox/Plyr works.
         Note: Alpine is provided by Livewire (@livewireScripts, below) — these
         libraries are independent and do not affect the JTC storefront component.
         ===================================================================== --}}
    {{-- jQuery --}}
    <script src="{{ asset('frontend/library/jquery/jquery-3.7.1.min.js') }}"></script>
    {{-- Bootstrap JS --}}
    <script src="{{ asset('frontend/library/bootstrap/bootstrap.bundle.min.js') }}"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
    {{-- Swiper JS --}}
    <script src="{{ asset('frontend/library/swiper/swiper-bundle.min.js') }}"></script>
    {{-- Fancybox JS --}}
    <script src="{{ asset('frontend/library/fancybox/fancybox.umd.js') }}"></script>
    {{-- Plyr --}}
    <script src="{{ asset('lib/plyr/plyr.polyfilled.js') }}"></script>
    {{-- Legacy custom JS --}}
    <script src="{{ asset('frontend/js/script.js') }}"></script>

    {{-- notyf (Livewire cart / session notifications) --}}
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script>
        window.appNotyf = new Notyf({
            duration: 3200,
            position: {
                x: 'right',
                y: 'top'
            }
        });
    </script>

    {{-- Swiper init (product pages: .mySwiper / .mySwiper2). No-op if absent. --}}
    <script>
        if (document.querySelector('.mySwiper')) {
            var swiper = new Swiper(".mySwiper", {
                spaceBetween: 10,
                slidesPerView: 4,
                freeMode: true,
                watchSlidesProgress: true,
            });
            var swiper2 = new Swiper(".mySwiper2", {
                spaceBetween: 10,
                autoplay: {
                    delay: 2000
                },
                pauseOnHover: true,
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                thumbs: {
                    swiper: swiper
                },
            });
        }
    </script>

    {{-- Fancybox init --}}
    <script>
        if (window.Fancybox) {
            Fancybox.bind("[data-fancybox]", {
                Thumbs: {
                    autoStart: true
                },
            });
        }
    </script>

    @if (session('cart_error'))
        <script>
            window.appNotyf.error(@json(session('cart_error')));
        </script>
    @endif
    @if (session('checkout_success'))
        <script>
            window.appNotyf.success(@json(session('checkout_success')));
        </script>
    @endif

    {{-- Device registration (tracking) --}}
    <script>
        (function() {
            var screen_size = window.screen.width + 'x' + window.screen.height;

            function registerDevice() {
                fetch('{{ route('device.register') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            device_type: navigator.userAgent,
                            device_model: navigator.platform,
                            screen_size: screen_size,
                            phone: null,
                            name: null,
                            address: null
                        })
                    })
                    .then(r => r.json())
                    .catch(() => {});
            }
            registerDevice();
            window.addEventListener('beforeunload', registerDevice);
        })();
    </script>

    {{-- Meta CAPI PageView --}}
    @php
        $pageViewPayload = null;
        try {
            $pageViewEvent = new \App\CAPI\PageViewEvent();
            $pageViewServerPayload = $pageViewEvent->serverPayload();
            $pageViewPayload = $pageViewEvent->browserEventPayload();
            \App\Jobs\SendMetaCapiEventJob::dispatch($pageViewServerPayload)->onQueue(
                env('META_CAPI_QUEUE', 'metacapi'),
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PageView CAPI skipped: ' . $e->getMessage());
        }
    @endphp
    <script>
        const page_view_browser = @json($pageViewPayload);
        if (page_view_browser) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push(page_view_browser);
        }
    </script>

    {{-- Livewire notifications --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (event) => {
                if (event.type === 'success') {
                    window.appNotyf.success(event.message);
                } else if (event.type === 'error') {
                    window.appNotyf.error(event.message);
                }
            });
        });
    </script>

    @livewireScripts
    @stack('scripts')
</body>

</html>
