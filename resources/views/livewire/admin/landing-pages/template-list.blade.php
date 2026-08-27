<div x-data x-init="$store.pageName = { name: 'Landing Page Templates', slug: 'landing-page-templates' }">

    <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-xl font-semibold text-gray-800">Templates</h1>
        <button wire:click="rescan" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Rescan Templates
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($templates as $template)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="h-36 bg-gray-100 flex items-center justify-center">
                    @if($template->previewImageUrl())
                        <img src="{{ $template->previewImageUrl() }}" alt="" class="h-full w-full object-cover">
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h12A2.25 2.25 0 0 1 20.25 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h5.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM16.5 13.5a2.25 2.25 0 0 0-2.25 2.25V18a2.25 2.25 0 0 0 2.25 2.25h1.5A2.25 2.25 0 0 0 20.25 18v-2.25a2.25 2.25 0 0 0-2.25-2.25h-1.5Z" />
                        </svg>
                    @endif
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-800">{{ $template->name }}</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium uppercase tracking-wide {{ $template->source === 'system' ? 'bg-indigo-50 text-indigo-600' : 'bg-purple-50 text-purple-600' }}">
                            {{ $template->source }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ $template->category ?? 'Uncategorized' }} &middot; v{{ $template->version }}</p>
                    @if($template->description)
                        <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $template->description }}</p>
                    @endif
                    <div class="mt-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $template->status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">
                            {{ ucfirst($template->status) }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center text-sm text-gray-400">
                No templates registered yet. Click "Rescan Templates" to discover them.
            </div>
        @endforelse
    </div>
</div>
