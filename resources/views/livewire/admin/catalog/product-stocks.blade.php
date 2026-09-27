<div>
    @php
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 3), '0'), '.');
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h2 class="text-sm font-semibold text-gray-800">Stocks</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                @if($inventoryEnabled)
                    Balance at {{ $warehouse->name }} — on hand minus what's booked by confirmed orders.
                @else
                    Inventory module is off — each item's own stock quantity is the balance.
                @endif
            </p>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
            {{ $savedType === 'variable' ? 'Variable · ' . $rows->count() . ' ' . \Illuminate\Support\Str::plural('variant', $rows->count()) : 'Simple product' }}
        </span>
    </div>

    @if($pendingType !== $savedType)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-700 mb-4">
            Product type was changed to "{{ ucfirst($pendingType) }}" but not saved yet — the stock below is for the saved type ("{{ ucfirst($savedType) }}"). Save the product to see the new layout.
        </div>
    @endif

    @if($savedType === 'combo')
        <p class="text-sm text-gray-400">Combo products don't carry their own stock — it's derived from the component products.</p>
    @else
        {{-- Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
            <div class="rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">On Hand</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $fmt($totalOnHand) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Booked</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $fmt($totalBooked) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Available</p>
                <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ $fmt($totalAvailable) }}</p>
            </div>
            @if($savedType === 'variable')
                <div class="rounded-xl border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-400">Low / Out</p>
                    <p class="text-xl font-semibold mt-0.5">
                        <span class="text-amber-600">{{ $lowStockCount }}</span>
                        <span class="text-gray-300">/</span>
                        <span class="text-red-500">{{ $outOfStockCount }}</span>
                    </p>
                </div>
            @else
                <div class="rounded-xl border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-400">Stock Status</p>
                    <p class="text-sm font-semibold text-gray-800 mt-1.5">{{ ucwords(str_replace('_', ' ', $product->stock_status)) }}</p>
                </div>
            @endif
        </div>

        {{-- Items --}}
        @if($rows->isEmpty())
            <div class="border border-dashed border-gray-200 rounded-xl px-4 py-8 text-center mb-6">
                <p class="text-sm text-gray-400">No variants yet. Generate variants in the Variants tab to track their stock.</p>
            </div>
        @else
            <div class="overflow-x-auto border border-gray-200 rounded-xl mb-6">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $savedType === 'variable' ? 'Variant' : 'Product' }}</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">SKU</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">On Hand</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Booked</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Available</th>
                            <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rows as $row)
                            @php
                                $qty = $row->available;
                                $badge = $qty <= 0 ? 'bg-red-50 text-red-500' : ($qty <= $row->threshold ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600');
                                $label = $qty <= 0 ? 'Out of Stock' : ($qty <= $row->threshold ? 'Low Stock' : 'In Stock');
                            @endphp
                            <tr wire:key="stock-row-{{ $row->variant_id ?? 'product' }}" class="hover:bg-gray-50/50 transition">
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-gray-800">{{ $row->label }}</span>
                                    @if($row->status === 'inactive')
                                        <span class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-500">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-mono text-gray-500">{{ $row->sku }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ $fmt($row->on_hand) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-500">{{ $fmt($row->booked) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">{{ $fmt($row->available) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap {{ $badge }}">{{ $label }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @if($inventoryEnabled)
                                            <a href="{{ route('admin.inventory.stock-detail', ['type' => $row->variant_id ? 'variant' : 'product', 'id' => $row->variant_id ?? $product->id]) }}" wire:navigate
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                                Details
                                            </a>
                                        @endif
                                        <button wire:click="openAdjustModal({{ $row->variant_id ?? 'null' }})" type="button"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                            Adjust
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    {{-- Recent movements --}}
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold text-gray-800">Recent Movements</h3>
        @if($inventoryEnabled)
            <a href="{{ route('admin.inventory.movements') }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View all</a>
        @endif
    </div>
    @if($movements->isEmpty())
        <p class="text-sm text-gray-400">No stock movements recorded for this product yet.</p>
    @else
        <div class="overflow-x-auto border border-gray-200 rounded-xl">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        @if($savedType === 'variable')
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Variant</th>
                        @endif
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Qty</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Balance</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                        $typeStyles = [
                            'initial' => 'bg-gray-100 text-gray-500',
                            'import' => 'bg-gray-100 text-gray-500',
                            'sale' => 'bg-red-50 text-red-500',
                            'sale_cancelled' => 'bg-emerald-50 text-emerald-600',
                            'adjustment' => 'bg-indigo-50 text-indigo-600',
                        ];
                    @endphp
                    @foreach($movements as $movement)
                        <tr wire:key="stock-movement-{{ $movement->id }}">
                            <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">{{ $movement->created_at?->format('M j, Y g:i A') }}</td>
                            @if($savedType === 'variable')
                                <td class="px-4 py-2.5 text-xs font-mono text-gray-500">{{ $movement->variant?->sku ?? '—' }}</td>
                            @endif
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize whitespace-nowrap {{ $typeStyles[$movement->type] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ str_replace('_', ' ', $movement->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="text-sm font-semibold {{ $movement->quantity >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ $movement->quantity >= 0 ? '+' : '' }}{{ $fmt($movement->quantity) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right text-xs text-gray-400 whitespace-nowrap">
                                {{ $fmt($movement->before_quantity) }} → {{ $fmt($movement->after_quantity) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="text-xs text-gray-500">{{ $movement->createdBy?->name ?? 'System' }}</span>
                                @if($movement->note)
                                    <span class="block text-xs text-gray-400" title="{{ $movement->note }}">{{ \Illuminate\Support\Str::limit($movement->note, 40) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Adjust Stock Modal --}}
    <div x-cloak x-data="{ open: @entangle('adjustModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Adjust Stock</h2>
                    <p class="text-xs text-gray-400">{{ $adjustLabel }} — sets the absolute on-hand quantity; the difference is logged as a movement.</p>
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
                    @error('adjustNote') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Save</button>
                </div>
            </form>
        </div>
    </div>

</div>
