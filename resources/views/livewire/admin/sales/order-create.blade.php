<div x-data x-init="$store.pageName = { name: 'Create Order', slug: 'sales-orders-create' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sales.orders') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-900">Create Order</h1>
        </div>
        <button wire:click="save" type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
            </svg>
            Create Order
        </button>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- Left: Customer + Items --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">

            {{-- Customer --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Customer</h2>

                @if($selectedCustomer)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $selectedCustomer->full_name }}</p>
                            <p class="text-xs text-gray-400">{{ $selectedCustomer->phone }}</p>
                        </div>
                        <button type="button" wire:click="clearCustomer" class="text-xs text-gray-400 hover:text-red-500 transition">Change</button>
                    </div>
                @else
                    <div class="relative">
                        <input wire:model.live.debounce.300ms="customerSearch" type="text" placeholder="Search customer by name or phone… (leave empty for guest order)"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @if($customerSearch !== '')
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @forelse($customerOptions as $option)
                                    <button type="button" wire:click="selectCustomer({{ $option->id }})"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between">
                                        <span>{{ $option->full_name }}</span>
                                        <span class="text-xs text-gray-400">{{ $option->phone }}</span>
                                    </button>
                                @empty
                                    <p class="px-3 py-2 text-xs text-gray-400">No matching customers found.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                @endif

                @if($selectedCustomer && $customerAddresses->isNotEmpty())
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Billing Address</label>
                            <select wire:model="billingAddressId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— None —</option>
                                @foreach($customerAddresses as $address)
                                    <option value="{{ $address->id }}">{{ $address->name }} — {{ Str::limit($address->full_address, 40) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Shipping Address</label>
                            <select wire:model="shippingAddressId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— None —</option>
                                @foreach($customerAddresses as $address)
                                    <option value="{{ $address->id }}">{{ $address->name }} — {{ Str::limit($address->full_address, 40) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Order Items</h2>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="relative">
                        <input wire:model.live.debounce.300ms="productSearch" type="text" placeholder="Search products to add…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @if($productSearch !== '')
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @forelse($productOptions as $option)
                                    <button type="button" wire:click="addProductItem({{ $option->id }})"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between">
                                        <span>{{ $option->name }}</span>
                                        <span class="text-xs text-gray-400 font-mono">{{ $option->code }}</span>
                                    </button>
                                @empty
                                    <p class="px-3 py-2 text-xs text-gray-400">No matching products found.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                    <div class="relative">
                        <input wire:model.live.debounce.300ms="comboSearch" type="text" placeholder="Search customer combos to add…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @if($comboSearch !== '')
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @forelse($comboOptions as $option)
                                    <button type="button" wire:click="addComboItem({{ $option->id }})"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between">
                                        <span>{{ $option->name ?? 'Custom Combo #' . $option->id }}</span>
                                        <span class="text-xs text-gray-400">{{ $option->customer?->full_name ?? 'Guest' }}</span>
                                    </button>
                                @empty
                                    <p class="px-3 py-2 text-xs text-gray-400">No matching combos found.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>

                @error('items') <p class="text-xs text-red-500 mb-3">{{ $message }}</p> @enderror

                <div class="space-y-3">
                    @forelse($items as $i => $item)
                        <div class="rounded-lg border border-gray-200 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-medium text-gray-800">{{ $item['label'] }}</span>
                                        @if($item['kind'] === 'combo')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-indigo-50 text-indigo-500">Combo</span>
                                        @endif
                                    </div>

                                    @if($item['kind'] === 'product')
                                        @php($variants = $variantOptions->get((int) $item['product_id']))
                                        @if($variants && $variants->isNotEmpty())
                                            <select wire:change="selectVariantForItem({{ $i }}, $event.target.value)"
                                                class="mt-1.5 w-48 rounded-lg border border-gray-300 px-2 py-1.5 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <option value="">— No variant —</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" @selected($item['variant_id'] == $variant->id)>{{ $variant->sku }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    @endif
                                </div>

                                <button type="button" wire:click="removeItem({{ $i }})"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-4 gap-3 mt-3">
                                <div>
                                    <label class="block text-[11px] text-gray-500 mb-1">Quantity</label>
                                    <input wire:model="items.{{ $i }}.quantity" type="number" step="0.001" min="0.001"
                                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-500 mb-1">Unit Price</label>
                                    <input wire:model="items.{{ $i }}.unit_price" type="number" step="0.01" min="0" @disabled($item['is_gift'])
                                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-500 mb-1">Purchase Price</label>
                                    <input wire:model="items.{{ $i }}.purchase_price" type="number" step="0.01" min="0" placeholder="—"
                                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <div class="flex items-end pb-1.5">
                                    <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                                        <input wire:model="items.{{ $i }}.is_gift" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        Gift item
                                    </label>
                                </div>
                            </div>
                        </div>
                        @error('items.' . $i . '.quantity') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        @error('items.' . $i . '.unit_price') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    @empty
                        <p class="text-sm text-gray-400">No items added yet. Search above to add a product or combo.</p>
                    @endforelse
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Notes</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Customer Note</label>
                        <textarea wire:model="customerNote" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Admin Note</label>
                        <textarea wire:model="adminNote" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Order settings + summary --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Order Settings</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Source</label>
                        <select wire:model="source" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($sources as $src)
                                <option value="{{ $src->value }}">{{ $src->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="status" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($statuses as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Status</label>
                        <select wire:model="paymentStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($paymentStatuses as $ps)
                                <option value="{{ $ps->value }}">{{ $ps->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Fulfillment Status</label>
                        <select wire:model="fulfillmentStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($fulfillmentStatuses as $fs)
                                <option value="{{ $fs->value }}">{{ $fs->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Totals</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Discount Amount</label>
                        <input wire:model.live="discountAmount" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Shipping Amount</label>
                        <input wire:model.live="shippingAmount" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Tax Amount</label>
                        <input wire:model.live="taxAmount" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div class="pt-3 border-t border-gray-100">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Coupon Code</label>
                        <div class="flex gap-2">
                            <input wire:model="couponCode" type="text" placeholder="e.g. FREESHIP50"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @if($shippingDiscount > 0)
                                <button type="button" wire:click="removeCoupon"
                                    class="px-3 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Remove</button>
                            @else
                                <button type="button" wire:click="applyCoupon"
                                    class="px-3 py-2 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Apply</button>
                            @endif
                        </div>
                        @if($couponError)
                            <p class="text-xs text-red-500 mt-1.5">{{ $couponError }}</p>
                        @elseif($shippingDiscount > 0)
                            <p class="text-xs text-emerald-600 mt-1.5">Shipping discount of {{ number_format($shippingDiscount, 2) }} applied.</p>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-gray-100 space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="text-gray-700 font-medium">{{ number_format($this->itemsSubtotal, 2) }}</span>
                        </div>
                        @if($shippingDiscount > 0)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Shipping Discount</span>
                                <span class="text-emerald-600 font-medium">−{{ number_format($shippingDiscount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between text-base pt-1.5 border-t border-gray-100">
                            <span class="font-semibold text-gray-800">Grand Total</span>
                            <span class="font-bold text-indigo-600">{{ number_format($this->grandTotal, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
