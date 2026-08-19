<div x-data="{ tab: @entangle('activeTab').live }">
    <div class="flex flex-col sm:flex-row gap-6">
        <div class="sm:w-48 shrink-0">
            <div class="flex sm:flex-col gap-1">
                <button type="button" wire:click="setActiveTab('sections')"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg text-left transition"
                    :class="tab === 'sections' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h12A2.25 2.25 0 0 1 20.25 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h5.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM16.5 13.5a2.25 2.25 0 0 0-2.25 2.25V18a2.25 2.25 0 0 0 2.25 2.25h1.5A2.25 2.25 0 0 0 20.25 18v-2.25a2.25 2.25 0 0 0-2.25-2.25h-1.5Z" />
                    </svg>
                    Sections
                </button>
                <button type="button" wire:click="setActiveTab('seo')"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg text-left transition"
                    :class="tab === 'seo' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    SEO
                </button>
                <button type="button" wire:click="setActiveTab('others')"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg text-left transition"
                    :class="tab === 'others' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Others
                </button>
            </div>
        </div>

        <div class="flex-1 min-w-0">
            {{-- Sections tab --}}
            <div x-show="tab === 'sections'">
                @if (empty($sections))
                    <x-empty-state title="No sections configured" description="No sections configured for this page yet." />
                @else
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-10"></th>
                                    <th class="px-4 py-2.5 text-left font-medium text-gray-500">Section</th>
                                    <th class="px-4 py-2.5 text-left font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-2.5 text-left font-medium text-gray-500">Edit</th>
                                </tr>
                            </thead>
                            <tbody id="section-sortable-body" wire:ignore.self class="divide-y divide-gray-100">
                                @foreach ($sections as $section)
                                    <tr wire:key="section-{{ $section['key'] }}" data-key="{{ $section['key'] }}">
                                        <td class="section-drag-handle px-4 py-2.5 cursor-grab text-gray-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                            </svg>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-700">{{ ucwords(str_replace('-', ' ', $section['key'])) }}</td>
                                        <td class="px-4 py-2.5" wire:key="switch-{{ $section['key'] }}-{{ $section['active'] ? '1' : '0' }}">
                                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" class="sr-only peer" {{ $section['active'] ? 'checked' : '' }}
                                                    onchange="@this.call('toggleActive', '{{ $section['key'] }}', this.checked)">
                                                <span class="w-9 h-5 bg-gray-200 rounded-full peer-checked:bg-emerald-500 relative transition after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition peer-checked:after:translate-x-4"></span>
                                                <span class="text-xs {{ $section['active'] ? 'text-emerald-600' : 'text-gray-400' }}">{{ $section['active'] ? 'Active' : 'Inactive' }}</span>
                                            </label>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <button type="button"
                                                onclick="Livewire.dispatch('open-section-config-editor', { page: '{{ $page }}', section: '{{ $section['key'] }}' })"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                </svg>
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- SEO tab --}}
            <div x-show="tab === 'seo'">
                <form wire:submit.prevent="saveSeo" class="space-y-4 max-w-xl">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                        <input type="text" wire:model="metaTitle" maxlength="255"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                        <textarea wire:model="metaDescription" rows="4" maxlength="500"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OG Image URL</label>
                        <input type="text" wire:model="ogImage" placeholder="https://..."
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    </div>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        Save SEO Settings
                    </button>
                </form>
            </div>

            {{-- Others tab --}}
            <div x-show="tab === 'others'">
                <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl">
                    <div>
                        <h5 class="font-medium text-gray-800 mb-1">Page Visibility</h5>
                        <p class="text-sm text-gray-400">Unpublish to hide this page from the storefront without deleting its configuration.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0" wire:key="published-{{ $published ? '1' : '0' }}">
                        <input type="checkbox" class="sr-only peer" {{ $published ? 'checked' : '' }}
                            onchange="@this.call('togglePublished', this.checked)">
                        <span class="w-9 h-5 bg-gray-200 rounded-full peer-checked:bg-emerald-500 relative transition after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition peer-checked:after:translate-x-4"></span>
                        <span class="text-sm {{ $published ? 'text-emerald-600' : 'text-gray-400' }}">{{ $published ? 'Published' : 'Draft' }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('livewire:init', () => {
        let sortable = null;

        const initSortable = () => {
            const el = document.getElementById('section-sortable-body');
            if (!el || el.dataset.sortableInit) return;
            el.dataset.sortableInit = '1';

            sortable = new Sortable(el, {
                handle: '.section-drag-handle',
                animation: 150,
                onEnd: () => {
                    const keys = Array.from(el.querySelectorAll('tr')).map(tr => tr.dataset.key);
                    const root = el.closest('[wire\\:id]');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('reorder', keys);
                },
            });
        };

        initSortable();
        Livewire.hook('morph.updated', () => {
            const el = document.getElementById('section-sortable-body');
            if (el) delete el.dataset.sortableInit;
            initSortable();
        });
    });
    </script>
    @endpush
</div>
