@props(['title' => null, 'metaDescription' => null, 'metaImage' => null])
<!DOCTYPE html>
<html lang="en" x-data>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-marketing.gtm />

    <title>{{ $title ?? 'Landing Page' }}</title>
    @if(!empty($metaDescription ?? ''))<meta name="description" content="{{ $metaDescription }}">@endif
    @if(!empty($metaImage ?? ''))<meta property="og:image" content="{{ $metaImage }}">@endif

    {{-- Common webfont for fragment templates. --}}
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

    @livewireStyles
</head>
<body>
    <x-marketing.gtm-noscript />

    {{ $slot }}

    @livewireScripts

    <x-device-fingerprint-script />
</body>
</html>
