<div x-data x-init="$store.pageName = { name: 'Brands', slug: 'catalog-brands' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-4 gap-3 flex-1 max-w-2xl">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Brands</p>
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
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Featured</p>
                <p class="text-xl font-semibold text-amber-500 mt-0.5">{{ $featuredCount }}</p>
            </div>
        </div>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Brand
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
            <select wire:model.live="filterFeatured"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Brands</option>
                <option value="1">Featured Only</option>
            </select>
        </div>

        {{-- List --}}
        <div class="px-2 py-2">
            @if($brands->isEmpty())
                <div class="px-5 py-16 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">No brands found</p>
                            <p class="text-xs text-gray-400 mt-0.5">Try adjusting filters or add a new brand</p>
                        </div>
                        <button wire:click="openCreateModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                            Add First Brand
                        </button>
                    </div>
                </div>
            @else
                <ul id="brand-list" class="divide-y divide-gray-50">
                    @foreach($brands as $brand)
                        <li class="brand-node" data-id="{{ $brand->id }}">
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 group">
                                <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 shrink-0" title="Drag to reorder">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/>
                                    </svg>
                                </span>

                                <div class="h-9 w-9 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                    @if($brand->logo_image_id)
                                        <img src="{{ file_path($brand->logo_image_id) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                        </svg>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1 flex items-baseline gap-2">
                                    <span class="text-sm font-medium text-gray-800 truncate">{{ $brand->name }}</span>
                                    <span class="text-xs font-mono text-gray-400 truncate hidden sm:inline">{{ $brand->slug }}</span>
                                </div>

                                {{-- Featured star --}}
                                <button wire:click="toggleFeatured({{ $brand->id }})" type="button"
                                    title="{{ $brand->featured ? 'Remove from featured' : 'Mark as featured' }}"
                                    class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg transition {{ $brand->featured ? 'text-amber-400 hover:bg-amber-50' : 'text-gray-300 hover:bg-gray-100 hover:text-gray-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="{{ $brand->featured ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.448a1 1 0 00-.364 1.118l1.287 3.959c.299.921-.755 1.688-1.538 1.118l-3.367-2.448a1 1 0 00-1.176 0l-3.367 2.448c-.783.57-1.837-.197-1.538-1.118l1.287-3.96a1 1 0 00-.364-1.117L2.062 9.386c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.689l1.286-3.958z"/>
                                    </svg>
                                </button>

                                {{-- Status toggle --}}
                                <button wire:click="toggleStatus({{ $brand->id }})" type="button"
                                    title="{{ $brand->status === 'active' ? 'Click to deactivate' : 'Click to activate' }}"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 shrink-0
                                        {{ $brand->status === 'active' ? 'bg-indigo-500 focus:ring-indigo-400' : 'bg-gray-300 focus:ring-gray-400' }}">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                        {{ $brand->status === 'active' ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                                </button>

                                <div class="flex items-center gap-1 shrink-0">
                                    <button wire:click="editBrand({{ $brand->id }})" type="button"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                    </button>
                                    <button type="button" x-data
                                        @click="Swal.fire({
                                            title: 'Delete brand?',
                                            text: '{{ addslashes($brand->name) }} will be removed permanently.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#ef4444',
                                            confirmButtonText: 'Delete'
                                        }).then(r => { if (r.isConfirmed) $wire.deleteBrand({{ $brand->id }}) })"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </li>
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
                    <h2 class="text-base font-semibold text-gray-900">Add Brand</h2>
                    <p class="text-xs text-gray-400">Create a new product brand</p>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="createBrand" class="overflow-y-auto px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input wire:model.live="newName" type="text" placeholder="e.g. Nike"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            Slug <span class="text-gray-400 font-normal ml-1">(auto-generated, editable)</span>
                        </label>
                        <input wire:model="newSlug" type="text" placeholder="nike"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newSlug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="newStatus"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input wire:model="newFeatured" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Featured brand</span>
                        </label>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                        <textarea wire:model="newDescription" rows="2" placeholder="Short description…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-media-picker-field field="newLogoImageId" :value="$newLogoImageId" label="Logo Image" type="image" placeholder="Select image" />
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
                        Add Brand
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
                    <h2 class="text-base font-semibold text-gray-900">Edit Brand</h2>
                    <p class="text-xs text-gray-400">Update brand details</p>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="updateBrand" class="overflow-y-auto px-6 py-5 space-y-4">
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
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="editStatus"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input wire:model="editFeatured" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Featured brand</span>
                        </label>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                        <textarea wire:model="editDescription" rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-media-picker-field field="editLogoImageId" :value="$editLogoImageId" label="Logo Image" type="image" placeholder="Select image" />
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

    @script
    <script>
        let brandSortable = null;

        function initBrandSortable() {
            if (brandSortable) brandSortable.destroy();
            const el = document.getElementById('brand-list');
            if (!el) return;
            brandSortable = new window.Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd(evt) {
                    const orderedIds = Array.from(el.children)
                        .map(li => parseInt(li.dataset.id, 10))
                        .filter(Boolean);
                    $wire.reorder(orderedIds);
                },
            });
        }

        initBrandSortable();
        Livewire.hook('morph.updated', ({ el }) => {
            if (el.id === 'brand-list' || el.closest?.('#brand-list')) {
                initBrandSortable();
            }
        });
    </script>
    @endscript

</div>
