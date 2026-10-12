{{-- H1 24px/700 + gray 13px subtitle, actions right-aligned. --}}
@props(['title', 'subtitle' => null])

<div class="flex flex-wrap items-end justify-between gap-[14px]">
    <div>
        <h1 class="m-0 text-[24px] font-bold">{{ $title }}</h1>
        @if ($subtitle)
            <div class="mt-[2px] text-[13px] text-[#6b7280]">{{ $subtitle }}</div>
        @endif
    </div>
    <div class="acc-no-print flex flex-wrap gap-[8px]">
        {{ $slot }}
    </div>
</div>
