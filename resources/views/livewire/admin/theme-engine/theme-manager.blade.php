<div>
    <p class="text-sm text-gray-500 mb-6">
        Installed theme packages. Activating a theme switches the entire storefront (pages, sections, layouts) to it.
    </p>

    @if (empty($themes))
        <x-empty-state title="No themes installed" description="No theme packages found under resources/*/theme.json." />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($themes as $slug => $meta)
                <div class="bg-white rounded-xl border {{ $active === $slug ? 'border-indigo-400 ring-1 ring-indigo-100' : 'border-gray-200' }} overflow-hidden flex flex-col">
                    @if ($meta['cover'])
                        <img src="{{ $meta['cover'] }}" alt="{{ $meta['name'] }}" class="h-36 w-full object-cover">
                    @endif

                    <div class="p-4 flex flex-col flex-1">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <div class="font-semibold text-gray-800">{{ $meta['name'] }}</div>
                            @if ($active === $slug)
                                <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600">Active</span>
                            @endif
                        </div>

                        @if ($meta['description'])
                            <p class="text-xs text-gray-400 mb-2 line-clamp-2">{{ $meta['description'] }}</p>
                        @endif

                        @if ($meta['version'])
                            <p class="text-xs text-gray-400 mb-3">v{{ $meta['version'] }}</p>
                        @endif

                        <div class="mt-auto flex gap-2 pt-2">
                            <button type="button"
                                wire:click="validate_('{{ $slug }}')"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                                Validate
                            </button>
                            @if ($active !== $slug)
                                <button type="button"
                                    wire:click="activate('{{ $slug }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="Switch the live storefront to {{ $meta['name'] }}?"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                                    Activate
                                </button>
                            @endif
                        </div>

                        @if ($reportSlug === $slug && $report)
                            <div class="mt-3 pt-3 border-t border-gray-100 text-xs space-y-1">
                                <div class="font-semibold mb-1 {{ $report->passed() ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $report->passed() ? 'READY' : 'NOT READY' }}
                                </div>
                                @foreach ($report->grouped() as $group => $checks)
                                    @foreach ($checks as $check)
                                        <div class="{{ $check['ok'] ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $check['ok'] ? '✓' : '✗' }} {{ $check['label'] }}
                                            @if (! $check['ok'] && $check['detail'])
                                                <span class="text-gray-400">— {{ $check['detail'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
