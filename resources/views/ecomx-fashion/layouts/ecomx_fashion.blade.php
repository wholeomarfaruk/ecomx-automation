@php
    // Site-wide defaults (Admin > Site Settings > General). Any page rendered
    // through this layout can override $title / $metaDescription / $metaImage
    // by passing them to ->layout('...', [...]) from its render() method —
    // see Home/Shop/Category/Product for per-page and per-product overrides.
    $siteName = \App\Models\Setting::get('site_name', 'Seldom Fashion') ?: 'Seldom Fashion';
    $siteTagline = \App\Models\Setting::get('site_tagline', 'Premium clothing, made in Bangladesh') ?: 'Premium clothing, made in Bangladesh';
    $defaultTitle = "{$siteName} — {$siteTagline}";
    $defaultDescription = 'Considered clothing, made rarely and made well. Designed in Dhaka, Bangladesh.';

    // Never let a missing/broken favicon setting break page rendering.
    $faviconUrl = null;
    try {
        if ($faviconId = \App\Models\Setting::get('site_favicon')) {
            $faviconUrl = file_path($faviconId);
        }
    } catch (\Throwable $e) {
        $faviconUrl = null;
    }

    $pageTitle = (isset($title) && trim((string) $title) !== '') ? $title : $defaultTitle;
    $pageDescription = (isset($metaDescription) && trim((string) $metaDescription) !== '') ? $metaDescription : $defaultDescription;
    $pageImage = $metaImage ?? null;
    $canonicalUrl = url()->current();
@endphp
<!DOCTYPE html>
<html lang="en" data-pal="{{ \App\Support\EcomxFashion\PaletteRegistry::active() }}" x-data>
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
    @vite(['resources/ecomx-fashion/scss/app.scss', 'resources/ecomx-fashion/js/app.js'])
    @livewireStyles
</head>
<body>
    <x-marketing.gtm-noscript />
    @include('ecomx-fashion.partials.header')

    <main>
        {{ $slot }}
    </main>

    @include('ecomx-fashion.partials.footer')
    @include('ecomx-fashion.partials.bottom-nav')
    @include('ecomx-fashion.partials.support-modal')
    @livewire('ecomx-fashion.cart-manager')
    @livewire('ecomx-fashion.search-modal')
    @livewire('ecomx-fashion.wishlist-drawer')
    @livewire('ecomx-fashion.auth-modal')

    @livewireScripts

    <x-device-fingerprint-script />
</body>
</html>
