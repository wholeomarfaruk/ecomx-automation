<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $r['title'] }} · {{ $r['period'] }}</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { margin: 0; background: #fff; font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; color: #111827; font-size: 14px; }
    @page { size: A4; margin: 12mm; }
    @media print { .no-print { display: none !important; } }
</style>
</head>
<body @if (! empty($autoprint)) onload="window.print()" @endif>
    <div style="max-width: 1000px; margin: 0 auto">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #e5e7eb">
            <div>
                <h1 style="margin: 0; font-size: 18px; font-weight: 700">{{ $r['title'] }}</h1>
                <div style="font-size: 12.5px; color: #6b7280; margin-top: 2px">{{ $r['business'] }} · {{ $r['period'] }}</div>
            </div>
            <div style="font-size: 11.5px; color: #6b7280">Printed {{ now()->format('d M Y, g:i A') }}</div>
        </div>
        @include('accounts.reports.table', ['r' => $r])
    </div>
</body>
</html>
