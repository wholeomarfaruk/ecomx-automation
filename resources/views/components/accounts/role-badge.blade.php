{{-- Role badge — 11.5px/600, radius 6px, padding 2px 8px (spec §2). --}}
@props(['role'])

@php
    $styles = [
        'customer' => 'bg-[#d1fae5] text-[#065f46]',
        'supplier' => 'bg-[#e0f2fe] text-[#075985]',
        'lender'   => 'bg-[#e0e7ff] text-[#3730a3]',
        'owner'    => 'bg-[#fef3c7] text-[#92400e]',
        'employee' => 'bg-[#ccfbf1] text-[#115e59]',
    ];
    $key = strtolower($role);
@endphp

<span {{ $attributes->class(['whitespace-nowrap rounded-[6px] px-[8px] py-[2px] text-[11.5px] font-semibold', $styles[$key] ?? 'bg-[#f3f4f6] text-[#374151]']) }}>{{ $slot->isEmpty() ? ucfirst($key) : $slot }}</span>
