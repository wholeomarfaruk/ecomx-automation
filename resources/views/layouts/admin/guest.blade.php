<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @php $title = $title ?? 'Admin'; @endphp
    @include('layouts.admin.partials.head-meta')
    @vite(['resources/sass/admin.scss', 'resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
    @stack('styles')
</head>

<body x-data class="antialiased min-h-screen bg-gray-100 flex items-center justify-center p-4">

    @php
        $siteName  = \App\Models\Setting::get('site_name', config('app.name'), 'general');
        $logoWhite = \App\Models\Setting::get('site_logo_white', null, 'general');
        $logoUrl   = $logoWhite ? file_path($logoWhite) : null;
    @endphp

    <div class="w-full max-w-sm">

        {{-- Branding --}}
        <div class="flex flex-col items-center mb-6">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-12 object-contain mb-3">
            @else
                <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center shadow-sm mb-3">
                    <span class="text-white font-black text-lg leading-none select-none">{{ strtoupper(mb_substr($siteName, 0, 1)) }}</span>
                </div>
            @endif
            <span class="text-gray-800 font-semibold text-lg">{{ $siteName }}</span>
        </div>

        {{ $slot }}

        <p class="text-center text-xs text-gray-400 mt-6">{{ $siteName }} &copy; {{ date('Y') }}</p>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
