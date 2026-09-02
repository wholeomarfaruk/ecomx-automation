{{-- Shared Today/Yesterday/7d/30d/90d/All time/Custom range picker. Expects $range, $customFrom, $customTo, and $this->ranges() in scope. --}}
<div class="flex flex-col gap-2 items-start sm:items-end">
    <div class="inline-flex rounded-lg bg-gray-100 border border-gray-200 p-0.5 self-start sm:self-end flex-wrap">
        @foreach ($this->ranges() as $key => $label)
            <button type="button" wire:click="$set('range', '{{ $key }}')"
                class="px-2 py-1 text-xs font-medium rounded-md transition-colors
                    {{ $range === $key ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($range === 'custom')
        <div class="flex items-center gap-2">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input wire:model.live="customFrom" type="text" placeholder="From" readonly
                    class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-xs text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-32 cursor-pointer">
            </div>
            <span class="text-xs text-gray-400">to</span>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input wire:model.live="customTo" type="text" placeholder="To" readonly
                    class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-xs text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-32 cursor-pointer">
            </div>
        </div>
    @endif
</div>
