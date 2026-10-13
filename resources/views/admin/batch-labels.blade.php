@php
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
    $dims = ['50x25' => [50, 25], '38x25' => [38, 25], 'a4' => [49.5, 25.4]][$size];
    $printed = 0;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode labels — {{ $batches->pluck('batch_no')->unique()->implode(', ') }}</title>
    @vite(['resources/css/admin.css'])
    <style>
        .label {
            width: {{ $dims[0] }}mm;
            height: {{ $dims[1] }}mm;
            padding: 1.5mm 2mm 1.2mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #fff;
            color: #000;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.15;
        }
        .label .name { font-size: 7.5pt; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .meta { font-size: 6.5pt; display: flex; justify-content: space-between; gap: 2mm; white-space: nowrap; }
        .label .meta .variant { overflow: hidden; text-overflow: ellipsis; }
        .label .meta .price { font-weight: 700; }
        .label .bars { flex: 1; min-height: 0; margin-top: .8mm; }
        .label .bars svg { display: block; width: 100%; height: 100%; }
        .label .code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 7pt; text-align: center; letter-spacing: .04em; margin-top: .4mm; }

        .sheet { display: flex; flex-wrap: wrap; gap: 4mm; }
        .sheet .label { outline: 1px dashed #d1d5db; }
        @if ($size === 'a4')
            .sheet.a4 { gap: 0; width: 198mm; background: #fff; }
        @endif

        @media print {
            .no-print { display: none !important; }
            html, body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .sheet { gap: 0; }
            .sheet .label { outline: none; }
            @if ($size === 'a4')
                @page { size: A4; margin: 8mm 6mm; }
                .label { break-inside: avoid; }
            @else
                @page { size: {{ $dims[0] }}mm {{ $dims[1] }}mm; margin: 0; }
                .sheet { display: block; }
                .label { break-after: page; page-break-after: always; }
                .label:last-child { break-after: auto; page-break-after: auto; }
            @endif
        }
    </style>
</head>

<body class="bg-gray-100 p-4 sm:p-8 text-gray-800 antialiased">

    <form method="GET" class="no-print mx-auto mb-6 max-w-4xl rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-gray-900">Barcode labels</h1>
                <p class="mt-0.5 text-xs text-gray-500">প্রতিটা piece-এ একটা label — barcode-এ batch no। Pack করার সময় scan করলে ঠিক ওই batch থেকেই stock কাটবে।</p>
            </div>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
                Print {{ $total }} labels
            </button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-gray-500">
                        <th class="py-2 pr-3">Product</th><th class="py-2 pr-3">Batch no</th><th class="py-2 pr-3 text-right">In stock</th><th class="py-2 text-right">Labels</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batches as $b)
                        <tr class="border-t border-gray-100">
                            <td class="py-2 pr-3"><div class="font-medium text-gray-800">{{ $b->product?->name ?? '—' }}</div>
                                @if ($b->variant)<div class="text-xs text-gray-500">{{ $b->variant->options_map ? implode(' / ', $b->variant->options_map) : $b->variant->sku }}</div>@endif</td>
                            <td class="py-2 pr-3 font-mono text-xs">{{ $b->batch_no }}</td>
                            <td class="py-2 pr-3 text-right text-gray-600">{{ $num($b->quantity) }}</td>
                            <td class="py-2 text-right"><input type="number" name="q[{{ $b->id }}]" value="{{ $copies[$b->id] }}" min="0" max="{{ $max }}" class="w-20 rounded-lg border border-gray-300 px-2 py-1 text-right text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-4 text-sm">
            <label class="flex items-center gap-2">
                <span class="text-gray-600">Label size</span>
                <select name="size" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @foreach ($sizes as $key => $label)
                        <option value="{{ $key }}" @selected($size === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="price" value="1" @checked($showPrice) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-gray-600">Show price</span>
            </label>
            <button type="submit" class="rounded-lg border border-gray-200 bg-white px-3.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Update preview</button>
            @if ($copies->sum() > $max)
                <span class="text-xs text-amber-600">একবারে সর্বোচ্চ {{ $max }}টা label — বাকিগুলো পরে print করুন।</span>
            @endif
        </div>
        <p class="mt-3 text-[11px] text-gray-400">Printer settings: margin “None”, scale 100%{{ $size !== 'a4' ? ', paper size = label size' : '' }}.</p>
    </form>

    <div class="sheet {{ $size === 'a4' ? 'a4' : '' }} mx-auto {{ $size === 'a4' ? '' : 'max-w-4xl' }}">
        @foreach ($batches as $b)
            @php
                $svg = \App\Support\Code128::svg($b->batch_no);
                $variant = $b->variant ? ($b->variant->options_map ? implode(' / ', $b->variant->options_map) : $b->variant->sku) : ($b->product?->code ?? '');
                $price = collect([$b->variant, $b->product])->filter()
                    ->map(fn ($s) => (float) $s->sale_price > 0 ? (float) $s->sale_price : (float) $s->price)
                    ->first(fn ($p) => $p > 0);
            @endphp
            @for ($n = 0; $n < $copies[$b->id] && $printed < $max; $n++, $printed++)
                <div class="label">
                    <div class="name">{{ $b->product?->name ?? $siteName }}</div>
                    <div class="meta">
                        <span class="variant">{{ $variant }}</span>
                        @if ($showPrice && $price)<span class="price">{{ $currency }}{{ number_format((float) $price, 0) }}</span>@endif
                    </div>
                    <div class="bars">{!! $svg !!}</div>
                    <div class="code">{{ \App\Support\Code128::normalize($b->batch_no) }}</div>
                </div>
            @endfor
        @endforeach
    </div>
</body>

</html>
