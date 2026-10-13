@php
    // Settings may be unreachable while the site is down — every lookup falls
    // back to nothing so this page always renders.
    $siteName = $logoUrl = $faviconUrl = null;
    try {
        $siteName = \App\Models\Setting::get('site_name', null, 'general') ?: null;
        $logoId   = \App\Models\Setting::get('site_logo_black') ?: \App\Models\Setting::get('site_logo');
        $logoUrl  = $logoId ? file_path($logoId) : null;
        $favId    = \App\Models\Setting::get('site_favicon');
        $faviconUrl = $favId ? file_path($favId) : null;
    } catch (\Throwable) {
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $siteName ? $siteName . ' — ' : '' }}Coming back soon</title>
    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #111827;
            background: #fafafa radial-gradient(circle at 50% 0%, #eef2ff 0%, rgba(250,250,250,0) 60%);
            -webkit-font-smoothing: antialiased;
        }
        .wrap { width: 100%; max-width: 460px; text-align: center; }
        .logo { display: block; max-height: 56px; max-width: 200px; margin: 0 auto 20px; object-fit: contain; }
        .name { font-size: 15px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; margin-bottom: 36px; }
        .icon {
            width: 64px; height: 64px; margin: 0 auto 28px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 20px; background: #fff; color: #4f46e5;
            box-shadow: 0 1px 2px rgba(17,24,39,.05), 0 8px 24px -8px rgba(79,70,229,.25);
        }
        .icon svg { width: 30px; height: 30px; animation: spin 6s linear infinite; }
        h1 { font-size: clamp(26px, 6vw, 34px); line-height: 1.2; font-weight: 700; letter-spacing: -.02em; }
        p { margin-top: 14px; font-size: 16px; line-height: 1.65; color: #6b7280; }
        .dots { display: inline-flex; gap: 6px; margin-top: 32px; }
        .dots span { width: 7px; height: 7px; border-radius: 50%; background: #c7d2fe; animation: pulse 1.4s ease-in-out infinite; }
        .dots span:nth-child(2) { animation-delay: .2s; }
        .dots span:nth-child(3) { animation-delay: .4s; }
        footer { margin-top: 48px; font-size: 13px; color: #9ca3af; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes pulse { 0%, 100% { opacity: .35; transform: scale(.85); } 50% { opacity: 1; transform: scale(1); background: #4f46e5; } }
        @media (prefers-reduced-motion: reduce) { .icon svg, .dots span { animation: none; } }
        @media (prefers-color-scheme: dark) {
            body { color: #f3f4f6; background: #0b0d12 radial-gradient(circle at 50% 0%, #1e1b4b 0%, rgba(11,13,18,0) 60%); }
            .icon { background: #151821; color: #a5b4fc; box-shadow: 0 8px 24px -8px rgba(99,102,241,.35); }
            p, .name { color: #9ca3af; }
            .dots span { background: #312e81; }
            footer { color: #6b7280; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        @if($logoUrl)
            <img class="logo" src="{{ $logoUrl }}" alt="{{ $siteName ?? 'Logo' }}">
        @endif
        @if($siteName && ! $logoUrl)
            <div class="name">{{ $siteName }}</div>
        @endif

        <div class="icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        </div>

        <h1>We'll be live very soon</h1>
        <p>We're making a few improvements to give you a better experience. Please check back in a little while — thank you for your patience.</p>

        <div class="dots" aria-hidden="true"><span></span><span></span><span></span></div>

        @if($siteName)
            <footer>&copy; {{ date('Y') }} {{ $siteName }}</footer>
        @endif
    </main>
</body>
</html>
