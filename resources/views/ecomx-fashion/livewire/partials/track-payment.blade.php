{{-- Payment status on the track page — see Order::paymentSummary(). Expects $order. --}}
@php
    $pay = $order->paymentSummary();
    $badge = [
        'paid'      => ['Paid', '#1e7e4f', 'rgba(30,126,79,.1)'],
        'partial'   => ['Partially paid', '#b85c00', 'rgba(184,92,0,.1)'],
        'verifying' => ['Verifying payment', '#9a6b00', 'rgba(154,107,0,.1)'],
        'unpaid'    => ['Unpaid', 'rgba(var(--pri-rgb),.6)', 'rgba(var(--pri-rgb),.06)'],
    ][$pay['state']];
@endphp

<div style="border:1px solid rgba(var(--pri-rgb),.1);border-radius:12px;padding:14px 16px;margin-bottom:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px">
        <p style="font-size:13px;font-weight:600;margin:0">Payment</p>
        <span style="font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:999px;color:{{ $badge[1] }};background:{{ $badge[2] }}">{{ $badge[0] }}</span>
    </div>

    <div style="display:flex;flex-direction:column;gap:4px;font-size:12.5px">
        <div style="display:flex;justify-content:space-between"><span class="muted">Total</span><span>৳{{ number_format($pay['total'], 2) }}</span></div>
        <div style="display:flex;justify-content:space-between"><span class="muted">Paid</span><span style="color:#1e7e4f">৳{{ number_format($pay['paid'], 2) }}</span></div>
        <div style="display:flex;justify-content:space-between;font-weight:700"><span>Due</span><span>৳{{ number_format($pay['due'], 2) }}</span></div>
    </div>

    @foreach($pay['pending'] as $p)
        <div style="margin-top:10px;padding:9px 12px;border-radius:8px;background:rgba(154,107,0,.08);font-size:12px;color:#7a5500">
            <strong>{{ $p->payment_method?->label() ?? 'Payment' }} ৳{{ number_format($p->amount, 2) }}</strong>
            @if($p->transaction_id) · TrxID {{ $p->transaction_id }} @endif
            <br>Received — we're verifying it. Your due will update once it's confirmed.
        </div>
    @endforeach

    @foreach($pay['rejected'] as $p)
        <div style="margin-top:10px;padding:9px 12px;border-radius:8px;background:rgba(192,57,43,.07);font-size:12px;color:#a93226">
            <strong>{{ $p->payment_method?->label() ?? 'Payment' }} ৳{{ number_format($p->amount, 2) }}</strong>
            @if($p->transaction_id) · TrxID {{ $p->transaction_id }} @endif
            <br>We couldn't verify this payment. Please contact us.
        </div>
    @endforeach
</div>
