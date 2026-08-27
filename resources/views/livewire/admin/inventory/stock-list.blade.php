<div x-data x-init="$store.pageName = { name: 'Inventory', slug: 'inventory-stock' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-4 gap-3 flex-1 max-w-3xl">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Variants Tracked</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ number_format($totalVariants) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Units</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ number_format($totalUnits) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Low Stock</p>
                <p class="text-xl font-semibold text-amber-600 mt-0.5">{{ number_format($lowStockCount) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Out of Stock</p>
                <p class="text-xl font-semibold text-red-500 mt-0.5">{{ number_format($outOfStockCount) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.inventory.movements') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                Movement History
            </a>
            <a href="{{ route('admin.inventory.warehouses') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                Warehouses
            </a>
            <a href="{{ route('admin.inventory.settings') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
                Settings
            </a>
        </div>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Tabs --}}
        <div class="flex items-center gap-1 px-5 pt-4 border-b border-gray-100">
            @foreach(['all' => 'All', 'low' => 'Low Stock', 'out' => 'Out of Stock'] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')" type="button"
                    class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition
                        {{ $filter === $key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by product name or SKU…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">SKU</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Stock</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($variants as $variant)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-gray-800">{{ $variant->product->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm font-mono text-gray-500">{{ $variant->sku }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $qty = (float) $variant->stock_quantity;
                                    $effectiveThreshold = $variant->reorder_level > 0 ? (float) $variant->reorder_level : $lowStockThreshold;
                                    $badge = $qty <= 0 ? 'bg-red-50 text-red-500' : ($qty <= $effectiveThreshold ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600');
                                    $label = $qty <= 0 ? 'Out of Stock' : ($qty <= $effectiveThreshold ? 'Low Stock' : 'In Stock');
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $badge }}">{{ $label }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-semibold text-gray-800">{{ rtrim(rtrim(number_format($variant->stock_quantity, 3), '0'), '.') }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button wire:click="openAdjustModal({{ $variant->id }})" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                    Adjust
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No variants found</p>
                                <p class="text-xs text-gray-400 mt-0.5">Try adjusting the filters above</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($variants->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $variants->links() }}
            </div>
        @endif
    </div>

    {{-- Adjust Stock Modal --}}
    <div x-cloak x-data="{ open: @entangle('adjustModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Adjust Stock</h2>
                    <p class="text-xs text-gray-400">Sets the absolute quantity — the difference is logged as an audited movement.</p>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="saveAdjustment" class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">New Quantity</label>
                    <input wire:model="adjustQuantity" type="number" step="0.001" min="0"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('adjustQuantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Reason (optional)</label>
                    <textarea wire:model="adjustNote" rows="2" placeholder="e.g. Physical recount"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Save</button>
                </div>
            </form>
        </div>
    </div>

</div>
