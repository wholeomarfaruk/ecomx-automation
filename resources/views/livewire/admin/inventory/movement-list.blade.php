<div x-data x-init="$store.pageName = { name: 'Stock Movements', slug: 'inventory-movements' }">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inventory.stock') }}"
                class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Stock Movements</h1>
                <p class="text-xs text-gray-400">{{ number_format($totalCount) }} total entries — an immutable audit log of every stock change</p>
            </div>
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
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by product name or SKU…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterType"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Types</option>
                <option value="initial">Initial</option>
                <option value="import">Import</option>
                <option value="sale">Sale</option>
                <option value="sale_cancelled">Sale Cancelled</option>
                <option value="adjustment">Adjustment</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Warehouse</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Change</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Before → After</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($movements as $movement)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-xs text-gray-500">{{ $movement->created_at?->format('M d, Y H:i') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-gray-800">{{ $movement->product->name ?? '—' }}</span>
                                @if($movement->variant)
                                    <span class="block text-xs font-mono text-gray-400">{{ $movement->variant->sku }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $movement->warehouse->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $typeStyles = [
                                        'initial' => 'bg-gray-100 text-gray-500',
                                        'import' => 'bg-gray-100 text-gray-500',
                                        'sale' => 'bg-red-50 text-red-500',
                                        'sale_cancelled' => 'bg-emerald-50 text-emerald-600',
                                        'adjustment' => 'bg-indigo-50 text-indigo-600',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $typeStyles[$movement->type] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ str_replace('_', ' ', $movement->type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-semibold {{ $movement->quantity >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ $movement->quantity >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($movement->quantity, 3), '0'), '.') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-xs text-gray-400">
                                    {{ rtrim(rtrim(number_format($movement->before_quantity, 3), '0'), '.') }}
                                    →
                                    {{ rtrim(rtrim(number_format($movement->after_quantity, 3), '0'), '.') }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs text-gray-500">{{ $movement->createdBy?->name ?? 'System' }}</span>
                                @if($movement->note)
                                    <span class="block text-xs text-gray-400" title="{{ $movement->note }}">{{ \Illuminate\Support\Str::limit($movement->note, 40) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No stock movements yet</p>
                                <p class="text-xs text-gray-400 mt-0.5">Every stock change will be logged here</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

</div>
