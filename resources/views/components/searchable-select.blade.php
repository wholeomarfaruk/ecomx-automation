@props([
    'field',
    'value'       => '',
    'options'     => [],
    'placeholder' => 'All',
    'searchPlaceholder' => 'Search…',
])

@php
    $optionsJson = json_encode(
        collect($options)->map(fn ($label, $val) => ['value' => (string) $val, 'label' => $label])->values()
    );
@endphp

<div
    x-data="{
        open: false,
        query: '',
        options: {{ $optionsJson }},
        value: @entangle($field).live,
        get selectedLabel() {
            const match = this.options.find(o => o.value === String(this.value ?? ''));
            return match ? match.label : '{{ $placeholder }}';
        },
        get filtered() {
            if (!this.query) return this.options;
            const q = this.query.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(q));
        },
        select(val) {
            this.value = val;
            this.open = false;
            this.query = '';
        },
    }"
    @click.outside="open = false"
    class="relative"
>
    <button type="button" @click="open = !open"
        class="flex items-center justify-between gap-2 text-sm rounded-lg border border-gray-300 px-3 py-2 bg-white hover:border-gray-400 transition w-full min-w-[160px]
               focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
        <span x-text="selectedLabel" :class="value ? 'text-gray-700' : 'text-gray-400'" class="truncate"></span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-cloak x-transition
        class="absolute z-30 top-full left-0 mt-1 w-full min-w-64 rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden">
        <div class="p-2 border-b border-gray-100">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input type="text" x-model="query" x-ref="search" @keydown.escape="open = false"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full pl-7 pr-2 py-1.5 text-sm rounded-md border border-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>
        <div class="max-h-56 overflow-y-auto py-1">
            <button type="button" @click="select('')"
                class="w-full text-left px-3 py-1.5 text-sm hover:bg-gray-50 transition"
                :class="!value ? 'text-indigo-600 font-medium' : 'text-gray-600'">
                {{ $placeholder }}
            </button>
            <template x-for="option in filtered" :key="option.value">
                <button type="button" @click="select(option.value)"
                    class="w-full text-left px-3 py-1.5 text-sm hover:bg-gray-50 transition truncate"
                    :class="value === option.value ? 'text-indigo-600 font-medium bg-indigo-50/50' : 'text-gray-600'"
                    x-text="option.label">
                </button>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-2 text-xs text-gray-400">No matches</p>
        </div>
    </div>
</div>
