<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payslips · {{ $run->month->format('F Y') }}</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { margin: 0; background: #f1f3f7; font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; color: #111827; font-size: 13px; }
    .slip { background: #fff; max-width: 760px; margin: 20px auto; padding: 24px 28px; border: 1px solid #e5e7eb; border-radius: 12px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 6px 8px; }
    .num { text-align: right; white-space: nowrap; }
    .muted { color: #6b7280; }
    @page { size: A4; margin: 12mm; }
    @media print { body { background: #fff; } .slip { border: 0; margin: 0 auto; page-break-after: always; } .no-print { display: none; } }
</style>
</head>
<body onload="window.print()">
@php
    $m = fn ($v) => \App\Support\Bdt::money($v);
    $biz = \App\Support\AccountsSettings::businessName();
@endphp
@foreach ($run->items as $i)
    @php
        $set = $i->profile?->roleSettings('employee') ?? [];
        $earn = array_filter([
            'Salary' => (float) $i->salary, 'House rent' => (float) $i->house_rent, 'Medical' => (float) $i->medical, 'Conveyance' => (float) $i->conveyance,
            'Other allowance' => (float) $i->other_allowance,
            'Overtime' . ((float) $i->ot_hours > 0 ? ' (' . rtrim(rtrim(number_format((float) $i->ot_hours, 2), '0'), '.') . ' h × ' . $m($i->ot_rate) . ')' : '') => (float) $i->ot_amount,
            'Bonus' . ($run->bonus_note ? " · {$run->bonus_note}" : '') => (float) $i->bonus, 'Incentive' => (float) $i->incentive, 'Arrears' => (float) $i->arrears,
        ], fn ($v) => $v > 0);
        $ded = array_filter([
            'Absent (' . rtrim(rtrim(number_format((float) $i->absent_days, 2), '0'), '.') . ' of ' . rtrim(rtrim(number_format((float) $i->working_days, 2), '0'), '.') . ' days)' => (float) $i->absent_deduction,
            'Advance recovery' => (float) $i->advance_deduction, 'Tax (TDS)' => (float) $i->tax, 'Fine / other' => (float) $i->other_deduction,
        ], fn ($v) => $v > 0);
        $rows = max(count($earn), count($ded), 1);
        $earnK = array_keys($earn);
        $dedK = array_keys($ded);
    @endphp
    <div class="slip">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111827; padding-bottom: 10px">
            <div><div style="font-size: 18px; font-weight: 700">{{ $biz }}</div><div class="muted">Payslip · বেতনের স্লিপ</div></div>
            <div style="text-align: right"><div style="font-size: 15px; font-weight: 700">{{ $run->month->format('F Y') }}</div><div class="muted">Pay date {{ $run->due_on?->format('d M Y') ?? '—' }}</div></div>
        </div>
        <table style="margin-top: 12px">
            <tr><td class="muted" style="width: 120px">Employee</td><td><b>{{ $i->profile?->display_name }}</b> · {{ $i->profile?->code }}</td>
                <td class="muted" style="width: 110px">Designation</td><td>{{ $set['designation'] ?? '—' }}</td></tr>
            <tr><td class="muted">Department</td><td>{{ $set['department'] ?? '—' }}</td>
                <td class="muted">Paid via</td><td>{{ ['cash' => 'Cash', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank'][$set['pay_via'] ?? ''] ?? '—' }}{{ ! empty($set['pay_account']) ? ' · ' . $set['pay_account'] : '' }}</td></tr>
        </table>
        <table style="margin-top: 14px; border: 1px solid #e5e7eb">
            <thead><tr style="background: #f9fafb; text-align: left"><th>Earnings</th><th class="num">৳</th><th style="border-left: 1px solid #e5e7eb">Deductions</th><th class="num">৳</th></tr></thead>
            <tbody>
                @for ($k = 0; $k < $rows; $k++)
                    <tr style="border-top: 1px solid #f0f1f4">
                        <td>{{ $earnK[$k] ?? '' }}</td><td class="num">{{ isset($earnK[$k]) ? $m($earn[$earnK[$k]]) : '' }}</td>
                        <td style="border-left: 1px solid #e5e7eb">{{ $dedK[$k] ?? ($k === 0 && ! $ded ? '—' : '') }}</td><td class="num">{{ isset($dedK[$k]) ? $m($ded[$dedK[$k]]) : '' }}</td>
                    </tr>
                @endfor
                <tr style="border-top: 2px solid #e5e7eb; background: #f9fafb; font-weight: 700">
                    <td>Gross earnings</td><td class="num">{{ $m($i->gross) }}</td><td style="border-left: 1px solid #e5e7eb">Total deductions</td><td class="num">{{ $m($i->total_deduction) }}</td>
                </tr>
            </tbody>
        </table>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding: 12px 14px; background: #ecfdf5; border-radius: 10px">
            <span style="font-weight: 700; color: #065f46">Net pay · হাতে পাবেন</span><span style="font-size: 20px; font-weight: 700; color: #065f46">{{ $m($i->net) }}</span>
        </div>
        <div class="muted" style="margin-top: 6px">Paid so far {{ $m($i->paid_amount) }}{{ $i->unpaid() > 0.5 ? ' · due ' . $m($i->unpaid()) : '' }}{{ $i->note ? ' · ' . $i->note : '' }}</div>
        <div style="display: flex; justify-content: space-between; gap: 40px; margin-top: 48px">
            <div style="flex: 1; border-top: 1px solid #9ca3af; padding-top: 6px; text-align: center" class="muted">Employee signature</div>
            <div style="flex: 1; border-top: 1px solid #9ca3af; padding-top: 6px; text-align: center" class="muted">Authorised signature</div>
        </div>
    </div>
@endforeach
</body>
</html>
