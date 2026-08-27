<div x-data x-init="$store.pageName = { name: 'Landing Pages', slug: 'landing-pages' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-3 gap-3 flex-1 max-w-xl">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Pages</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Published</p>
                <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ $publishedCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Draft</p>
                <p class="text-xl font-semibold text-amber-500 mt-0.5">{{ $draftCount }}</p>
            </div>
        </div>
        <a href="{{ route('admin.landingpages.pages.create') }}" wire:navigate
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Landing Page
        </a>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, title, or slug…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="unpublished">Unpublished</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Template</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Slug</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pages as $page)
                        @php
                            $statusStyles = [
                                'draft' => 'bg-amber-50 text-amber-600',
                                'published' => 'bg-emerald-50 text-emerald-600',
                                'unpublished' => 'bg-gray-100 text-gray-500',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.landingpages.pages.edit', $page->id) }}" wire:navigate class="text-sm font-medium text-gray-800 hover:text-indigo-600">
                                    {{ $page->name }}
                                </a>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $page->title }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $page->template?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm text-gray-500 font-mono">/lp/{{ $page->slug }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusStyles[$page->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ ucfirst($page->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($page->status === 'published')
                                        <a href="{{ route('landingpage.show', $page->slug) }}" target="_blank"
                                            class="text-xs font-medium text-gray-500 hover:text-indigo-600">View</a>
                                    @else
                                        <a href="{{ route('admin.landingpages.pages.preview', $page->id) }}" target="_blank"
                                            class="text-xs font-medium text-gray-500 hover:text-indigo-600">Preview</a>
                                    @endif
                                    <a href="{{ route('admin.landingpages.pages.edit', $page->id) }}" wire:navigate
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                    <button wire:click="duplicate({{ $page->id }})" class="text-xs font-medium text-gray-500 hover:text-gray-700">Duplicate</button>
                                    @if($page->status === 'published')
                                        <button wire:click="unpublish({{ $page->id }})" class="text-xs font-medium text-amber-600 hover:text-amber-800">Unpublish</button>
                                    @else
                                        <button wire:click="publish({{ $page->id }})" class="text-xs font-medium text-emerald-600 hover:text-emerald-800">Publish</button>
                                    @endif
                                    <button wire:click="deletePage({{ $page->id }})" wire:confirm="Delete this landing page?"
                                        class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">No landing pages yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-gray-100">
            {{ $pages->links() }}
        </div>
    </div>
</div>
