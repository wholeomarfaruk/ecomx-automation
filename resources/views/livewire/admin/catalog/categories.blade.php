<div x-data x-init="$store.pageName = { name: 'Categories', slug: 'catalog-categories' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-3 gap-3 flex-1 max-w-lg">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Categories</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Active</p>
                <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ $activeCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Inactive</p>
                <p class="text-xl font-semibold text-gray-500 mt-0.5">{{ $inactiveCount }}</p>
            </div>
        </div>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Category
        </button>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name or slug…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            @if(!$autoExpand)
                <p class="text-xs text-gray-400 hidden md:block">Drag <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 inline -mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/></svg> to reorder or move between categories</p>
            @endif
        </div>

        {{-- Tree --}}
        <div class="px-2 py-2" id="category-tree-root">
            @if($tree->isEmpty())
                <div class="px-5 py-16 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">No categories found</p>
                            <p class="text-xs text-gray-400 mt-0.5">Try adjusting filters or add a new category</p>
                        </div>
                        <button wire:click="openCreateModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                            Add First Category
                        </button>
                    </div>
                </div>
            @else
                <ul class="category-tree" data-parent-id="">
                    @foreach($tree as $category)
                        @include('livewire.admin.catalog.partials.category-row', ['category' => $category, 'autoExpand' => $autoExpand, 'collapsed' => $collapsed])
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Create Modal --}}
    <div x-cloak x-data="{ open: @entangle('createModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Add Category</h2>
                    <p class="text-xs text-gray-400">Create a new product category</p>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="createCategory" class="overflow-y-auto px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input wire:model.live="newName" type="text" placeholder="e.g. Electronics"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            Slug <span class="text-gray-400 font-normal ml-1">(auto-generated, editable)</span>
                        </label>
                        <input wire:model="newSlug" type="text" placeholder="electronics"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newSlug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Parent Category</label>
                        <select wire:model="newParentId"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— None (top level) —</option>
                            @foreach($parentOptions as $parent)
                                <option value="{{ $parent->id }}">{{ str_repeat('— ', $parent->depth) }}{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        @error('newParentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="newStatus"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                        <textarea wire:model="newDescription" rows="2" placeholder="Short description…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-media-picker-field field="newFeaturedImageId" :value="$newFeaturedImageId" label="Featured Image" type="image" placeholder="Select image" />
                    <x-media-picker-field field="newCoverImageId" :value="$newCoverImageId" label="Cover Image" type="image" placeholder="Select image" />
                </div>

                {{-- SEO (collapsible) --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden" x-data="{ seo: @entangle('seoOpen') }">
                    <button type="button" @click="seo = !seo"
                        class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left">
                        <span class="text-sm font-medium text-gray-700">SEO Settings</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': seo }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="seo" x-cloak x-transition class="p-4 space-y-4">
                        <x-media-picker-field field="newMetaImageId" :value="$newMetaImageId" label="Meta Image" type="image" placeholder="Select image" />
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Title</label>
                            <input wire:model="newMetaTitle" type="text" placeholder="SEO title"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Description</label>
                            <textarea wire:model="newMetaDescription" rows="2" placeholder="SEO description"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-cloak x-data="{ open: @entangle('editModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Edit Category</h2>
                    <p class="text-xs text-gray-400">Update category details</p>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="updateCategory" class="overflow-y-auto px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input wire:model="editName" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Slug</label>
                        <input wire:model="editSlug" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editSlug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Parent Category</label>
                        <select wire:model="editParentId"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— None (top level) —</option>
                            @foreach($parentOptions as $parent)
                                @if($parent->id !== $editingId)
                                    <option value="{{ $parent->id }}">{{ str_repeat('— ', $parent->depth) }}{{ $parent->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('editParentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="editStatus"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                        <textarea wire:model="editDescription" rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-media-picker-field field="editFeaturedImageId" :value="$editFeaturedImageId" label="Featured Image" type="image" placeholder="Select image" />
                    <x-media-picker-field field="editCoverImageId" :value="$editCoverImageId" label="Cover Image" type="image" placeholder="Select image" />
                </div>

                {{-- SEO (collapsible) --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden" x-data="{ seo: @entangle('seoOpen') }">
                    <button type="button" @click="seo = !seo"
                        class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left">
                        <span class="text-sm font-medium text-gray-700">SEO Settings</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': seo }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="seo" x-cloak x-transition class="p-4 space-y-4">
                        <x-media-picker-field field="editMetaImageId" :value="$editMetaImageId" label="Meta Image" type="image" placeholder="Select image" />
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Title</label>
                            <input wire:model="editMetaTitle" type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Description</label>
                            <textarea wire:model="editMetaDescription" rows="2"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Manage Products Modal --}}
    <div x-cloak x-data="{ open: @entangle('productsModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-gray-900">Manage Products</h2>
                    <p class="text-xs text-gray-400 truncate">{{ $productsCategory?->name }}</p>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-5 space-y-6">

                {{-- Add product --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Add a product</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                        <input wire:model.live.debounce.300ms="productSearch" type="text" placeholder="Search products by name or code…"
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>

                    @if($productSearch !== '')
                        <div class="mt-2 border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-48 overflow-y-auto">
                            @forelse($availableProducts as $product)
                                <button wire:click="attachProduct({{ $product->id }})" type="button"
                                    class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 transition text-left">
                                    <div class="h-7 w-7 rounded bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if($product->featured_image_id)
                                            <img src="{{ file_path($product->featured_image_id) }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-800 truncate">{{ $product->name }}</p>
                                        <p class="text-xs font-mono text-gray-400">{{ $product->code }}</p>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                </button>
                            @empty
                                <p class="px-3 py-3 text-xs text-gray-400">No matching products found.</p>
                            @endforelse
                        </div>
                    @endif
                </div>

                {{-- Assigned products --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-medium text-gray-600">Products in this category</label>
                        <span class="text-xs text-gray-400">{{ $assignedProducts->count() }} {{ Str::plural('product', $assignedProducts->count()) }}</span>
                    </div>

                    @if($assignedProducts->isEmpty())
                        <div class="border border-dashed border-gray-200 rounded-lg px-4 py-8 text-center">
                            <p class="text-sm text-gray-400">No products assigned yet. Search above to add some.</p>
                        </div>
                    @else
                        <ul id="category-product-list" class="border border-gray-200 rounded-lg divide-y divide-gray-100">
                            @foreach($assignedProducts as $product)
                                <li data-id="{{ $product->id }}" class="flex items-center gap-3 px-3 py-2.5 bg-white">
                                    <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/>
                                        </svg>
                                    </span>
                                    <div class="h-7 w-7 rounded bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if($product->featured_image_id)
                                            <img src="{{ file_path($product->featured_image_id) }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-800 truncate">{{ $product->name }}</p>
                                        <p class="text-xs font-mono text-gray-400">{{ $product->code }}</p>
                                    </div>
                                    <button wire:click="detachProduct({{ $product->id }})" type="button"
                                        class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0">
                <button @click="open = false" type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                    Done
                </button>
            </div>
        </div>
    </div>

    @script
    <script>
        let sortableInstances = [];

        function destroySortables() {
            sortableInstances.forEach(s => s.destroy());
            sortableInstances = [];
        }

        function initSortables() {
            destroySortables();
            document.querySelectorAll('#category-tree-root .category-tree').forEach((el) => {
                sortableInstances.push(new window.Sortable(el, {
                    group: 'categories',
                    handle: '.drag-handle',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onEnd(evt) {
                        const parentUl = evt.to;
                        const parentId = parentUl.dataset.parentId || null;
                        const orderedIds = Array.from(parentUl.children)
                            .map(li => parseInt(li.dataset.id, 10))
                            .filter(Boolean);

                        $wire.reorder(orderedIds, parentId ? parseInt(parentId, 10) : null);
                    },
                }));
            });
        }

        let productSortable = null;

        function initProductSortable() {
            if (productSortable) productSortable.destroy();
            const el = document.getElementById('category-product-list');
            if (!el) return;
            productSortable = new window.Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd() {
                    const orderedIds = Array.from(el.children)
                        .map(li => parseInt(li.dataset.id, 10))
                        .filter(Boolean);
                    $wire.reorderCategoryProducts(orderedIds);
                },
            });
        }

        initSortables();
        initProductSortable();
        Livewire.hook('morph.updated', ({ el }) => {
            if (el.id === 'category-tree-root' || el.closest?.('#category-tree-root')) {
                initSortables();
            }
            if (el.id === 'category-product-list' || el.closest?.('#category-product-list')) {
                initProductSortable();
            }
        });
    </script>
    @endscript

</div>
