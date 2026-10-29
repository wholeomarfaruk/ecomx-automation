{{--
    Issue state of any record using HasIssues — load the counts with
    withCount/loadCount ('issues as open_issues_count' etc., see Orders /
    OrderDetail) and pass them in. Open wins: red "Issue"; otherwise green
    "Fixed" (something was resolved) or grey "Closed" (only dropped ones).
    Renders nothing when the record has no issues.
--}}
@props([
    'open'     => 0,
    'resolved' => 0,
    'closed'   => 0,
    'href'     => null,
    'size'     => 'sm',
])

@php
    $open = (int) $open;
    $resolved = (int) $resolved;
    $closed = (int) $closed;

    [$label, $title, $classes, $icon] = match (true) {
        $open > 0 => [
            $open > 1 ? "{$open} Issues" : 'Issue',
            $open . ' open issue' . ($open > 1 ? 's' : '') . ($resolved + $closed > 0 ? ' · ' . ($resolved + $closed) . ' done' : ''),
            'bg-red-50 text-red-600 ring-red-200',
            'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        ],
        $resolved > 0 => [
            'Fixed',
            $resolved . ' issue' . ($resolved > 1 ? 's' : '') . ' resolved',
            'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z',
        ],
        $closed > 0 => [
            'Closed',
            $closed . ' issue' . ($closed > 1 ? 's' : '') . ' closed',
            'bg-gray-100 text-gray-500 ring-gray-200',
            'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        default => [null, null, null, null],
    };

    $sizeClasses = $size === 'md' ? 'px-3 py-1.5 text-xs gap-1.5' : 'px-1.5 py-0.5 text-[10px] gap-1';
    $iconClasses = $size === 'md' ? 'h-3.5 w-3.5' : 'h-3 w-3';
@endphp

@if ($label)
    <{{ $href ? 'a' : 'span' }} @if ($href) href="{{ $href }}" @endif title="{{ $title }}"
        {{ $attributes->merge(['class' => "inline-flex items-center rounded-full font-medium ring-1 {$sizeClasses} {$classes}"]) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconClasses }} shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
        {{ $label }}
    </{{ $href ? 'a' : 'span' }}>
@endif
