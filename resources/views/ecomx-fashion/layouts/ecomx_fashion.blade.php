<!DOCTYPE html>
<html lang="en" data-pal="{{ \App\Support\EcomxFashion\PaletteRegistry::active() }}" x-data>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Seldom Fashion — Premium clothing, made in Bangladesh' }}</title>
    <meta name="description" content="Considered clothing, made rarely and made well. Designed in Dhaka, Bangladesh.">
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
