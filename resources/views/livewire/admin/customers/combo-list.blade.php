<div x-data x-init="$store.pageName = { name: 'Customer Combos', slug: 'customers-combo' }">

    {{-- Header --}}
    <div class="grid grid-cols-1 gap-3 mb-6 max-w-xs">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Total Custom Combos</p>
            <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
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
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by customer name, phone, or combo name…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Combo</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Qty</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Price</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($combos as $combo)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer" wire:click="toggleExpand({{ $combo->id }})">
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-gray-800">{{ $combo->name ?? 'Custom Combo #' . $combo->id }}</span>
                                <span class="block text-xs text-gray-400">{{ $combo->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="block text-sm text-gray-600">{{ $combo->customer?->full_name ?? 'Guest' }}</span>
                                <span class="block text-xs text-gray-400">{{ $combo->customer?->phone ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm text-gray-600">{{ $combo->items_count }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm text-gray-600">{{ rtrim(rtrim(number_format($combo->quantity, 3), '0'), '.') }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($combo->price, 2) }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform {{ in_array($combo->id, $expanded) ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                                </svg>
                            </td>
                        </tr>
                        @if(in_array($combo->id, $expanded))
                            <tr>
                                <td colspan="6" class="px-5 py-3 bg-gray-50/60">
                                    <div class="space-y-1.5">
                                        @forelse($combo->items as $comboItem)
                                            <div class="flex items-center justify-between text-xs text-gray-600">
                                                <span>
                                                    {{ $comboItem->product->name ?? 'Unknown product' }}
                                                    @if($comboItem->variant)
                                                        <span class="font-mono text-gray-400">({{ $comboItem->variant->sku }})</span>
                                                    @endif
                                                    <span class="text-gray-400">× {{ rtrim(rtrim(number_format($comboItem->quantity, 3), '0'), '.') }}</span>
                                                </span>
                                                <span>{{ number_format($comboItem->price, 2) }}</span>
                                            </div>
                                        @empty
                                            <p class="text-xs text-gray-400">No components.</p>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No custom combos found</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Customer-built combos will appear here</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($combos->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $combos->links() }}
            </div>
        @endif
    </div>
</div>
