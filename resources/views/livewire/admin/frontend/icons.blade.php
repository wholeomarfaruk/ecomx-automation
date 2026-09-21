<div x-data x-init="$store.pageName = { name: 'Icons', slug: 'icons' }">
    <div class="flex items-center justify-between mb-4 gap-4 flex-wrap">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Icon library</h3>
            <p class="text-xs text-gray-400 mt-0.5">
                Every icon in <code class="text-[11px] bg-gray-100 px-1 py-0.5 rounded">App\Support\IconLibrary</code> —
                the shared set behind <code class="text-[11px] bg-gray-100 px-1 py-0.5 rounded">&lt;x-icon&gt;</code> /
                <code class="text-[11px] bg-gray-100 px-1 py-0.5 rounded">&lt;x-anyniche::icon&gt;</code> and the Menus icon picker.
                Click a name to copy it. View-only — icons are added in code.
            </p>
        </div>
        <div class="w-full sm:w-64">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search icons..."
                class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
        </div>
    </div>

    <div class="mb-6">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Outline icons ({{ count($this->outlineIcons) }})</h4>
        @if (empty($this->outlineIcons))
            <x-empty-state title="No icons found" description="Try a different search term." />
        @else
            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                @foreach ($this->outlineIcons as $name)
                    <button type="button"
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText('{{ $name }}'); copied = true; setTimeout(() => copied = false, 1200)"
                        class="flex flex-col items-center gap-2 p-3 rounded-xl border border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/50 transition text-center">
                        <span class="text-gray-600"><x-icon :name="$name" size="22" /></span>
                        <span class="text-[11px] text-gray-500 truncate w-full" x-text="copied ? 'Copied!' : '{{ $name }}'"></span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Brand icons ({{ count($this->brandIcons) }})</h4>
        @if (empty($this->brandIcons))
            <x-empty-state title="No icons found" description="Try a different search term." />
        @else
            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                @foreach ($this->brandIcons as $name)
                    <button type="button"
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText('{{ $name }}'); copied = true; setTimeout(() => copied = false, 1200)"
                        class="flex flex-col items-center gap-2 p-3 rounded-xl border border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/50 transition text-center">
                        <span class="text-gray-600"><x-icon :name="$name" size="22" /></span>
                        <span class="text-[11px] text-gray-500 truncate w-full" x-text="copied ? 'Copied!' : '{{ $name }}'"></span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</div>
