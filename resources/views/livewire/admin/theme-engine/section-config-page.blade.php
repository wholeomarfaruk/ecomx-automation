<div x-data x-init="$store.pageName = { name: 'Configure — {{ ucwords(str_replace('-', ' ', $section)) }}', slug: 'frontend' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.frontend.menu.show', $page) }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ ucwords(str_replace('-', ' ', $section)) }}</h1>
                <p class="text-xs text-gray-400">{{ ucfirst($page) }} page section</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.frontend.menu.show', $page) }}" wire:navigate
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            @if (collect($this->fields())->contains(fn ($f) => $f['type'] !== 'category_list'))
                <button type="button" wire:click="save"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                    Save Changes
                </button>
                <button type="button" wire:click="saveAndExit"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition">
                    Save &amp; Back to Page
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
        @if (empty($this->fields()))
            <x-empty-state title="Nothing to configure" description="No configurable fields for this section yet." />
        @else
            @foreach ($this->fields() as $field)
                <div class="pb-6 border-b border-gray-100 last:border-b-0 last:pb-0">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-800">{{ $field['label'] }}</h3>
                        @if ($field['type'] === 'media_list')
                            <button type="button" wire:click="addMediaSlot('{{ $field['key'] }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                + Add Image
                            </button>
                        @elseif ($field['type'] === 'text_list')
                            <button type="button" wire:click="addTextItem('{{ $field['key'] }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                + Add Item
                            </button>
                        @elseif ($field['type'] === 'faq_list')
                            <button type="button" wire:click="addFaqItem('{{ $field['key'] }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                + Add FAQ
                            </button>
                        @elseif ($field['type'] === 'stat_list')
                            <button type="button" wire:click="addStatItem('{{ $field['key'] }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                + Add Stat
                            </button>
                        @endif
                    </div>

                    @if ($field['type'] === 'category_multi_select')
                        <p class="text-xs text-gray-400 mb-3">
                            Pick up to {{ $field['max'] ?? count($categories) }} categories
                            ({{ count($values[$field['key']] ?? []) }}/{{ $field['max'] ?? count($categories) }} selected).
                        </p>
                    @elseif ($field['type'] === 'category_list')
                        <p class="text-xs text-gray-400 mb-3">
                            Pick the categories to show as tiles in this section.
                        </p>
                    @endif

                    @if ($field['type'] === 'text')
                        <input type="text"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            value="{{ $values[$field['key']] ?? '' }}"
                            onchange="@this.call('updateText', '{{ $field['key'] }}', this.value)"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    @elseif ($field['type'] === 'category_select')
                        <select onchange="@this.call('updateCategorySelect', '{{ $field['key'] }}', this.value)"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                            <option value="">— None (use demo products) —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['id'] }}" {{ (string) ($values[$field['key']] ?? '') === (string) $cat['id'] ? 'selected' : '' }}>
                                    {{ $cat['name'] }}
                                </option>
                            @endforeach
                        </select>
                    @elseif ($field['type'] === 'category_multi_select' || $field['type'] === 'category_list')
                        @php $selected = $values[$field['key']] ?? []; @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @forelse ($categories as $cat)
                                @php $isChecked = in_array($cat['id'], $selected, true); @endphp
                                <label class="flex items-center gap-2 border rounded-lg p-2 text-sm cursor-pointer {{ $isChecked ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200' }}">
                                    <input type="checkbox"
                                        {{ $isChecked ? 'checked' : '' }}
                                        {{ (! $isChecked && isset($field['max']) && count($selected) >= $field['max']) ? 'disabled' : '' }}
                                        onchange="@this.call('toggleCategoryMultiSelect', '{{ $field['key'] }}', {{ $cat['id'] }}, this.checked)"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-400">
                                    <span class="truncate">{{ $cat['name'] }}</span>
                                </label>
                            @empty
                                <p class="col-span-full text-sm text-gray-400">No active categories found.</p>
                            @endforelse
                        </div>
                    @elseif ($field['type'] === 'media_list')
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @forelse ($values[$field['key']] ?? [] as $i => $item)
                                <div wire:key="{{ $field['key'] }}-{{ $item['id'] }}">
                                    <div class="relative mb-2">
                                        <img src="{{ $item['url'] }}" class="w-full aspect-square object-cover rounded-lg border border-gray-200">
                                        <button type="button" wire:click="removeMediaItem('{{ $field['key'] }}', {{ $i }})"
                                            class="absolute top-1 right-1 w-6 h-6 flex items-center justify-center bg-red-600 text-white rounded-full hover:bg-red-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <input type="text"
                                        placeholder="Link (optional)"
                                        value="{{ $item['link'] ?? '' }}"
                                        onchange="@this.call('updateMediaLink', '{{ $field['key'] }}', {{ $i }}, this.value)"
                                        class="w-full rounded-lg border-gray-300 text-xs focus:border-indigo-400 focus:ring-indigo-400">
                                </div>
                            @empty
                                <p class="col-span-full text-sm text-gray-400">No images added yet.</p>
                            @endforelse
                        </div>
                    @elseif ($field['type'] === 'text_list')
                        @if (empty($values[$field['key']] ?? []))
                            <p class="text-sm text-gray-400">No items added yet.</p>
                        @else
                            <div class="text-list-sortable space-y-2" data-field="{{ $field['key'] }}" wire:ignore.self>
                                @foreach ($values[$field['key']] as $i => $item)
                                    <div class="flex items-center gap-2" wire:key="{{ $field['key'] }}-item-{{ $i }}" data-index="{{ $i }}">
                                        <span class="text-list-drag-handle cursor-grab text-gray-300 shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                            </svg>
                                        </span>
                                        <input type="text"
                                            value="{{ $item['text'] ?? '' }}"
                                            onchange="@this.call('updateTextItem', '{{ $field['key'] }}', {{ $i }}, this.value)"
                                            class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                                        <button type="button" wire:click="removeTextItem('{{ $field['key'] }}', {{ $i }})"
                                            class="shrink-0 text-red-500 hover:text-red-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @elseif ($field['type'] === 'faq_list')
                        @if (empty($values[$field['key']] ?? []))
                            <p class="text-sm text-gray-400">No FAQs added yet.</p>
                        @else
                            <div class="faq-list-sortable space-y-3" data-field="{{ $field['key'] }}" wire:ignore.self>
                                @foreach ($values[$field['key']] as $i => $item)
                                    <div class="flex items-start gap-2" wire:key="{{ $field['key'] }}-faq-{{ $i }}" data-index="{{ $i }}">
                                        <span class="faq-list-drag-handle cursor-grab text-gray-300 shrink-0 mt-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                            </svg>
                                        </span>
                                        <div class="flex-1 space-y-2">
                                            <input type="text" placeholder="Question"
                                                value="{{ $item['q'] ?? '' }}"
                                                onchange="@this.call('updateFaqItem', '{{ $field['key'] }}', {{ $i }}, 'q', this.value)"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                                            <textarea rows="2" placeholder="Answer"
                                                onchange="@this.call('updateFaqItem', '{{ $field['key'] }}', {{ $i }}, 'a', this.value)"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">{{ $item['a'] ?? '' }}</textarea>
                                        </div>
                                        <button type="button" wire:click="removeFaqItem('{{ $field['key'] }}', {{ $i }})"
                                            class="shrink-0 mt-2 text-red-500 hover:text-red-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @elseif ($field['type'] === 'stat_list')
                        @if (empty($values[$field['key']] ?? []))
                            <p class="text-sm text-gray-400">No stats added yet.</p>
                        @else
                            <div class="stat-list-sortable space-y-2" data-field="{{ $field['key'] }}" wire:ignore.self>
                                @foreach ($values[$field['key']] as $i => $item)
                                    <div class="flex items-center gap-2" wire:key="{{ $field['key'] }}-stat-{{ $i }}" data-index="{{ $i }}">
                                        <span class="stat-list-drag-handle cursor-grab text-gray-300 shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                            </svg>
                                        </span>
                                        <input type="text" placeholder="Value (e.g. 40,000+)"
                                            value="{{ $item['val'] ?? '' }}"
                                            onchange="@this.call('updateStatItem', '{{ $field['key'] }}', {{ $i }}, 'val', this.value)"
                                            class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                                        <input type="text" placeholder="Label (e.g. Happy customers)"
                                            value="{{ $item['label'] ?? '' }}"
                                            onchange="@this.call('updateStatItem', '{{ $field['key'] }}', {{ $i }}, 'label', this.value)"
                                            class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                                        <button type="button" wire:click="removeStatItem('{{ $field['key'] }}', {{ $i }})"
                                            class="shrink-0 text-red-500 hover:text-red-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    @push('scripts')
    <script>
    document.addEventListener('livewire:init', () => {
        const initSortables = () => {
            document.querySelectorAll('.text-list-sortable').forEach((el) => {
                if (el.dataset.sortableInit) return;
                el.dataset.sortableInit = '1';

                new Sortable(el, {
                    handle: '.text-list-drag-handle',
                    animation: 150,
                    onEnd: () => {
                        const indexes = Array.from(el.querySelectorAll('[data-index]')).map(row => parseInt(row.dataset.index, 10));
                        const root = el.closest('[wire\\:id]');
                        const component = Livewire.find(root.getAttribute('wire:id'));
                        component.call('reorderTextItems', el.dataset.field, indexes);
                    },
                });
            });

            document.querySelectorAll('.faq-list-sortable').forEach((el) => {
                if (el.dataset.sortableInit) return;
                el.dataset.sortableInit = '1';

                new Sortable(el, {
                    handle: '.faq-list-drag-handle',
                    animation: 150,
                    onEnd: () => {
                        const indexes = Array.from(el.querySelectorAll('[data-index]')).map(row => parseInt(row.dataset.index, 10));
                        const root = el.closest('[wire\\:id]');
                        const component = Livewire.find(root.getAttribute('wire:id'));
                        component.call('reorderFaqItems', el.dataset.field, indexes);
                    },
                });
            });

            document.querySelectorAll('.stat-list-sortable').forEach((el) => {
                if (el.dataset.sortableInit) return;
                el.dataset.sortableInit = '1';

                new Sortable(el, {
                    handle: '.stat-list-drag-handle',
                    animation: 150,
                    onEnd: () => {
                        const indexes = Array.from(el.querySelectorAll('[data-index]')).map(row => parseInt(row.dataset.index, 10));
                        const root = el.closest('[wire\\:id]');
                        const component = Livewire.find(root.getAttribute('wire:id'));
                        component.call('reorderStatItems', el.dataset.field, indexes);
                    },
                });
            });
        };

        initSortables();
        Livewire.hook('morph.updated', () => {
            document.querySelectorAll('.text-list-sortable, .faq-list-sortable, .stat-list-sortable').forEach((el) => delete el.dataset.sortableInit);
            initSortables();
        });
    });
    </script>
    @endpush
</div>
