<div x-data="{
        selected: $wire.entangle('selected'),
        requires: @js(collect($groups)->map(fn ($g) => $g['requires'])),
        modal: false,
        get effective() {
            const out = new Set(), queue = [...this.selected];
            while (queue.length) {
                const k = queue.shift();
                if (out.has(k)) continue;
                out.add(k);
                queue.push(...(this.requires[k] || []));
            }
            return out;
        },
        isAuto(k) { return this.effective.has(k) && !this.selected.includes(k); },
        selectAll() { this.selected = Object.keys(this.requires); },
    }"
    x-init="$store.pageName = { name: 'Clean Data', slug: 'data-reset' }"
    @close-data-reset-modal.window="modal = false"
    wire:init="loadCounts" class="space-y-6">

    {{-- ══════════════════ WARNING ══════════════════ --}}
    <div class="bg-red-50 border border-red-200 rounded-2xl px-6 py-4 flex gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
        <div class="text-sm text-red-800 space-y-1">
            <p class="font-semibold">This permanently deletes data and cannot be undone.</p>
            <p class="text-xs text-red-700">Take a database backup first. Settings, theme, pages, menus, chart of accounts, warehouses, branches, couriers, templates, locations, roles, media library, superadmins and your own account are always kept.</p>
        </div>
    </div>

    {{-- ══════════════════ GROUPS ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Clean Data</h2>
                    <p class="text-xs text-gray-400">Choose what to wipe. Linked data is included automatically.</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="selectAll()"
                    class="text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Select all</button>
                <button type="button" @click="selected = []"
                    class="text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Clear</button>
            </div>
        </div>

        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($groups as $key => $group)
                <label class="border rounded-xl p-4 flex gap-3 cursor-pointer transition"
                    :class="effective.has('{{ $key }}') ? 'border-red-300 bg-red-50/50' : 'border-gray-100 hover:border-gray-200'">
                    <input type="checkbox" value="{{ $key }}" x-model="selected"
                        class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-800">{{ $group['label'] }}</p>
                            <span class="text-[11px] font-mono text-gray-500 shrink-0">
                                @if ($counts === null)
                                    <span class="inline-block w-8 h-3 bg-gray-100 rounded animate-pulse"></span>
                                @else
                                    {{ number_format($counts[$key] ?? 0) }} rows
                                @endif
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $group['description'] }}</p>
                        <p x-cloak x-show="isAuto('{{ $key }}')" class="text-[11px] font-medium text-red-600 mt-1.5">Included automatically (linked data)</p>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <p class="text-xs text-gray-500"><span x-text="effective.size"></span> group(s) will be wiped</p>
            <button type="button" @click="modal = true" :disabled="effective.size === 0"
                class="inline-flex items-center gap-1.5 text-xs font-medium px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
                Clean selected data
            </button>
        </div>
    </div>

    @if ($result)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-6 py-4 text-sm text-gray-700">
            <p class="font-semibold text-gray-900 mb-1">Last run</p>
            <p class="text-xs text-gray-500">
                Wiped: {{ collect($result['groups'])->map(fn ($k) => $groups[$k]['label'])->implode(', ') }}
                — {{ $result['tables'] }} tables emptied, {{ $result['users'] }} users and {{ $result['profiles'] }} profiles deleted.
            </p>
        </div>
    @endif

    {{-- ══════════════════ CONFIRM MODAL ══════════════════ --}}
    <div x-cloak x-show="modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @keydown.escape.window="modal = false">
        <div @click.outside="modal = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Confirm data wipe</h3>
                <p class="text-xs text-gray-500 mt-1">These groups will be permanently deleted:</p>
                <ul class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($groups as $key => $group)
                        <li x-show="effective.has('{{ $key }}')" class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ $group['label'] }}</li>
                    @endforeach
                </ul>
            </div>
            <form wire:submit="cleanData" class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Type <span class="font-mono font-semibold text-red-600">{{ $phrase }}</span> to confirm</label>
                    <input type="text" wire:model="confirmPhrase" autocomplete="off"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-red-500 focus:ring-red-500">
                    @error('confirmPhrase') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Your password</label>
                    <input type="password" wire:model="password" autocomplete="current-password"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-red-500 focus:ring-red-500">
                    @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                @error('selected') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="modal = false"
                        class="text-xs font-medium px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="cleanData"
                        class="text-xs font-medium px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 transition">
                        <span wire:loading.remove wire:target="cleanData">Delete permanently</span>
                        <span wire:loading wire:target="cleanData">Cleaning…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
