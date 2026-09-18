@php
    $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

    // Title/intro are edited alongside the article body in the same
    // 'privacy-content' section (Admin > Frontend Engine > Pages > Privacy
    // Policy) — read directly here since the hero markup lives outside
    // .jtc-legal__layout (where the section itself renders) and can't move
    // without breaking the page's designed structure.
    $heroConfig = \App\Support\EcomxAnyniche\PageSectionConfigRegistry::find('privacy-policy', 'privacy-content');
    $heroTitle = $heroConfig['title'] ?? 'Privacy policy';
    $heroIntro = $heroConfig['intro'] ?? "This policy explains what personal information {$siteName} collects when you browse or order from us, why we collect it, who we share it with, and the choices you have. We wrote it in plain language on purpose — no legal jargon you need a lawyer to decode.";
@endphp

<div class="jtc-legal">
    <div class="jtc-legal__hero">
        <nav class="jtc-shop__crumb" style="margin-bottom:14px">
            <a href="{{ route('ecomx-anyniche.home') }}" style="color:inherit;text-decoration:none">Home</a>
            <span>/</span>
            <span style="color:#14201c;font-weight:600">{{ $heroTitle }}</span>
        </nav>
        <h1>{{ $heroTitle }}</h1>
        <p class="jtc-legal__updated">Last updated: {{ now()->format('d F Y') }}</p>
        <p>{{ $heroIntro }}</p>
    </div>

    <div class="jtc-legal__layout">
        {{-- Content managed from Admin > Frontend Engine > Pages > Privacy Policy --}}
        {{-- (App\Support\EcomxAnyniche\PageSectionRegistry), same pattern as Home's sections. --}}
        @foreach($sections as $section)
            @livewire(
                config(\App\Support\EcomxAnyniche\ActiveTheme::slug() . ".sections.$section"),
                [],
                key('privacy-policy-' . $section)
            )
        @endforeach
    </div>
</div>
