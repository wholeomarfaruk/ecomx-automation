<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $e->ref() }} — {{ $business }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css'])
    <style>@page { size: A5 landscape; margin: 12mm; } @media print { .no-print { display: none !important; } body { background: #fff !important; } }</style>
</head>
<body class="m-0 bg-[#f1f3f7] font-['Plus_Jakarta_Sans','Hind_Siliguri',sans-serif] text-[13px] text-[#111827]">
<div class="mx-auto my-[24px] max-w-[760px] rounded-[14px] border border-[#e5e7eb] bg-white p-[28px] print:m-0 print:border-0 print:p-0">
    <div class="no-print mb-[14px] flex justify-end"><button onclick="window.print()" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Print / Save as PDF</button></div>
    <div class="flex items-start justify-between border-b border-[#e5e7eb] pb-[14px]">
        <div><div class="text-[18px] font-bold">{{ $business }}</div>@if ($bin)<div class="text-[12px] text-[#6b7280]">BIN/TIN {{ $bin }}</div>@endif</div>
        <div class="text-right"><div class="text-[16px] font-bold">{{ $type }} voucher</div><div class="text-[13px]">{{ $e->ref() }} · {{ $e->entry_date->format('d M Y') }}</div><div class="text-[12px] text-[#6b7280]">{{ ucfirst($e->status->value) }}</div></div>
    </div>
    <div class="mt-[12px] grid grid-cols-[110px_1fr] gap-y-[4px]">
        <span class="text-[#6b7280]">Party</span><span class="font-semibold">{{ $party }}</span>
        <span class="text-[#6b7280]">Narration</span><span>{{ $e->description ?: '—' }}</span>
    </div>
    <table class="mt-[14px] w-full border-collapse text-[12.5px]">
        <thead><tr class="bg-[#f9fafb] text-left text-[11px] uppercase tracking-[0.04em] text-[#6b7280]"><th class="px-[10px] py-[7px]">Account</th><th class="px-[10px] py-[7px] text-right">Debit</th><th class="px-[10px] py-[7px] text-right">Credit</th></tr></thead>
        <tbody>
            @foreach ($e->lines as $l)
                @php $p = $l->subledger_type ? $vouchers->partyName($l->subledger_type, $l->subledger_id) : null; @endphp
                <tr class="border-t border-[#f0f1f4]"><td class="px-[10px] py-[7px]">{{ $l->account?->code }} · {{ $l->account?->name }}{{ $p ? ' — ' . $p : '' }}</td>
                    <td class="px-[10px] py-[7px] text-right">{{ (float) $l->debit > 0 ? \App\Support\Bdt::money($l->debit) : '' }}</td><td class="px-[10px] py-[7px] text-right">{{ (float) $l->credit > 0 ? \App\Support\Bdt::money($l->credit) : '' }}</td></tr>
            @endforeach
            <tr class="border-t-2 border-[#e5e7eb] font-bold"><td class="px-[10px] py-[7px]">Total</td><td class="px-[10px] py-[7px] text-right">{{ \App\Support\Bdt::money($e->lines->sum('debit')) }}</td><td class="px-[10px] py-[7px] text-right">{{ \App\Support\Bdt::money($e->lines->sum('credit')) }}</td></tr>
        </tbody>
    </table>
    <div class="mt-[40px] grid grid-cols-3 gap-[20px] text-center text-[12px] text-[#6b7280]">
        <div class="border-t border-[#d1d5db] pt-[6px]">Prepared · {{ $e->createdBy?->name ?? 'System' }}</div>
        <div class="border-t border-[#d1d5db] pt-[6px]">Approved · {{ $e->approvedBy?->name ?? '' }}</div>
        <div class="border-t border-[#d1d5db] pt-[6px]">Posted · {{ $e->postedBy?->name ?? '' }}</div>
    </div>
</div>
</body>
</html>
