{{-- Modal in the Accounts Panel design language: white, 14px radius, 15px bold header. --}}
@props(['title', 'close', 'width' => 'max-w-[520px]'])

<div class="acc-no-print fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-[#0f172a]/50 p-4 sm:items-center"
     x-data x-on:keydown.escape.window="$el.querySelector('[data-close]').click()" role="dialog" aria-modal="true" aria-label="{{ $title }}">
    <div {{ $attributes->class([$width, 'my-8 w-full overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white text-[14px] text-[#111827] shadow-xl']) }}>
        <div class="flex items-center justify-between border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <h2 class="m-0 text-[15px] font-bold">{{ $title }}</h2>
            <button type="button" data-close wire:click="{{ $close }}" aria-label="Close" class="rounded-[8px] p-1 text-[#6b7280] hover:bg-[#f3f4f6]">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="flex flex-col gap-[14px] px-[18px] py-[16px]">
            {{ $slot }}
        </div>
        @isset($footer)
            <div class="flex flex-wrap justify-end gap-[10px] border-t border-[#f0f1f4] bg-[#f9fafb] px-[18px] py-[12px]">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
