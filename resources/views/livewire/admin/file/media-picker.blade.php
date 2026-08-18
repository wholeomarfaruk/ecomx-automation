@push('styles')
<style>
    .filepond--root { max-width: 100%; }
    .filepond--drop-label { color: #6b7280; }
</style>
@endpush

<div>
    <div
        x-cloak
        x-data="mediaPickerInit()"
        x-show="$wire.mediapickerModal"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    >
        <div
            x-show="$wire.mediapickerModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-5xl max-h-[90vh] rounded-xl bg-white shadow-2xl flex flex-col overflow-hidden"
        >
            {{-- ── Header ── --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-900 truncate">Media Library</h2>
                    @if(count($this->selected) > 0)
                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800 shrink-0">
                            {{ count($this->selected) }} selected
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    {{-- Type filter --}}
                    <select wire:model.live="filterType" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        <option value="image">Images</option>
                        <option value="video">Videos</option>
                        <option value="document">Documents</option>
                    </select>
                    <button wire:click="close" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- ── Tabs ── --}}
            <div class="flex border-b border-gray-200 shrink-0 px-6">
                <button
                    @click="tab = 'library'"
                    :class="tab === 'library' ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-3 text-sm transition"
                >
                    Library
                </button>
                <button
                    @click="tab = 'upload'"
                    :class="tab === 'upload' ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-3 text-sm transition"
                >
                    Upload
                </button>
            </div>

            {{-- ── Tab Content ── --}}
            <div class="flex-1 overflow-hidden flex flex-col">

                {{-- Library Tab --}}
                <div x-show="tab === 'library'" class="flex-1 flex flex-col overflow-hidden">

                    {{-- Search + select all toolbar --}}
                    <div class="flex items-center gap-3 px-6 py-3 border-b border-gray-100 shrink-0">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                wire:model.live.debounce.400ms="search"
                                placeholder="Search files..."
                                class="w-full rounded-lg border border-gray-300 pl-9 pr-4 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <button
                            wire:click="selectAll({{ json_encode($currentPageIds) }})"
                            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap"
                        >
                            Select all
                        </button>
                        <button
                            wire:click="deselectAll"
                            class="text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap"
                        >
                            Clear
                        </button>
                    </div>

                    {{-- File Grid --}}
                    <div class="flex-1 overflow-y-auto p-6">
                        @if($files->isEmpty())
                            <div class="flex flex-col items-center justify-center h-full text-center py-12">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                </svg>
                                <p class="text-gray-500 font-medium">No files found</p>
                                <p class="text-gray-400 text-sm mt-1">
                                    @if($search)
                                        No results for "{{ $search }}"
                                    @else
                                        Upload your first file to get started
                                    @endif
                                </p>
                                <button @click="tab = 'upload'" class="mt-4 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                    Go to Upload →
                                </button>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 md:grid-cols-5">
                                @foreach($files as $file)
                                    @php $isSelected = in_array($file->id, $selected); @endphp
                                    <div
                                        wire:click="selectImage({{ $file->id }})"
                                        class="group relative rounded-xl border-2 cursor-pointer overflow-hidden transition-all
                                            {{ $isSelected ? 'border-indigo-500 ring-2 ring-indigo-500 ring-offset-1' : 'border-gray-200 hover:border-indigo-300' }}"
                                    >
                                        {{-- Checkbox --}}
                                        <div class="absolute top-2 left-2 z-10">
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition
                                                {{ $isSelected ? 'bg-indigo-600 border-indigo-600' : 'bg-white/80 border-gray-400 opacity-70 group-hover:opacity-100' }}"
                                            >
                                                @if($isSelected)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Action buttons (dimmed by default, full opacity on hover/touch) --}}
                                        <div class="absolute top-2 right-2 z-10 flex gap-1 opacity-70 group-hover:opacity-100 transition">
                                            {{-- Download --}}
                                            <a
                                                href="{{ file_path($file->id) }}"
                                                download="{{ $file->name }}"
                                                target="_blank"
                                                onclick="event.stopPropagation()"
                                                class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center hover:bg-emerald-600 shadow"
                                                title="Download"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                            </a>
                                            {{-- Delete --}}
                                            <button
                                                wire:click.stop="delete({{ $file->id }})"
                                                onclick="event.stopPropagation(); if(!confirm('Delete this file permanently?')) event.preventDefault();"
                                                class="w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 shadow"
                                                title="Delete"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- Type badge --}}
                                        <div class="absolute bottom-7 left-1.5 z-10">
                                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded
                                                {{ match($file->type) {
                                                    'video' => 'bg-purple-100 text-purple-700',
                                                    'document' => 'bg-amber-100 text-amber-700',
                                                    default => 'bg-blue-100 text-blue-700',
                                                } }}">
                                                {{ $file->type ?? 'file' }}
                                            </span>
                                        </div>

                                        {{-- Thumbnail --}}
                                        <div class="{{ $isSelected ? 'bg-indigo-50' : 'bg-gray-50' }} aspect-square flex items-center justify-center overflow-hidden">
                                            @if($file->type === 'video')
                                                <div class="relative w-full h-full flex items-center justify-center">
                                                    <video class="w-full h-full object-cover" preload="metadata">
                                                        <source src="{{ file_path($file->id) }}">
                                                    </video>
                                                    <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white drop-shadow" viewBox="0 0 24 24" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            @elseif($file->type === 'document')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                            @else
                                                <img
                                                    src="{{ file_path($file->id) }}"
                                                    alt="{{ $file->name }}"
                                                    class="w-full h-full object-cover"
                                                    loading="lazy"
                                                >
                                            @endif
                                        </div>

                                        {{-- Filename --}}
                                        <div class="px-2 py-1.5 bg-white border-t border-gray-100">
                                            <p class="text-xs text-gray-600 truncate" title="{{ $file->name }}">{{ $file->name }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Pagination --}}
                            <div class="mt-6">
                                {{ $files->links() }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Upload Tab --}}
                <div x-show="tab === 'upload'" class="flex-1 flex flex-col p-6">
                    <div wire:ignore class="flex-1 border-2 border-dashed border-gray-300 rounded-xl p-4 hover:border-indigo-400 transition">
                        <input type="file" class="filepond-picker" multiple>
                    </div>
                </div>

            </div>

            {{-- ── Footer (Library tab only) ── --}}
            <div x-show="tab === 'library'" class="border-t border-gray-200 px-6 py-4 shrink-0">
                <div class="flex items-center justify-between gap-4">
                    {{-- Selected previews --}}
                    <div class="flex items-center gap-2 flex-1 overflow-x-auto py-1">
                        @forelse($selected as $selId)
                            <div class="relative shrink-0">
                                @if($type === 'video')
                                    <div class="h-12 w-12 rounded-lg border border-gray-300 bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                    </div>
                                @elseif($type === 'document')
                                    <div class="h-12 w-12 rounded-lg border border-gray-300 bg-amber-50 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                @else
                                    <img src="{{ file_path($selId) }}" alt="" class="h-12 w-12 rounded-lg border border-gray-300 object-cover">
                                @endif
                                <button
                                    wire:click="removeSelect({{ $selId }})"
                                    class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 italic">No files selected</p>
                        @endforelse
                    </div>

                    {{-- Action buttons --}}
                    <div class="flex items-center gap-3 shrink-0">
                        <button
                            wire:click="close"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="save"
                            class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                            @if(empty($selected)) disabled @endif
                        >
                            @if(count($selected) > 0)
                                Save ({{ count($selected) }})
                            @else
                                Save
                            @endif
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    function mediaPickerInit() {
        return {
            tab: 'library',
            pondInstance: null,

            init() {
                this.$watch('tab', (val) => {
                    if (val === 'upload') {
                        this.$nextTick(() => this.initFilePond());
                    }
                });

                Livewire.on('fileUploaded', () => {
                    this.tab = 'library';
                });
            },

            initFilePond() {
                if (this.pondInstance) return;

                const input = this.$el.querySelector('.filepond-picker');
                if (!input) return;

                this.pondInstance = FilePond.create(input, {
                    allowMultiple: true,
                    allowReorder: true,
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
                        if (!error) {
                            Livewire.dispatch('fileUploaded');
                        }
                    },
                });
            },
        };
    }
</script>
@endpush
