@php
    // Shared <title>/favicon logic for every admin-area <head> — the main
    // admin shell, the Livewire auth guest layout, and the standalone
    // Fortify auth views (login, register, password reset, 2FA, ...) which
    // each render their own <head> outside any shared layout. Pass $title
    // per page to override the default; never throws on a missing/invalid
    // site_favicon setting — just omits the favicon tags.
    $siteName = \App\Models\Setting::get('site_name', config('app.name'), 'general') ?: config('app.name');
    $pageTitle = (isset($title) && trim((string) $title) !== '') ? "{$title} — {$siteName}" : $siteName;

    $faviconUrl = null;
    try {
        if ($faviconId = \App\Models\Setting::get('site_favicon', null, 'general')) {
            $faviconUrl = file_path($faviconId);
        }
    } catch (\Throwable $e) {
        $faviconUrl = null;
    }
@endphp
<title>{{ $pageTitle }}</title>
@if($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
@endif
