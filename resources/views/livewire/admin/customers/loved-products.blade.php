<div x-data x-init="$store.pageName = { name: 'Loved Products', slug: 'customers-loved' }">

    {{-- Header --}}
    <div class="grid grid-cols-1 gap-3 mb-6 max-w-xs">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Total Loves</p>
            <p class="text-xl font-semibold text-rose-500 mt-0.5">{{ number_format($totalLoves) }}</p>
        </div>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by product name or code…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Price</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Loved</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $i => $product)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-400">{{ ($products->currentPage() - 1) * $products->perPage() + $i + 1 }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if($product->featured_image_id)
                                            <img src="{{ file_path($product->featured_image_id) }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-sm font-medium text-gray-800 truncate">{{ $product->name }}</span>
                                        <span class="block text-xs font-mono text-gray-400">{{ $product->code }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs text-gray-500 capitalize">{{ $product->product_type->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($product->sale_price)
                                    <span class="text-sm font-medium text-gray-800">{{ number_format($product->sale_price, 2) }}</span>
                                @elseif($product->price)
                                    <span class="text-sm font-medium text-gray-800">{{ number_format($product->price, 2) }}</span>
                                @else
                                    <span class="text-sm text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-rose-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.653 16.915l-.005-.003-.019-.01a20.759 20.759 0 01-1.162-.682 22.045 22.045 0 01-2.582-1.9C4.045 12.733 2 10.352 2 7.5 2 5.015 4.015 3 6.5 3c1.36 0 2.585.617 3.5 1.594C10.915 3.617 12.14 3 13.5 3 15.985 3 18 5.015 18 7.5c0 2.852-2.044 5.234-3.885 6.82a22.045 22.045 0 01-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 01-.69.001l-.002-.001z"/>
                                    </svg>
                                    {{ number_format($product->wishlist_items_count) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No loved products yet</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Products customers add to their Loved list will appear here</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
