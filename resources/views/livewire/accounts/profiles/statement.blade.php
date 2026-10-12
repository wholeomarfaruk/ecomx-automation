{{-- Printable statement — content of the Ledger tab (ProfileDetail.dc.html). --}}
@php $p = $profile; @endphp

<div class="mx-auto my-[24px] box-border w-full max-w-[860px] rounded-[14px] border border-[#e5e7eb] bg-white p-[28px] print:my-0 print:max-w-none print:rounded-none print:border-0 print:p-0">
    <div class="no-print mb-[16px] flex justify-end gap-[8px]">
        <button type="button" onclick="window.print()" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Print / Save as PDF</button>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-[16px] border-b border-[#e5e7eb] pb-[16px]">
        <div>
            <div class="text-[20px] font-bold">{{ $site }}</div>
            <div class="text-[13px] text-[#6b7280]">হিসাব বিবরণী · Statement of account</div>
        </div>
        <div class="text-right text-[13px] text-[#374151]">
            <div class="font-bold">{{ $p->display_name }}</div>
            <div>{{ $p->code }} · {{ $p->phone }}</div>
            <div>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}{{ $role ? ' · ' . ucfirst($role) : '' }}</div>
        </div>
    </div>

    <table class="mt-[16px] w-full border-collapse text-[12.5px]">
        <thead>
            <tr class="bg-[#f9fafb] text-left text-[11px] uppercase tracking-[0.04em] text-[#6b7280]">
                <th class="px-[10px] py-[8px] font-semibold">Date</th>
                <th class="px-[8px] py-[8px] font-semibold">Voucher</th>
                <th class="px-[8px] py-[8px] font-semibold">Particulars</th>
                <th class="px-[8px] py-[8px] text-right font-semibold">Debit</th>
                <th class="px-[8px] py-[8px] text-right font-semibold">Credit</th>
                <th class="px-[10px] py-[8px] text-right font-semibold">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-t border-[#f0f1f4]">
                <td class="px-[10px] py-[7px]">{{ \Carbon\Carbon::parse($from)->format('d M') }}</td><td class="px-[8px] py-[7px] font-semibold">OB</td>
                <td class="px-[8px] py-[7px]">Opening balance</td><td></td><td></td>
                <td class="px-[10px] py-[7px] text-right font-semibold {{ $st['opening'] >= 0 ? 'text-[#047857]' : 'text-[#b91c1c]' }}">{{ $drCr($st['opening']) }}</td>
            </tr>
            @foreach ($st['rows'] as $l)
                <tr class="border-t border-[#f0f1f4]">
                    <td class="whitespace-nowrap px-[10px] py-[7px]">{{ $l['date']->format('d M') }}</td>
                    <td class="whitespace-nowrap px-[8px] py-[7px] font-semibold">{{ $l['ref'] }}</td>
                    <td class="px-[8px] py-[7px]"><div>{{ $l['text'] }}</div><div class="text-[11px] text-[#6b7280]">{{ $l['sub'] }}</div></td>
                    <td class="whitespace-nowrap px-[8px] py-[7px] text-right">{{ $l['debit'] > 0 ? $money($l['debit']) : '' }}</td>
                    <td class="whitespace-nowrap px-[8px] py-[7px] text-right">{{ $l['credit'] > 0 ? $money($l['credit']) : '' }}</td>
                    <td class="whitespace-nowrap px-[10px] py-[7px] text-right font-semibold {{ $l['balance'] >= 0 ? 'text-[#047857]' : 'text-[#b91c1c]' }}">{{ $drCr($l['balance']) }}</td>
                </tr>
            @endforeach
            <tr class="border-t-2 border-[#e5e7eb] bg-[#f9fafb] font-bold">
                <td class="px-[10px] py-[9px]" colspan="3">Closing balance · {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</td>
                <td class="px-[8px] py-[9px] text-right">{{ $money($st['debit']) }}</td>
                <td class="px-[8px] py-[9px] text-right">{{ $money($st['credit']) }}</td>
                <td class="px-[10px] py-[9px] text-right {{ $st['closing'] >= 0 ? 'text-[#047857]' : 'text-[#b91c1c]' }}">{{ $drCr($st['closing']) }}</td>
            </tr>
        </tbody>
    </table>
    <div class="mt-[10px] text-[11.5px] text-[#6b7280]">Dr = উনি আমাদের কাছে দেনা (পাবো) · Cr = আমরা ওনার কাছে দেনা (দিবো) · Printed {{ now()->format('d M Y, h:i A') }}</div>
</div>
