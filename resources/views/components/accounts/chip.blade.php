{{-- Filter chip (spec §2): active indigo, inactive white. --}}
@props(['on' => false, 'count' => null])

<button type="button" {{ $attributes->class([
    'rounded-[20px] border px-[12px] py-[6px] text-[12.5px]',
    'border-[#4f46e5] bg-[#4f46e5] font-semibold text-white' => $on,
    'border-[#e5e7eb] bg-white text-[#374151]' => ! $on,
]) }}>{{ $slot }}@if ($count !== null) <span class="opacity-70">{{ $count }}</span>@endif</button>
