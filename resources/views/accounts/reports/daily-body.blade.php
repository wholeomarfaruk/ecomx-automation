{{-- Daily report body: tiles + money accounts table (page, print and archive). --}}
@php
    $m = fn ($v) => abs((float) $v) < 0.005 ? '—' : \App\Support\Bdt::money($v);
    $t = $data['tiles'];
    $rows = $data['rows'];
@endphp
<div style="padding: 16px 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px">
    <div style="background: #f9fafb; border-radius: 12px; padding: 12px 14px"><div style="font-size: 12px; color: #6b7280">বিক্রি</div><div style="font-size: 19px; font-weight: 700; margin-top: 2px">{{ \App\Support\Bdt::money($t['sales'][0]) }}</div><div style="font-size: 11.5px; color: #6b7280">{{ $t['sales'][1] }}</div></div>
    <div style="background: #f9fafb; border-radius: 12px; padding: 12px 14px"><div style="font-size: 12px; color: #6b7280">টাকা আসলো</div><div style="font-size: 19px; font-weight: 700; color: #047857; margin-top: 2px">{{ \App\Support\Bdt::money($t['in'][0]) }}</div><div style="font-size: 11.5px; color: #6b7280">{{ $t['in'][1] }}</div></div>
    <div style="background: #f9fafb; border-radius: 12px; padding: 12px 14px"><div style="font-size: 12px; color: #6b7280">টাকা গেলো</div><div style="font-size: 19px; font-weight: 700; color: #b91c1c; margin-top: 2px">{{ \App\Support\Bdt::money($t['out'][0]) }}</div><div style="font-size: 11.5px; color: #6b7280">{{ $t['out'][1] }}</div></div>
    <div style="background: #f9fafb; border-radius: 12px; padding: 12px 14px"><div style="font-size: 12px; color: #6b7280">নতুন বাকি</div><div style="font-size: 19px; font-weight: 700; color: #b45309; margin-top: 2px">{{ \App\Support\Bdt::money($t['due'][0]) }}</div><div style="font-size: 11.5px; color: #6b7280">{{ $t['due'][1] }}</div></div>
    <div style="background: #f9fafb; border-radius: 12px; padding: 12px 14px"><div style="font-size: 12px; color: #6b7280">Stock</div><div style="font-size: 19px; font-weight: 700; margin-top: 2px">{{ $t['stock'][0] ?? '—' }}</div><div style="font-size: 11.5px; color: #6b7280">{{ $t['stock'][1] }}</div></div>
</div>
<div style="overflow-x: auto">
    <table style="width: 100%; border-collapse: collapse; min-width: 640px; font-size: 13.5px">
        <thead><tr style="text-align: left; color: #6b7280; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.04em; background: #f9fafb"><th style="padding: 10px 20px; font-weight: 600">Account</th><th style="padding: 10px 12px; text-align: right; font-weight: 600">শুরুতে</th><th style="padding: 10px 12px; text-align: right; font-weight: 600">In</th><th style="padding: 10px 12px; text-align: right; font-weight: 600">Out</th><th style="padding: 10px 20px; text-align: right; font-weight: 600">দিন শেষে</th></tr></thead>
        <tbody>
            @foreach ($rows as $r)
                <tr style="border-top: 1px solid #f0f1f4"><td style="padding: 9px 20px; font-weight: 600">{{ $r['name'] }}</td><td style="padding: 9px 12px; text-align: right">{{ \App\Support\Bdt::money($r['open']) }}</td><td style="padding: 9px 12px; text-align: right; color: #047857">{{ $m($r['in']) }}</td><td style="padding: 9px 12px; text-align: right; color: #b91c1c">{{ $m($r['out']) }}</td><td style="padding: 9px 20px; text-align: right; font-weight: 700">{{ \App\Support\Bdt::money($r['close']) }}</td></tr>
            @endforeach
            <tr style="border-top: 2px solid #e5e7eb; background: #f9fafb; font-weight: 700"><td style="padding: 10px 20px">মোট</td><td style="padding: 10px 12px; text-align: right">{{ \App\Support\Bdt::money(collect($rows)->sum('open')) }}</td><td style="padding: 10px 12px; text-align: right; color: #047857">{{ \App\Support\Bdt::money(collect($rows)->sum('in')) }}</td><td style="padding: 10px 12px; text-align: right; color: #b91c1c">{{ \App\Support\Bdt::money(collect($rows)->sum('out')) }}</td><td style="padding: 10px 20px; text-align: right">{{ \App\Support\Bdt::money(collect($rows)->sum('close')) }}</td></tr>
        </tbody>
    </table>
</div>
@if ($data['transfer'] > 0)
    <div style="padding: 6px 20px 0; font-size: 12px; color: #6b7280">In/Out-এ {{ \App\Support\Bdt::money($data['transfer']) }} নিজেদের account-এর মধ্যে transfer দুই দিকেই আছে।</div>
@endif
