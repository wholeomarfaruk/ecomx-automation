{{-- One report (Reports service shape) — inline styles so the page, print / PDF copy and archive look the same. --}}
@php
    $fmt = function ($v, $type) {
        if ($v === null || $v === '') {
            return '';
        }
        if (! is_numeric($v)) {
            return $v;
        }
        $v = (float) $v;

        return match ($type) {
            'money' => abs($v) < 0.005 ? '—' : ($v < 0 ? '− ' . \App\Support\Bdt::money(abs($v)) : \App\Support\Bdt::money($v)),
            'pct' => rtrim(rtrim(number_format($v, 1), '0'), '.') . '%',
            'num' => rtrim(rtrim(number_format($v, 2), '0'), '.'),
            default => (string) $v,
        };
    };
    $toneCss = ['green' => 'color: #047857', 'red' => 'color: #b91c1c', 'amber' => 'color: #b45309', 'muted' => 'color: #6b7280', '' => ''];
    $tileBg = ['green' => 'background: #ecfdf5', 'red' => 'background: #fef2f2', 'amber' => 'background: #fffbeb'];
    $rowCss = [
        'head' => 'border-top: 1px solid #e5e7eb; background: #f8fafc',
        'total' => 'border-top: 2px solid #e5e7eb; background: #f9fafb; font-weight: 700',
        'net' => 'border-top: 2px solid #e5e7eb; background: #f9fafb; font-weight: 700',
        'open' => 'border-top: 1px solid #f0f1f4; background: #f8fafc; font-weight: 600',
    ];
    $labelCss = [
        'head' => 'padding: 9px 20px; font-weight: 700; color: #334155; font-size: 12.5px; text-transform: uppercase; letter-spacing: 0.04em',
        'line' => 'padding: 9px 20px 9px 36px; color: #374151',
    ];
    $table = function ($columns, $rows) use ($fmt, $toneCss, $rowCss, $labelCss) {
        $last = count($columns) - 1;
        $h = '<table style="width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: ' . (count($columns) > 3 ? 640 : 320) . 'px"><thead><tr style="text-align: left; color: #6b7280; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.04em; background: #f9fafb">';
        foreach ($columns as $i => [$label, $type]) {
            $right = in_array($type, ['money', 'num', 'pct'], true);
            $pad = $i === 0 ? '10px 12px 10px 20px' : ($i === $last ? '10px 20px 10px 12px' : '10px 12px');
            $h .= '<th style="padding: ' . $pad . ($right ? '; text-align: right' : '') . '; font-weight: 600">' . e($label) . '</th>';
        }
        $h .= '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $kind = $r['kind'];
            $h .= '<tr style="' . ($rowCss[$kind] ?? 'border-top: 1px solid #f0f1f4') . '">';
            foreach ($columns as $i => [$label, $type]) {
                $v = $r['cells'][$i] ?? null;
                $right = in_array($type, ['money', 'num', 'pct'], true);
                $pad = $i === 0 ? '9px 12px 9px 20px' : ($i === $last ? '9px 20px 9px 12px' : '9px 12px');
                $css = $i === 0 && isset($labelCss[$kind]) ? $labelCss[$kind] : "padding: {$pad}";
                if ($right) {
                    $css .= '; text-align: right; white-space: nowrap';
                    if ($kind === 'net') {
                        $css .= '; font-weight: 700; color: #047857; font-size: 15px';
                    }
                }
                if ($type === 'ref' && $v) {
                    $css .= '; color: #4338ca; font-weight: 600; white-space: nowrap';
                }
                if (! empty($r['tone'][$i]) && $v !== null && $v !== '' && (float) $v != 0) {
                    $css .= '; ' . ($toneCss[$r['tone'][$i]] ?? '');
                }
                if ($kind === 'head' && $i > 0) {
                    $v = null;
                }
                $h .= '<td style="' . $css . '">' . e($fmt($v, $type)) . '</td>';
            }
            $h .= '</tr>';
        }

        return $h . '</tbody></table>';
    };
@endphp

@if (! empty($r['tiles']))
    <div style="padding: 16px 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px">
        @foreach ($r['tiles'] as [$label, $value, $sub, $tone])
            <div style="{{ $tileBg[$tone] ?? 'background: #f9fafb' }}; border-radius: 12px; padding: 12px 14px">
                <div style="font-size: 12px; color: {{ $tone === 'green' ? '#065f46' : '#6b7280' }}">{{ $label }}</div>
                <div style="font-size: 20px; font-weight: 700; margin-top: 2px; {{ $toneCss[$tone] ?? '' }}">{{ is_numeric($value) ? $fmt($value, 'money') : $value }}</div>
                @if ($sub)<div style="font-size: 11.5px; color: {{ $tone === 'green' ? '#065f46' : '#6b7280' }}">{{ $sub }}</div>@endif
            </div>
        @endforeach
    </div>
@endif

@if (! empty($r['split']))
    <div style="display: flex; flex-wrap: wrap">
        @foreach ($r['split'] as $title => $rows)
            <div style="flex: 1 1 320px; min-width: 0; {{ $loop->first ? 'border-right: 1px solid #f0f1f4' : '' }}">
                <div style="padding: 12px 20px; font-weight: 700; background: #f9fafb; border-bottom: 1px solid #f0f1f4">{{ $title }}</div>
                <div style="overflow-x: auto">{!! $table([['', 'text'], ['', 'money']], $rows) !!}</div>
            </div>
        @endforeach
    </div>
@elseif (! empty($r['columns']))
    <div style="overflow-x: auto">{!! $table($r['columns'], $r['rows']) !!}</div>
@endif

@if (! empty($r['check']))
    @php $c = $r['check']; @endphp
    <div style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; border-top: 1px solid #f0f1f4; font-size: 13px; font-weight: 600; {{ $c['ok'] ? 'background: #ecfdf5; color: #065f46' : 'background: #fef2f2; color: #991b1b' }}">
        <span style="width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; {{ $c['ok'] ? 'background: #d1fae5' : 'background: #fee2e2' }}">{{ $c['ok'] ? '✓' : '×' }}</span>
        @if (! empty($c['tb']))
            Debit {{ $fmt($c['debit'], 'money') }} {{ $c['ok'] ? '=' : '≠' }} Credit {{ $fmt($c['credit'], 'money') }} · {{ $c['ok'] ? 'balanced' : 'difference ' . $fmt($c['diff'], 'money') }}
        @else
            Assets {{ $fmt($c['assets'], 'money') }} {{ $c['ok'] ? '=' : '≠' }} Liabilities {{ $fmt($c['liabilities'], 'money') }} + Equity {{ $fmt($c['equity'], 'money') }} · {{ $c['ok'] ? 'balanced' : 'difference ' . $fmt($c['diff'], 'money') }}
        @endif
    </div>
@endif

@if (! empty($r['note']))
    <div style="padding: 12px 20px 16px; font-size: 12px; color: #6b7280">{{ $r['note'] }}</div>
@endif
