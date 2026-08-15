{{--
    Phone Code Picker
    @param string $wireProperty  — Livewire property name to bind (e.g. 'newUserCountryCode')
    @param string $inputClass    — extra classes for the trigger button (optional)
--}}
@php
    $countriesJson = $countries->map(fn($c) => [
        'id'   => $c->id,
        'name' => $c->name,
        'code' => (string) $c->phone_code,
        'flag' => $c->emoji_flag ?? '',
    ])->values();
@endphp

<div x-data="{
        open: false,
        search: '',
        list: {{ Js::from($countriesJson) }},
        get model()  { return $wire.{{ $wireProperty }}; },
        set model(v) { $wire.set('{{ $wireProperty }}', v); },
        get label()  { return this.model ? this.model : ''; },
        get filtered() {
            const s = this.search.toLowerCase().replace('+', '');
            if (!s) return this.list;
            return this.list.filter(c =>
                c.name.toLowerCase().includes(s) || c.code.includes(s)
            );
        },
        pick(code) { this.model = code; this.open = false; this.search = ''; },
        toggle() {
            this.open = !this.open;
            if (this.open) this.$nextTick(() => this.$refs.search?.focus());
        }
     }"
     @click.outside="open = false"
     class="relative {{ $inputClass ?? '' }}">

    {{-- Trigger --}}
    <button type="button" @click="toggle()"
        class="w-full flex items-center justify-between gap-2 rounded-xl border border-gray-300 px-3 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition">
        <span x-text="label || '— Code —'" :class="model ? 'text-gray-900 font-medium' : 'text-gray-400'"></span>
        <svg class="h-4 w-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"
             fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 top-full left-0 mt-1 w-72 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">

        {{-- Search --}}
        <div class="p-2 border-b border-gray-100">
            <input x-ref="search" x-model="search" type="text"
                placeholder="Search country or code…"
                @keydown.escape.stop="open = false"
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400">
        </div>

        {{-- List --}}
        <div class="max-h-52 overflow-y-auto py-1">
            <template x-for="c in filtered" :key="c.id">
                <button type="button" @click="pick(c.code)"
                    :class="model === c.code ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                    class="flex items-center gap-2.5 w-full px-3 py-2 text-xs text-left transition">
                    <span x-text="c.flag" class="text-base w-5 text-center shrink-0 leading-none"></span>
                    <span x-text="c.code" class="font-mono font-semibold w-10 shrink-0"></span>
                    <span x-text="c.name" class="truncate text-gray-500"></span>
                </button>
            </template>
            <div x-show="filtered.length === 0"
                 class="px-3 py-5 text-xs text-gray-400 text-center">
                No results
            </div>
        </div>

        {{-- Clear --}}
        <div x-show="model" class="border-t border-gray-100 p-1.5">
            <button type="button" @click="pick('')"
                class="w-full px-3 py-1.5 text-xs text-red-500 hover:bg-red-50 rounded-lg transition text-left">
                Clear selection
            </button>
        </div>
    </div>
</div>
