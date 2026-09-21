<div x-data x-init="$store.pageName = { name: 'Menus', slug: 'frontend' }">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Storefront menus</h3>
            <p class="text-xs text-gray-400 mt-0.5">
                @if (in_array($activeMenu, ['header', 'topbar'], true))
                    Controls the links shown in the top bar above the header.
                @elseif ($activeMenu === 'search-categories')
                    Controls which categories appear in the search bar's "All categories" filter.
                @elseif (in_array($activeMenu, ['shop', 'categories'], true))
                    Controls the links shown in the storefront's slide-out menu.
                @elseif (str_starts_with($activeMenu, 'footer-'))
                    Controls the links shown in this footer column.
                @elseif ($activeMenu === 'main-nav')
                    Controls the header's primary navigation links.
                @else
                    Controls the links shown in this menu.
                @endif
            </p>
        </div>
    </div>

    @php $activeRegistry = \App\Livewire\Admin\Frontend\Menus::menuRegistry(); @endphp
    <div class="flex gap-1 mb-4 border-b border-gray-200">
        @foreach ($activeRegistry::MENUS as $menu)
            <button type="button" wire:click="setActiveMenu('{{ $menu }}')"
                class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition"
                :class="'{{ $activeMenu }}' === '{{ $menu }}' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'">
                {{ $activeRegistry::MENU_LABELS[$menu] ?? ucfirst($menu) }}
            </button>
        @endforeach
    </div>

    @if ($activeMenu === 'search-categories')
        @if (empty($searchCategoryItems))
            <x-empty-state title="No categories added" description="Add a category below to show it in the search filter." />
        @else
            <div class="rounded-xl border border-gray-200 divide-y divide-gray-100" id="search-cat-sortable-root" wire:ignore.self>
                @foreach ($searchCategoryItems as $item)
                    <div wire:key="searchcat-{{ $item['id'] }}" data-id="{{ $item['id'] }}" class="p-3 flex items-center gap-2">
                        <span class="menu-drag-handle cursor-grab text-gray-300 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                            </svg>
                        </span>
                        <span class="text-sm text-gray-700 font-medium flex-1 truncate">{{ $item['name'] }}</span>
                        <button type="button" wire:click="removeSearchCategory('{{ $item['id'] }}')" wire:confirm="Remove this category from the search filter?"
                            class="px-2 py-1 text-xs font-medium text-red-500 bg-white border border-gray-200 rounded-lg hover:bg-red-50 transition">
                            Remove
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        @php $availableCategories = collect($allCategories)->reject(fn ($c) => collect($searchCategoryItems)->contains('category_id', $c['id']))->values(); @endphp
        @if ($availableCategories->isNotEmpty())
            <div class="mt-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Add a category</label>
                <select onchange="if (this.value) { @this.call('addSearchCategory', parseInt(this.value)); this.value = ''; }"
                    class="w-full max-w-sm rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    <option value="">Select a category…</option>
                    @foreach ($availableCategories as $cat)
                        <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    @elseif (empty($items))
        <x-empty-state title="No menu items" description="Add the first link for this menu." />
    @else
        <div class="rounded-xl border border-gray-200 divide-y divide-gray-100" id="menu-sortable-root" wire:ignore.self wire:key="menu-root-{{ $activeMenu }}">
            @foreach ($items as $item)
                <div wire:key="item-{{ $item['id'] }}" data-id="{{ $item['id'] }}" class="p-3">
                    <div class="flex items-center gap-2">
                        <span class="menu-drag-handle cursor-grab text-gray-300 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                            </svg>
                        </span>

                        @php $itemImageUrl = ! empty($item['image_id']) ? file_path($item['image_id']) : null; @endphp
                        @if ($itemImageUrl)
                            <img src="{{ $itemImageUrl }}" alt="" class="h-6 w-6 rounded object-cover shrink-0">
                        @elseif (!empty($item['icon']))
                            <span class="text-xs text-gray-400 shrink-0 w-14 truncate">{{ $item['icon'] }}</span>
                        @endif

                        <span class="text-sm text-gray-700 font-medium truncate">{{ $item['label'] }}</span>
                        <span class="text-xs text-gray-400 truncate flex-1">{{ $item['url'] }}</span>

                        @if ($item['new_tab'])
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 shrink-0">new tab</span>
                        @endif

                        <div class="flex items-center gap-1 shrink-0">
                            @if (! in_array($activeMenu, \App\Livewire\Admin\Frontend\Menus::noChildrenMenus(), true))
                                <button type="button" wire:click="addItem('{{ $item['id'] }}')"
                                    class="px-2 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                    + Sub
                                </button>
                            @endif
                            <button type="button" wire:click="editItem('{{ $item['id'] }}')"
                                class="px-2 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                Edit
                            </button>
                            <button type="button" wire:click="removeItem('{{ $item['id'] }}')" wire:confirm="Remove this menu item?"
                                class="px-2 py-1 text-xs font-medium text-red-500 bg-white border border-gray-200 rounded-lg hover:bg-red-50 transition">
                                Remove
                            </button>
                        </div>
                    </div>

                    @if (!empty($item['children']))
                        <div class="mt-2 ml-6 pl-3 border-l border-gray-100 space-y-1.5 menu-sortable-children" data-parent-id="{{ $item['id'] }}" wire:ignore.self>
                            @foreach ($item['children'] as $child)
                                <div wire:key="child-{{ $child['id'] }}" data-id="{{ $child['id'] }}" class="flex items-center gap-2">
                                    <span class="menu-drag-handle-child cursor-grab text-gray-300 shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                        </svg>
                                    </span>
                                    @php $childImageUrl = ! empty($child['image_id']) ? file_path($child['image_id']) : null; @endphp
                                    @if ($childImageUrl)
                                        <img src="{{ $childImageUrl }}" alt="" class="h-6 w-6 rounded object-cover shrink-0">
                                    @elseif (!empty($child['icon']))
                                        <span class="text-xs text-gray-400 shrink-0 w-14 truncate">{{ $child['icon'] }}</span>
                                    @endif
                                    <span class="text-sm text-gray-600 truncate">{{ $child['label'] }}</span>
                                    <span class="text-xs text-gray-400 truncate flex-1">{{ $child['url'] }}</span>
                                    @if ($child['new_tab'])
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 shrink-0">new tab</span>
                                    @endif
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" wire:click="editItem('{{ $child['id'] }}', '{{ $item['id'] }}')"
                                            class="px-2 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="removeItem('{{ $child['id'] }}', '{{ $item['id'] }}')" wire:confirm="Remove this submenu item?"
                                            class="px-2 py-1 text-xs font-medium text-red-500 bg-white border border-gray-200 rounded-lg hover:bg-red-50 transition">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($activeMenu !== 'search-categories')
    <button type="button" wire:click="addItem()"
        class="mt-4 inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add menu item
    </button>
    @endif

    {{-- Item edit/create form. No @click.outside here on purpose: the media
         picker (<x-media-picker-field> below) renders as a separate sibling
         component at the end of <body> (see layouts.admin.admin), so a click
         on it — e.g. its Save button — registers as "outside" this modal and
         would close it before the field even receives the picked image.
         Closing is explicit (Cancel/X) only, matching every other admin
         modal in this codebase (catalog/categories, brands, etc). --}}
    <div x-cloak x-data="{ open: @entangle('showForm') }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-lg w-full max-w-md p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">
                {{ $editingId ? 'Edit menu item' : ($editingParentId ? 'Add submenu item' : 'Add menu item') }}
            </h4>

            <form wire:submit.prevent="saveItem" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Label</label>
                    <input type="text" wire:model="formLabel" maxlength="60"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    @error('formLabel') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">URL</label>
                    <input type="text" wire:model="formUrl" placeholder="/category/example or https://..." maxlength="2048"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    @error('formUrl') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Icon (optional)</label>
                    <select wire:model="formIcon" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                        @foreach (\App\Livewire\Admin\Frontend\Menus::icons() as $icon)
                            <option value="{{ $icon }}">{{ $icon === '' ? 'None' : ucfirst($icon) }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Used only when no image is set below.</p>
                </div>

                <x-media-picker-field field="formImageId" :value="$formImageId" label="Image (optional)" type="image" placeholder="Select image" />

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="formNewTab" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-400">
                    <span class="text-sm text-gray-600">Open in new tab</span>
                </label>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeForm" class="px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('livewire:init', () => {
        // Tracks every Sortable instance created below so it can be torn down
        // before the next init — otherwise a Livewire morph (e.g. the toast
        // dispatched by saveItem(), or any sibling component update) leaves
        // the old instance's listeners attached to DOM nodes that survive the
        // morph, and a single drag can fire onEnd twice with stale id lists.
        // Mirrors the pattern in catalog/categories.blade.php.
        let sortableInstances = [];

        const destroySortables = () => {
            sortableInstances.forEach((s) => s.destroy());
            sortableInstances = [];
        };

        const initSortables = () => {
            destroySortables();

            const root = document.getElementById('menu-sortable-root');
            if (root) {
                sortableInstances.push(new Sortable(root, {
                    handle: '.menu-drag-handle',
                    animation: 150,
                    onEnd: () => {
                        const ids = Array.from(root.children).map(el => el.dataset.id);
                        const component = Livewire.find(root.closest('[wire\\:id]').getAttribute('wire:id'));
                        component.call('reorderItems', ids);
                    },
                }));
            }

            document.querySelectorAll('.menu-sortable-children').forEach((el) => {
                sortableInstances.push(new Sortable(el, {
                    handle: '.menu-drag-handle-child',
                    animation: 150,
                    onEnd: () => {
                        const ids = Array.from(el.children).map(child => child.dataset.id);
                        const parentId = el.dataset.parentId;
                        const component = Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id'));
                        component.call('reorderChildren', parentId, ids);
                    },
                }));
            });

            const searchCatRoot = document.getElementById('search-cat-sortable-root');
            if (searchCatRoot) {
                sortableInstances.push(new Sortable(searchCatRoot, {
                    handle: '.menu-drag-handle',
                    animation: 150,
                    onEnd: () => {
                        const ids = Array.from(searchCatRoot.children).map(el => el.dataset.id);
                        const component = Livewire.find(searchCatRoot.closest('[wire\\:id]').getAttribute('wire:id'));
                        component.call('reorderSearchCategories', ids);
                    },
                }));
            }
        };

        initSortables();
        Livewire.hook('morph.updated', () => initSortables());
    });
    </script>
    @endpush
</div>
