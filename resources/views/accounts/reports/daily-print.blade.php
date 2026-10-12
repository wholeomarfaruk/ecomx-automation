<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daily report · {{ $data['date']->format('d M Y') }}</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>body { margin: 0; background: #fff; font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; color: #111827; font-size: 14px; } @page { size: A4; margin: 12mm; }</style>
</head>
<body>
<div style="max-width: 1000px; margin: 0 auto">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #e5e7eb">
        <div>
            <h1 style="margin: 0; font-size: 18px; font-weight: 700">Daily report · দিনের হিসাব</h1>
            <div style="font-size: 12.5px; color: #6b7280; margin-top: 2px">{{ \App\Support\AccountsSettings::businessName() }} · {{ $data['date']->format('l, j F Y') }}{{ $version > 1 ? " · version {$version}" : '' }}</div>
        </div>
        <div style="font-size: 11.5px; color: #6b7280">{{ $auto ? 'Auto-closed' : 'Closed by ' . (auth()->user()?->name ?? '—') }} · {{ now()->format('d M Y, g:i A') }}</div>
    </div>
    @include('accounts.reports.daily-body', ['data' => $data])
    <div style="padding: 16px 20px; display: flex; flex-direction: column; gap: 6px">
        <div style="font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.04em">Checklist</div>
        @foreach ($data['checklist'] as [$state, $text])
            <div style="font-size: 13px; padding: 6px 10px; border-radius: 8px; background: #f9fafb">{{ $state === 'ok' ? '✓' : ($state === 'bad' ? '×' : '!') }} {{ $text }}</div>
        @endforeach
        @if ($note)
            <div style="font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 8px">দিনের note</div>
            <div style="font-size: 13px; white-space: pre-line">{{ $note }}</div>
        @endif
    </div>
</div>
</body>
</html>
