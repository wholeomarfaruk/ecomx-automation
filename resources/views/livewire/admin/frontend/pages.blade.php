<div x-data x-init="$store.pageName = { name: 'Frontend', slug: 'frontend' }">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <p class="text-sm text-gray-500">
                All registered pages for the active theme. Click a page to manage its sections.
            </p>
            <button type="button" wire:click="syncAllPages"
                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition whitespace-nowrap"
                title="Register every page's sections now, instead of only after visiting each one">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Sync all pages
            </button>
        </div>

        @if ($needsSync)
            <div class="flex items-center justify-between gap-4 mb-6 p-4 rounded-lg border border-amber-200 bg-amber-50">
                <p class="text-sm text-amber-800">
                    Page sections and SEO haven't been generated for this theme yet. The site is using the theme's default sections until you generate them.
                </p>
                <button type="button" wire:click="syncAllPages"
                    class="shrink-0 px-3 py-2 text-xs font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition whitespace-nowrap">
                    Generate now
                </button>
            </div>
        @endif

        @if (empty($pages))
            <x-empty-state title="No pages registered" description="The active theme has no pages configured." />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($pages as $key => $meta)
                    <a href="{{ route('admin.frontend.menu.show', $key) }}"
                        class="flex items-center gap-3 p-4 bg-white border border-gray-200 rounded-xl hover:border-indigo-300 hover:shadow-sm transition">
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-800 truncate">{{ $meta['label'] ?? ucfirst($key) }}</div>
                            <div class="text-xs text-gray-400">
                                @if (count($meta['sections'] ?? []) > 0)
                                    {{ count($meta['sections']) }} {{ Str::plural('section', count($meta['sections'])) }}
                                @else
                                    No sections yet
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
