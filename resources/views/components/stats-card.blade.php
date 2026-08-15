@props([
    'title'  => '',
    'value'  => '0',
    'icon'   => null,
    'color'  => 'blue',   // blue | green | red | yellow | purple | gray
    'trend'  => null,     // e.g. '+12%' or '-3%'
    'trendUp' => true,
])

@php
$colors = [
    'blue'   => 'bg-blue-50 text-blue-600',
    'green'  => 'bg-green-50 text-green-600',
    'red'    => 'bg-red-50 text-red-600',
    'yellow' => 'bg-yellow-50 text-yellow-600',
    'purple' => 'bg-purple-50 text-purple-600',
    'gray'   => 'bg-gray-50 text-gray-600',
];
$iconBg = $colors[$color] ?? $colors['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg p-5 shadow-sm flex items-start gap-4']) }}>
    @if ($icon)
        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center {{ $iconBg }}">
            {!! $icon !!}
        </div>
    @endif

    <div class="flex-1 min-w-0">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide truncate">{{ $title }}</p>
        <p class="mt-1 text-2xl font-bold text-gray-800">{{ $value }}</p>

        @if ($trend)
            <p class="mt-1 text-xs font-medium {{ $trendUp ? 'text-green-600' : 'text-red-500' }}">
                {{ $trendUp ? '▲' : '▼' }} {{ $trend }}
                <span class="text-gray-400 font-normal ml-1">vs last month</span>
            </p>
        @endif
    </div>
</div>
