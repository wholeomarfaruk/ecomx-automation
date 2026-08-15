<div x-data="uploadsPage()" x-init="$store.pageName = { name: 'Uploads', slug: 'uploads' }">

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 min-h-[80vh] flex flex-col">

        {{-- ── Toolbar ── --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-200">

            {{-- Search --}}
            <div class="relative w-full sm:flex-1 sm:min-w-48">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search by name..."
                    class="w-full rounded-lg border border-gray-300 pl-9 pr-4 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                >
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            {{-- Type filter --}}
            <div class="flex items-center gap-2 text-sm">
                <label class="flex items-center gap-1.5 cursor-pointer text-gray-600 hover:text-gray-800">
                    <input type="radio" wire:model.live="filterType" value="" class="accent-indigo-600"> All
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer text-gray-600 hover:text-gray-800">
                    <input type="radio" wire:model.live="filterType" value="image" class="accent-indigo-600"> Images
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer text-gray-600 hover:text-gray-800">
                    <input type="radio" wire:model.live="filterType" value="video" class="accent-indigo-600"> Videos
                </label>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto">
                {{-- Bulk delete --}}
                @if(count($selected) > 0)
                    <button
                        x-on:click="confirmBulkDelete()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Delete {{ count($selected) }}
                    </button>
                @endif

                {{-- Upload button --}}
                <button
                    wire:click="$toggle('showUploadPanel')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                    </svg>
                    Upload Files
                </button>
            </div>
        </div>

        {{-- ── Upload Panel (collapsible) ── --}}
        <div x-show="$wire.showUploadPanel" x-transition class="border-b border-gray-200 px-5 py-4 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-700">Upload New Files</h3>
                <button wire:click="$set('showUploadPanel', false)" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div wire:ignore>
                <input type="file" class="filepond-uploads" multiple>
            </div>
        </div>

        {{-- ── Bulk action bar ── --}}
        @if(count($selected) > 0)
            <div class="flex items-center gap-4 px-5 py-2.5 bg-indigo-50 border-b border-indigo-100 text-sm">
                <span class="font-medium text-indigo-800">{{ count($selected) }} file(s) selected</span>
                <button wire:click="selectAll" class="text-indigo-600 hover:text-indigo-800 font-medium">Select all</button>
                <button wire:click="deselectAll" class="text-gray-500 hover:text-gray-700">Deselect all</button>
            </div>
        @endif

        {{-- ── File Grid ── --}}
        <div class="flex-1 p-5">
            @if($files->isEmpty())
                {{-- Empty state --}}
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700">
                        {{ $search || $filterType ? 'No files match your filters' : 'No files uploaded yet' }}
                    </h3>
                    <p class="text-gray-400 text-sm mt-1">
                        {{ $search || $filterType ? 'Try adjusting your search or filter.' : 'Upload your first file to get started.' }}
                    </p>
                    @if(!$search && !$filterType)
                        <button
                            wire:click="$set('showUploadPanel', true)"
                            class="mt-4 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition"
                        >
                            Upload Files
                        </button>
                    @else
                        <button wire:click="$set('filterType', ''); $set('search', '')" class="mt-4 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            Clear filters
                        </button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach($files as $file)
                        @php
                            $isSelected = in_array($file->id, $selected);
                            $fileUrl    = file_path($file->id);
                        @endphp

                        <div class="group relative rounded-xl border-2 overflow-hidden transition-all
                            {{ $isSelected ? 'border-indigo-500 ring-2 ring-indigo-500 ring-offset-1' : 'border-gray-200 hover:border-gray-300 hover:shadow-md' }}">

                            {{-- Checkbox --}}
                            <div class="absolute top-2 left-2 z-10">
                                <input
                                    type="checkbox"
                                    wire:click="toggleSelect({{ $file->id }})"
                                    @checked($isSelected)
                                    class="w-4 h-4 rounded accent-indigo-600 cursor-pointer
                                           {{ $isSelected ? 'opacity-100' : 'opacity-70 group-hover:opacity-100 group-focus-within:opacity-100' }} transition"
                                >
                            </div>

                            {{-- Action buttons (dimmed by default, full opacity on hover/focus/touch) --}}
                            <div class="absolute top-2 right-2 z-10 flex gap-1 opacity-70 group-hover:opacity-100 group-focus-within:opacity-100 transition">
                                {{-- Preview --}}
                                <a data-fancybox
                                   href="{{ $fileUrl }}"
                                   @if($file->type === 'video') data-type="video" @endif
                                   class="w-6 h-6 rounded bg-sky-500 text-white flex items-center justify-center hover:bg-sky-600 shadow">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </a>

                                {{-- Download --}}
                                <a href="{{ $fileUrl }}" download="{{ $file->name }}"
                                   onclick="event.stopPropagation()"
                                   class="w-6 h-6 rounded bg-violet-500 text-white flex items-center justify-center hover:bg-violet-600 shadow">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                </a>

                                {{-- Copy URL --}}
                                <button
                                    type="button"
                                    onclick="navigator.clipboard.writeText('{{ $fileUrl }}').then(() => { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'URL copied!', showConfirmButton: false, timer: 2000, timerProgressBar: true }); }); event.stopPropagation();"
                                    class="w-6 h-6 rounded bg-emerald-500 text-white flex items-center justify-center hover:bg-emerald-600 shadow"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                    </svg>
                                </button>

                                {{-- Delete --}}
                                <button
                                    type="button"
                                    x-on:click.stop="confirmDelete({{ $file->id }})"
                                    class="w-6 h-6 rounded bg-red-500 text-white flex items-center justify-center hover:bg-red-600 shadow"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Media preview --}}
                            <div class="aspect-square bg-gray-100 overflow-hidden cursor-pointer"
                                 wire:click="toggleSelect({{ $file->id }})">
                                @if($file->type === 'video')
                                    <div class="relative w-full h-full flex items-center justify-center">
                                        <video class="w-full h-full object-cover" preload="metadata">
                                            <source src="{{ $fileUrl }}">
                                        </video>
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/20 pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white drop-shadow-lg" viewBox="0 0 24 24" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                @else
                                    <img
                                        src="{{ $fileUrl }}"
                                        alt="{{ $file->name }}"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    >
                                @endif
                            </div>

                            {{-- File info footer --}}
                            <div class="px-2 py-2 bg-white border-t border-gray-100">
                                <p class="text-xs font-medium text-gray-700 truncate" title="{{ $file->name }}">
                                    {{ $file->name }}
                                </p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium
                                        {{ $file->type === 'video' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ strtoupper($file->extension ?? $file->type ?? 'file') }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">
                                        {{ local_time($file->created_at)->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $files->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script>
    function uploadsPage() {
        return {
            pond: null,

            init() {
                this.$watch('$wire.showUploadPanel', (val) => {
                    if (val) this.$nextTick(() => this.initPond());
                    else if (this.pond) {
                        this.pond.destroy();
                        this.pond = null;
                    }
                });
            },

            initPond() {
                if (this.pond) return;
                const input = document.querySelector('.filepond-uploads');
                if (!input) return;
                this.pond = FilePond.create(input, {
                    allowMultiple: true,
                    imagePreviewMaxHeight: 150,
                    server: {
                        process: {
                            url: '/admin/upload',
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        },
                        revert: {
                            url: '/admin/upload/revert',
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        },
                    },
                    onprocessfile: (error) => {
                        if (!error) Livewire.dispatch('fileUploaded');
                    },
                });
            },

            confirmDelete(id) {
                Swal.fire({
                    title: 'Delete this file?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete',
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.$wire.delete(id);
                    }
                });
            },

            confirmBulkDelete() {
                const count = this.$wire.selected.length;
                Swal.fire({
                    title: `Delete ${count} file(s)?`,
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete all',
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.$wire.deleteSelected();
                    }
                });
            },
        };
    }

    addEventListener('DOMContentLoaded', () => {
        Fancybox.bind('[data-fancybox]', {});
    });
</script>
@endpush
