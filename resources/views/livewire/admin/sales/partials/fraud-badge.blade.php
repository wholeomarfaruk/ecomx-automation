{{-- Orders list row badge ($fc: ?FraudCheck, $orderId, optional $error). Also returned as HTML by OrderFraudCheckController, so it uses plain onclick (no Alpine scope needed). --}}
@php $openModal = "event.stopPropagation(); window.dispatchEvent(new CustomEvent('open-fraud-check', { detail: { orderId: {$orderId} } }))"; @endphp
@if ($fc)
    <button type="button" onclick="{{ $openModal }}"
        title="FraudShield: {{ $fc->success_parcel }}/{{ $fc->total_parcel }} delivered, {{ $fc->cancelled_parcel }} cancelled · checked {{ local_time($fc->checked_at)?->diffForHumans() }}{{ ! empty($error) ? ' · ' . $error : '' }}"
        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium ring-1 {{ $fc->badgeClass() }}">
        {{ $fc->tone() === 'neutral' ? 'New' : rtrim(rtrim(number_format($fc->success_ratio, 1), '0'), '.') . '%' }}
        <span class="opacity-70">· {{ $fc->levelLabel() }}</span>
    </button>
@elseif (! empty($error))
    <button type="button" onclick="{{ $openModal }}" title="{{ $error }}"
        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium ring-1 bg-gray-50 text-gray-400 ring-gray-200">
        Check failed
    </button>
@endif
