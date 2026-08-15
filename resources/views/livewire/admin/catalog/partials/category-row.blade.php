@props(['category', 'autoExpand' => false, 'collapsed' => []])

@php
    $isOpen = $autoExpand || ! ($collapsed[$category->id] ?? false);
@endphp

<li class="category-node" data-id="{{ $category->id }}">
    <div class="flex items-center gap-2 px-3 py-2.5 rounded-lg hover:bg-gray-50 group border border-transparent hover:border-gray-100">

        {{-- Drag handle --}}
        <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 shrink-0" title="Drag to reorder">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/>
            </svg>
        </span>

        {{-- Expand / collapse --}}
        @if($category->children_count > 0)
            <button type="button" wire:click="toggleCollapse({{ $category->id }})"
                class="shrink-0 w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform {{ $isOpen ? 'rotate-90' : '' }}" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        @else
            <span class="shrink-0 w-5 h-5"></span>
        @endif

        {{-- Thumbnail --}}
        <div class="h-8 w-8 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
            @if($category->featured_image_id)
                <img src="{{ file_path($category->featured_image_id) }}" alt="" class="h-full w-full object-cover">
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                </svg>
            @endif
        </div>

        {{-- Name + slug --}}
        <div class="min-w-0 flex-1 flex items-baseline gap-2">
            <span class="text-sm font-medium text-gray-800 truncate">{{ $category->name }}</span>
            <span class="text-xs font-mono text-gray-400 truncate hidden sm:inline">{{ $category->slug }}</span>
            @if($category->children_count > 0)
                <span class="text-[11px] text-gray-400 shrink-0">({{ $category->children_count }})</span>
            @endif
        </div>

        {{-- Status toggle --}}
        <button wire:click="toggleStatus({{ $category->id }})" type="button"
            title="{{ $category->status === 'active' ? 'Click to deactivate' : 'Click to activate' }}"
            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 shrink-0
                {{ $category->status === 'active' ? 'bg-indigo-500 focus:ring-indigo-400' : 'bg-gray-300 focus:ring-gray-400' }}">
            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                {{ $category->status === 'active' ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
        </button>

        {{-- Actions --}}
        <div class="flex items-center gap-1 shrink-0">
            <button wire:click="openProductsModal({{ $category->id }})" type="button"
                title="Manage Products"
                class="inline-flex items-center gap-1.5 px-2.5 h-8 rounded-lg text-xs font-medium text-gray-500 border border-gray-200 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                </svg>
                <span class="hidden md:inline">Manage Products</span>
                @if($category->products_count > 0)
                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-600">
                        {{ $category->products_count }}
                    </span>
                @endif
            </button>
            <button wire:click="editCategory({{ $category->id }})" type="button"
                class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                </svg>
            </button>
            <button type="button" x-data
                @click="Swal.fire({
                    title: 'Delete category?',
                    text: '{{ addslashes($category->name) }} will be removed permanently.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Delete'
                }).then(r => { if (r.isConfirmed) $wire.deleteCategory({{ $category->id }}) })"
                class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
            </button>
        </div>
    </div>

    @if($category->children_count > 0)
        <ul class="category-tree pl-7 {{ $isOpen ? '' : 'hidden' }}" data-parent-id="{{ $category->id }}">
            @foreach($category->nodes as $child)
                @include('livewire.admin.catalog.partials.category-row', ['category' => $child, 'autoExpand' => $autoExpand, 'collapsed' => $collapsed])
            @endforeach
        </ul>
    @endif
</li>
