<div x-data x-init="$store.pageName = { name: '{{ $editingId ? 'Edit Purchase Order' : 'New Purchase Order' }}', slug: 'purchase-order-form' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.purchase.orders') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Purchase Order' : 'New Purchase Order' }}</h1>
                @if($order)
                    <p class="text-xs text-gray-400 font-mono">{{ $order->order_number }}
                        <span class="capitalize">— {{ $order->status }}</span>
                    </p>
                @endif
            </div>
        </div>
        <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save"
            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
            <svg wire:loading.remove wire:target="save" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
            </svg>
            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save Changes' : 'Create Purchase Order' }}</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- Left: Order info + Items --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">

            {{-- Order Info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Order Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Order Number <span class="text-red-500">*</span></label>
                        <input wire:model="orderNumber" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('orderNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Supplier <span class="text-red-500">*</span></label>
                        <x-searchable-select field="supplierId" :value="$supplierId"
                            :options="$supplierOptions"
                            placeholder="— Select —" search-placeholder="Search suppliers…" />
                        @error('supplierId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Order Date</label>
                        <input wire:model="orderDate" type="text" placeholder="Select date" readonly
                            class="flatpickr-only-date w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                        @error('orderDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Deadline</label>
                        <input wire:model="deadline" type="text" placeholder="Select date" readonly
                            class="flatpickr-only-date w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                        @error('deadline') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Notes</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Order Items</h2>
                    <button wire:click="addItem" type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add Item
                    </button>
                </div>

                @error('items') <p class="text-xs text-red-500 mb-3">{{ $message }}</p> @enderror

                <div class="space-y-3">
                    @foreach($items as $index => $item)
                        <div class="rounded-lg border border-gray-200 px-4 py-3" wire:key="item-{{ $index }}">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide pt-1.5">Item {{ $index + 1 }}</span>
                                @if(count($items) > 1)
                                    <button wire:click="removeItem({{ $index }})" type="button"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-12 gap-3 items-start">
                                <div class="col-span-12 md:col-span-6">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-[11px] text-gray-500">Product Variant <span class="text-red-500">*</span></label>
                                        @if($item['variant_id'] !== '')
                                            <button wire:click="viewPriceHistory({{ $item['variant_id'] }})" type="button" title="View purchase price history"
                                                class="inline-flex items-center gap-1 text-[11px] text-indigo-600 hover:text-indigo-700 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                History
                                            </button>
                                        @endif
                                    </div>
                                    <x-searchable-select field="items.{{ $index }}.variant_id" :value="$items[$index]['variant_id']"
                                        :options="$variantOptions" :images="$variantImages"
                                        placeholder="— Select a product variant —" search-placeholder="Search by product name or SKU…" />
                                    @error("items.{$index}.variant_id") <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-span-4 md:col-span-2">
                                    <label class="block text-[11px] text-gray-500 mb-1">Quantity <span class="text-red-500">*</span></label>
                                    <input wire:model.live="items.{{ $index }}.quantity" type="number" step="0.001" min="0"
                                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error("items.{$index}.quantity") <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-span-4 md:col-span-2">
                                    <label class="block text-[11px] text-gray-500 mb-1">Unit Price</label>
                                    <input wire:model.live="items.{{ $index }}.unit_price" type="number" step="0.01" min="0"
                                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error("items.{$index}.unit_price") <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-span-4 md:col-span-2">
                                    <label class="block text-[11px] text-gray-500 mb-1">Line Total</label>
                                    <div class="w-full rounded-lg bg-gray-50 px-2 py-1.5 text-sm font-medium text-gray-700 text-right">
                                        @if($item['quantity'] !== '' && $item['unit_price'] !== '')
                                            {{ number_format((float) $item['quantity'] * (float) $item['unit_price'], 2) }}
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: Summary --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Summary</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Items</span>
                        <span class="text-gray-700 font-medium">{{ count($items) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Total Quantity</span>
                        <span class="text-gray-700 font-medium">{{ rtrim(rtrim(number_format(collect($items)->sum(fn($i) => (float) ($i['quantity'] ?? 0)), 3), '0'), '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-base pt-3 border-t border-gray-100">
                        <span class="font-semibold text-gray-800">Order Total</span>
                        <span class="font-bold text-indigo-600">{{ number_format($this->grandTotal, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($order)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Status</h2>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium capitalize
                        {{ $order->status === 'received' ? 'bg-emerald-50 text-emerald-600' : ($order->status === 'cancelled' ? 'bg-red-50 text-red-500' : 'bg-amber-50 text-amber-600') }}">
                        {{ $order->status }}
                    </span>
                    <p class="text-xs text-gray-400 mt-2">Updates automatically once every item below is fully received.</p>
                </div>
            @endif

            {{-- Low stock / out of stock quick-add --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-gray-800">Needs Restocking</h2>
                        @if($restockGroups->isNotEmpty())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600">
                                {{ $restockGroups->sum(fn($g) => $g['variants']->count()) }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Low or out-of-stock variants — click + to add to this order.</p>
                    <div class="relative mt-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                        <input wire:model.live.debounce.300ms="restockSearch" type="text" placeholder="Search product or SKU…"
                            class="w-full pl-8 pr-3 py-1.5 text-sm rounded-lg border border-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="max-h-[480px] overflow-y-auto divide-y divide-gray-100">
                    @php($addedVariantIds = collect($items)->pluck('variant_id')->filter()->map(fn($v) => (string) $v)->all())
                    @forelse($restockGroups as $group)
                        <div class="px-5 py-3">
                            <div class="flex items-center gap-2 mb-2">
                                @php($thumb = $group['product']->featured_image ?? null)
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="" class="w-6 h-6 rounded object-cover shrink-0">
                                @endif
                                <span class="text-xs font-semibold text-gray-700 truncate">{{ $group['product']->name }}</span>
                            </div>
                            <div class="space-y-1.5">
                                @foreach($group['variants'] as $variant)
                                    <div class="flex items-center justify-between gap-2 pl-8">
                                        <div class="min-w-0">
                                            <span class="text-xs text-gray-500 font-mono truncate block">{{ $variant->sku }}</span>
                                            <span class="text-[11px] {{ $variant->stock_quantity <= 0 ? 'text-red-500 font-medium' : 'text-amber-600 font-medium' }}">
                                                {{ $variant->stock_quantity <= 0 ? 'Out of stock' : rtrim(rtrim(number_format($variant->stock_quantity, 3), '0'), '.') . ' left' }}
                                            </span>
                                        </div>
                                        @if(in_array((string) $variant->id, $addedVariantIds, true))
                                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-medium bg-emerald-50 text-emerald-600 shrink-0">
                                                Added
                                            </span>
                                        @else
                                            <button wire:click="addLowStockItem({{ $variant->id }})" type="button" title="Add to order"
                                                class="w-7 h-7 shrink-0 inline-flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-6 text-xs text-gray-400 text-center">
                            {{ trim($restockSearch) !== '' ? 'No matches for "' . $restockSearch . '".' : 'Nothing is low or out of stock right now.' }}
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Receiving --}}
    @if($order)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mt-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Receiving</h2>
                <p class="text-xs text-gray-400 mt-0.5">Record deliveries against this order — a purchase order can arrive in several partial shipments.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($receivingItems as $item)
                    <div class="p-5 flex flex-wrap items-center gap-4" wire:key="receive-{{ $item->id }}">
                        <div class="flex-1 min-w-[180px]">
                            <span class="text-sm font-medium text-gray-800">{{ $item->variant->product->name ?? 'Unknown product' }}</span>
                            <span class="block text-xs font-mono text-gray-400">{{ $item->variant->sku ?? '' }}</span>
                            @if($item->received_batches->isNotEmpty())
                                <div class="flex flex-wrap items-center gap-1 mt-1.5">
                                    @foreach($item->received_batches as $batch)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono font-medium bg-indigo-50 text-indigo-600">
                                            {{ $batch->batch_no }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-gray-400">Received / Ordered</p>
                            <p class="text-sm font-semibold {{ $item->remaining <= 0 ? 'text-emerald-600' : 'text-gray-700' }}">
                                {{ rtrim(rtrim(number_format($item->received_so_far, 3), '0'), '.') }} / {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}
                            </p>
                        </div>
                        @if($item->remaining > 0)
                            <div class="flex items-center gap-2 shrink-0">
                                <input wire:model="receiveQuantities.{{ $item->id }}" type="number" step="0.001" min="0" max="{{ $item->remaining }}"
                                    placeholder="Qty"
                                    class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <button wire:click="receiveItem({{ $item->id }})" type="button"
                                    class="px-3.5 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition">
                                    Receive
                                </button>
                            </div>
                            @error("receiveQuantities.{$item->id}")
                                <p class="text-xs text-red-500 shrink-0 basis-full">{{ $message }}</p>
                            @enderror
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600 shrink-0">
                                Fully Received
                            </span>
                        @endif
                    </div>
                @empty
                    <p class="p-5 text-sm text-gray-400">No items yet.</p>
                @endforelse
            </div>
        </div>
    @endif

    {{-- Purchase Price History modal --}}
    @if($showPriceHistory)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50" wire:click.self="closePriceHistory">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-800">Purchase Price History</h2>
                        @if($priceHistoryVariant)
                            <p class="text-xs text-gray-500 mt-0.5 truncate">
                                {{ $priceHistoryVariant->product->name ?? 'Unknown product' }}
                                <span class="font-mono text-gray-400">[{{ $priceHistoryVariant->sku }}]</span>
                            </p>
                        @endif
                    </div>
                    <button wire:click="closePriceHistory" type="button" class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-4 border-b border-gray-100 grid grid-cols-5 gap-3">
                    <div class="text-center">
                        <p class="text-[11px] text-gray-400">Variant Price</p>
                        <p class="text-sm font-semibold text-emerald-600">
                            {{ $priceHistoryVariant?->purchase_price !== null ? number_format($priceHistoryVariant->purchase_price, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-[11px] text-gray-400">Product Price</p>
                        <p class="text-sm font-semibold text-teal-600">
                            {{ $priceHistoryVariant?->product?->purchase_price !== null ? number_format($priceHistoryVariant->product->purchase_price, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-[11px] text-gray-400">Lowest Paid</p>
                        <p class="text-sm font-semibold text-indigo-600">
                            {{ $priceHistorySummary && $priceHistorySummary['lowest'] !== null ? number_format($priceHistorySummary['lowest'], 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-[11px] text-gray-400">Highest Paid</p>
                        <p class="text-sm font-semibold text-red-500">
                            {{ $priceHistorySummary && $priceHistorySummary['highest'] !== null ? number_format($priceHistorySummary['highest'], 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-[11px] text-gray-400">Most Recent</p>
                        <p class="text-sm font-semibold text-gray-700">
                            {{ $priceHistorySummary && $priceHistorySummary['most_recent'] !== null ? number_format($priceHistorySummary['most_recent'], 2) : '—' }}
                        </p>
                    </div>
                </div>

                <div class="overflow-y-auto divide-y divide-gray-100">
                    @forelse($priceHistory as $entry)
                        <div class="px-6 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                                        {{ match($entry['source']) {
                                            'current_variant' => 'bg-emerald-50 text-emerald-600',
                                            'current_product' => 'bg-teal-50 text-teal-600',
                                            'purchase_order' => 'bg-indigo-50 text-indigo-600',
                                            'batch' => 'bg-amber-50 text-amber-600',
                                            default => 'bg-purple-50 text-purple-600',
                                        } }}">
                                        {{ $entry['source_label'] }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $entry['date']?->format('M j, Y') ?? '—' }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 truncate">
                                    @if($entry['source'] === 'current_variant')
                                        SKU {{ $entry['reference'] }} &middot; Stored on the variant record
                                    @elseif($entry['source'] === 'current_product')
                                        {{ $entry['reference'] }} &middot; Stored on the product record
                                    @else
                                        {{ $entry['supplier'] ?? 'Unknown supplier' }} &middot; Ref {{ $entry['reference'] }} &middot; Qty {{ rtrim(rtrim(number_format($entry['quantity'], 3), '0'), '.') }}
                                    @endif
                                </p>
                            </div>
                            <span class="text-sm font-semibold text-gray-800 shrink-0">{{ number_format($entry['unit_price'], 2) }}</span>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-sm text-gray-400 text-center">No purchase price history recorded for this variant yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

</div>
