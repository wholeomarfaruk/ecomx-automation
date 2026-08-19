<div>
    <p class="text-sm text-gray-500 mb-6">
        Pick the site-wide colour palette for the storefront. The active palette applies to every visitor immediately.
    </p>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach ($palettes as $palette)
            @php $c = $swatches[$palette] ?? ['pri' => '#999', 'sec' => '#eee', 'ac' => '#999']; @endphp
            <div class="bg-white rounded-xl border {{ $active === $palette ? 'border-indigo-400 ring-1 ring-indigo-100' : 'border-gray-200' }} p-4 text-center">
                <div class="flex justify-center gap-1.5 mb-3">
                    <span class="block w-7 h-7 rounded-full border border-black/10" style="background:{{ $c['pri'] }}"></span>
                    <span class="block w-7 h-7 rounded-full border border-black/10" style="background:{{ $c['sec'] }}"></span>
                    <span class="block w-7 h-7 rounded-full border border-black/10" style="background:{{ $c['ac'] }}"></span>
                </div>
                <div class="font-semibold text-sm text-gray-800 mb-2">{{ ucfirst($palette) }}</div>
                @if ($active === $palette)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600">Active</span>
                @else
                    <button type="button"
                        wire:click="activate('{{ $palette }}')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                        Activate
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
