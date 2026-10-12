{{-- Summary card: 12.5px label, 24px/700 value, 12px gray sub-line. --}}
@props(['label', 'value', 'sub' => null, 'color' => null])

<div {{ $attributes->class('rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]') }}>
    <div class="text-[12.5px] text-[#4b5563]">{{ $label }}</div>
    <div class="mt-[6px] text-[24px] font-bold" @if ($color) style="color: {{ $color }}" @endif>{{ $value }}</div>
    @if ($sub !== null)
        <div class="mt-[4px] text-[12px] text-[#6b7280]">{{ $sub }}</div>
    @endif
</div>
