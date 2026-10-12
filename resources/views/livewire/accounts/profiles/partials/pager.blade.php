<div class="flex flex-wrap items-center justify-between gap-[10px] border-t border-[#f0f1f4] px-[18px] py-[12px] text-[13px] text-[#6b7280]">
    <span>Showing {{ $rows->total() ? number_format($rows->firstItem()) . '–' . number_format($rows->lastItem()) : '0' }} of {{ number_format($rows->total()) }}</span>
    <div class="flex gap-[6px]">
        <button type="button" wire:click="previousPage" @disabled($rows->onFirstPage())
                class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] {{ $rows->onFirstPage() ? 'text-[#9ca3af]' : 'text-[#374151]' }}">Previous</button>
        <button type="button" wire:click="nextPage" @disabled(! $rows->hasMorePages())
                class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] {{ $rows->hasMorePages() ? 'text-[#374151]' : 'text-[#9ca3af]' }}">Next</button>
    </div>
</div>
