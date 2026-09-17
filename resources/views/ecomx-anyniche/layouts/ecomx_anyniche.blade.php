@php
    // Site-wide defaults (Admin > Site Settings > General). Any page rendered
    // through this layout can override $title / $metaDescription / $metaImage
    // by passing them to ->layout('...', [...]) from its render() method —
    // see Home/Shop/Category/Product for per-page and per-product overrides.
    $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';
    $siteTagline = \App\Models\Setting::get('site_tagline', 'Everything you need, delivered fast') ?: 'Everything you need, delivered fast';
    $defaultTitle = "{$siteName} — {$siteTagline}";
    $defaultDescription = 'Quality products, competitive prices, and fast delivery — placeholder copy, edit in Site Settings.';

    // Never let a missing/broken favicon setting break page rendering.
    $faviconUrl = null;
    try {
        if ($faviconId = \App\Models\Setting::get('site_favicon')) {
            $faviconUrl = file_path($faviconId);
        }
    } catch (\Throwable $e) {
        $faviconUrl = null;
    }

    // Admin > Frontend > Pages > (page) > SEO tab writes here per page key
    // (route name with the ecomx-anyniche. prefix stripped, e.g. 'track').
    // Only used when the component itself didn't already pass an explicit
    // $title/$metaDescription — those (Shop/Category/Product's per-item
    // titles) always win, since they're more specific than a page-level default.
    $pageKey = str_starts_with((string) request()->route()?->getName(), 'ecomx-anyniche.')
        ? substr(request()->route()->getName(), strlen('ecomx-anyniche.'))
        : null;
    $seo = $pageKey ? \App\Support\EcomxAnyniche\PageSeoRegistry::forPage($pageKey) : [];

    $pageTitle = (isset($title) && trim((string) $title) !== '')
        ? $title
        : (trim((string) ($seo['meta_title'] ?? '')) !== '' ? $seo['meta_title'] : $defaultTitle);
    $pageDescription = (isset($metaDescription) && trim((string) $metaDescription) !== '')
        ? $metaDescription
        : (trim((string) ($seo['meta_description'] ?? '')) !== '' ? $seo['meta_description'] : $defaultDescription);
    $pageImage = $metaImage ?? ($seo['og_image'] ?? null);
    $canonicalUrl = url()->current();
@endphp
<!DOCTYPE html>
<html lang="en" data-pal="{{ \App\Support\EcomxAnyniche\PaletteRegistry::active() }}" x-data="{ drawer: false }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">

    @if($pageImage)
        <meta property="og:image" content="{{ $pageImage }}">
    @endif

    <meta name="twitter:card" content="{{ $pageImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    @if($pageImage)
        <meta name="twitter:image" content="{{ $pageImage }}">
    @endif


    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/ecomx-anyniche/scss/app.scss', 'resources/ecomx-anyniche/js/app.js'])
    @livewireStyles
</head>
<body>
    <x-marketing.gtm-noscript />
    @include('ecomx-anyniche.partials.topbar')
    @include('ecomx-anyniche.partials.header')
    @include('ecomx-anyniche.partials.offcanvas')
    <aside id="sidebar"></aside>
    <main>
        {{ $slot }}
    </main>

    @include('ecomx-anyniche.partials.footer')

    @include('ecomx-anyniche.partials.support-modal')
    @livewire('ecomx-anyniche.cart-manager')
    @livewire('ecomx-anyniche.search-modal')
    @livewire('ecomx-anyniche.wishlist-drawer')
    @livewire('ecomx-anyniche.auth-modal')

    @livewireScripts

    <x-device-fingerprint-script />
</body>
</html>
