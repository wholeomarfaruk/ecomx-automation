{{-- One FraudShield result ($check: App\Models\FraudCheck). Used by the Fraud Check modal and Advance → Fraud Checker. --}}
@php
    $tone = $check->tone();
    $ringColor = match ($tone) { 'safe' => '#10b981', 'warning' => '#f59e0b', 'danger' => '#ef4444', default => '#d1d5db' };
    $ratio = max(0, min(100, $check->success_ratio));
    $checkedAt = local_time($check->checked_at);
@endphp

<div class="space-y-5">
    {{-- Headline: success ratio ring + totals --}}
    <div class="flex items-center gap-5">
        <div class="relative w-24 h-24 shrink-0">
            <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f3f4f6" stroke-width="3.5"/>
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="{{ $ringColor }}" stroke-width="3.5" stroke-linecap="round"
                    stroke-dasharray="{{ round($ratio, 2) }} 100" pathLength="100"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-lg font-bold text-gray-900 leading-none">{{ rtrim(rtrim(number_format($ratio, 1), '0'), '.') }}%</span>
                <span class="text-[10px] text-gray-400 mt-0.5">success</span>
            </div>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 {{ $check->badgeClass() }}">
                    {{ $check->levelLabel() }}
                </span>
                @if ($check->label)
                    <span class="text-sm text-gray-600">{{ $check->label }}</span>
                @endif
                @if ($check->score !== null)
                    <span class="text-xs text-gray-400">Risk score {{ $check->score }}/100</span>
                @endif
            </div>
            <div class="grid grid-cols-3 gap-2 mt-3">
                <div class="rounded-lg bg-gray-50 px-3 py-2">
                    <p class="text-[11px] text-gray-400">Total</p>
                    <p class="text-base font-semibold text-gray-900">{{ $check->total_parcel }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 px-3 py-2">
                    <p class="text-[11px] text-emerald-600">Delivered</p>
                    <p class="text-base font-semibold text-emerald-700">{{ $check->success_parcel }}</p>
                </div>
                <div class="rounded-lg bg-red-50 px-3 py-2">
                    <p class="text-[11px] text-red-500">Cancelled</p>
                    <p class="text-base font-semibold text-red-600">{{ $check->cancelled_parcel }}</p>
                </div>
            </div>
        </div>
    </div>

    @if ($check->tone() === 'neutral')
        <p class="text-sm text-gray-500 rounded-lg bg-gray-50 px-4 py-3">No courier history for this number — likely a new customer.</p>
    @endif

    {{-- Per courier --}}
    @if ($couriers = $check->couriers())
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">By courier</p>
            <div class="rounded-lg border border-gray-100 divide-y divide-gray-100">
                @foreach ($couriers as $key => $row)
                    @php $rowRatio = max(0, min(100, (float) ($row['success_ratio'] ?? 0))); @endphp
                    <div class="flex items-center gap-3 px-3 py-2.5">
                        <div class="w-7 h-7 shrink-0 rounded bg-white border border-gray-100 flex items-center justify-center overflow-hidden">
                            @if (! empty($row['logo']) && str_starts_with($row['logo'], 'http'))
                                <img src="{{ $row['logo'] }}" alt="" class="w-full h-full object-contain" loading="lazy">
                            @else
                                <span class="text-[10px] font-semibold text-gray-400">{{ strtoupper(substr($row['name'] ?? $key, 0, 2)) }}</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-800 truncate">{{ $row['name'] ?? ucfirst($key) }}</span>
                                <span class="text-xs text-gray-500 tabular-nums whitespace-nowrap">
                                    {{ (int) ($row['success_parcel'] ?? 0) }}/{{ (int) ($row['total_parcel'] ?? 0) }}
                                    @if (($row['cancelled_parcel'] ?? 0) > 0)
                                        · <span class="text-red-500">{{ (int) $row['cancelled_parcel'] }} cancelled</span>
                                    @endif
                                </span>
                            </div>
                            <div class="mt-1.5 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $rowRatio >= 80 ? 'bg-emerald-500' : ($rowRatio >= 60 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $rowRatio }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-xs font-medium text-gray-700 tabular-nums">{{ rtrim(rtrim(number_format($rowRatio, 1), '0'), '.') }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Reviews --}}
    @if ($reviews = $check->reviews())
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Reviews ({{ count($reviews) }})</p>
            <div class="space-y-2">
                @foreach ($reviews as $review)
                    <div class="rounded-lg border border-gray-100 px-3 py-2.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-700">{{ $review['name'] ?? 'Anonymous' }}</span>
                            @if (isset($review['rating']))
                                <span class="text-amber-500">{{ str_repeat('★', max(0, min(5, (int) $review['rating']))) }}<span class="text-gray-200">{{ str_repeat('★', 5 - max(0, min(5, (int) $review['rating']))) }}</span></span>
                            @endif
                        </div>
                        @if (! empty($review['comment']))
                            <p class="text-sm text-gray-600 mt-1">{{ $review['comment'] }}</p>
                        @endif
                        @if (! empty($review['created_at']))
                            <p class="text-[11px] text-gray-400 mt-1">{{ \Illuminate\Support\Carbon::parse($review['created_at'])->format('d M, Y') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <p class="text-[11px] text-gray-400">
        {{ $check->phone }} · checked {{ $checkedAt?->diffForHumans() }}
        @if ($check->checker) by {{ $check->checker->name }} @endif
        · via FraudShield
    </p>
</div>
