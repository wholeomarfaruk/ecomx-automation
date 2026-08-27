<div x-data x-init="$store.pageName = { name: 'Stock In', slug: 'inventory-stock-in' }">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.inventory.stock') }}"
            class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <h1 class="text-lg font-semibold text-gray-900">Stock In</h1>
    </div>

    <form wire:submit.prevent="submit" class="max-w-2xl space-y-6">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-800">Purchase Order (optional)</h2>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Purchase Order</label>
                <select wire:model.live="purchaseOrderId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">— None —</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->order_number }} — {{ $po->supplier->name ?? 'Unknown supplier' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1.5">A PO can hold several products — pick which item this delivery is for below.</p>
            </div>

            @if($purchaseOrderId && $purchaseOrderItems->isNotEmpty())
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Item <span class="text-red-500">*</span></label>
                    <select wire:model.live="purchaseOrderItemId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select an item —</option>
                        @foreach($purchaseOrderItems as $item)
                            <option value="{{ $item->id }}" @disabled($item->remaining <= 0)>
                                {{ $item->variant->product->name ?? 'Unknown product' }} ({{ $item->variant->sku ?? '' }}) — received {{ rtrim(rtrim(number_format($item->received_so_far, 3), '0'), '.') }} of {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}
                                @if($item->remaining <= 0) — fully received @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1.5">Selecting an item fills in product, variant, remaining quantity, and price below. A PO item can be received across several deliveries; the whole order is only marked Received once every item is fully received.</p>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-800">Product</h2>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Product <span class="text-red-500">*</span></label>
                <x-searchable-select field="productId" :value="$productId"
                    :options="$products->pluck('name', 'id')" placeholder="— Select a product —" search-placeholder="Search products…" />
                @error('productId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            @if($productId && $variants->isNotEmpty())
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Variant</label>
                    <select wire:model="variantId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— No variant (product-level) —</option>
                        @foreach($variants as $variant)
                            <option value="{{ $variant->id }}">{{ $variant->sku }} — current: {{ rtrim(rtrim(number_format($variant->stock_quantity, 3), '0'), '.') }}</option>
                        @endforeach
                    </select>
                    @error('variantId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="max-w-xs">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Quantity <span class="text-red-500">*</span></label>
                <input wire:model="quantity" type="number" step="0.001" min="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('quantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
            <label class="flex items-center justify-between gap-4 cursor-pointer">
                <span>
                    <span class="block text-sm font-semibold text-gray-800">Track as a Batch</span>
                    <span class="block text-xs text-gray-400">Give this stock-in a batch/lot number and optional expiry date, for FEFO issuing and expiry alerts.</span>
                </span>
                <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                    <input type="checkbox" wire:model.live="useBatch" class="peer sr-only">
                    <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                    <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                </span>
            </label>

            @if($useBatch)
                <div class="space-y-4 pt-2 border-t border-gray-100">
                    @if(! $productId)
                        <p class="text-xs text-gray-400">Select a product above to choose or create a batch.</p>
                    @else
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch <span class="text-red-500">*</span></label>
                            <x-searchable-select field="batchSelection" :value="$batchSelection"
                                :options="collect([\App\Livewire\Admin\Inventory\StockIn::NEW_BATCH => '+ Create new batch'])
                                    ->union($existingBatches->mapWithKeys(fn ($batch) => [
                                        (string) $batch->id => $batch->batch_no
                                            . ' — ' . rtrim(rtrim(number_format($batch->quantity, 3), '0'), '.') . ' in stock'
                                            . ($batch->expiry_date ? ' — exp. ' . $batch->expiry_date->format('M j, Y') : ''),
                                    ]))"
                                placeholder="+ Create new batch" search-placeholder="Search batches…" />
                            @error('batchSelection') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-gray-400 mt-1.5">Pick an existing batch to add this stock to it, or create a new one.</p>
                        </div>

                        @if($batchSelection === \App\Livewire\Admin\Inventory\StockIn::NEW_BATCH)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch / Lot No <span class="text-red-500">*</span></label>
                                    <div class="flex items-center gap-2">
                                        <input wire:model="batchNo" type="text" readonly placeholder="Click Generate"
                                            class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm font-mono text-gray-700 cursor-default">
                                        <button wire:click="generateBatchNo" type="button"
                                            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                            </svg>
                                            {{ $batchNo ? 'Regenerate' : 'Generate' }}
                                        </button>
                                    </div>
                                    @error('batchNo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Purchase Price (per unit)</label>
                                    <input wire:model="purchasePrice" type="number" step="0.01" min="0" placeholder="0.00"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('purchasePrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Manufacture Date</label>
                                    <input wire:model="manufactureDate" type="date"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('manufactureDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Expiry Date</label>
                                    <input wire:model="expiryDate" type="date"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('expiryDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4">
                                <div>
                                    <p class="text-[11px] text-gray-400">Batch No</p>
                                    <p class="text-sm font-mono text-gray-700">{{ $batchNo }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400">Manufacture Date</p>
                                    <p class="text-sm text-gray-700">{{ $manufactureDate ?: '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400">Expiry Date</p>
                                    <p class="text-sm text-gray-700">{{ $expiryDate ?: '—' }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Adding stock to this existing batch — quantity above will be added to it. You can still adjust the price if this delivery's cost differs.</p>
                            <div class="max-w-xs">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Purchase Price (per unit)</label>
                                <input wire:model="purchasePrice" type="number" step="0.01" min="0" placeholder="0.00"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <label class="block text-xs font-medium text-gray-600 mb-1.5">Note</label>
            <textarea wire:model="note" rows="2" placeholder="e.g. Supplier invoice #, purchase order reference…"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                Add Stock
            </button>
        </div>
    </form>

</div>
