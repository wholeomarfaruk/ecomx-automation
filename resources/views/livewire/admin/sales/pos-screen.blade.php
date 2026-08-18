<div x-data x-init="$store.pageName = { name: 'POS Screen', slug: 'sales-pos-screen' }">

    @if(! $session)
        <div class="max-w-md mx-auto mt-16 bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
            <div class="w-14 h-14 mx-auto rounded-full bg-amber-50 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">No open session</p>
            <p class="text-xs text-gray-400 mt-1 mb-5">You need to open a POS session before taking sales.</p>
            <a href="{{ route('admin.sales.pos.sessions.open') }}" wire:navigate
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                Open Session
            </a>
        </div>
    @else
        <div class="grid grid-cols-12 gap-5">

            {{-- Left: Product grid --}}
            <div class="col-span-12 lg:col-span-8">
                <div class="relative mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input wire:model.live.debounce.200ms="productSearch" type="text" placeholder="Search products by name or code…"
                        class="w-full pl-10 pr-3 py-3 text-sm rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 shadow-sm">
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                    @forelse($productOptions as $product)
                        <button type="button" wire:click="addProductItem({{ $product->id }})"
                            class="text-left bg-white rounded-2xl border border-gray-200 hover:border-indigo-400 hover:shadow-md transition p-3 group">
                            <div class="h-20 rounded-xl bg-gray-100 mb-2.5 overflow-hidden flex items-center justify-center">
                                @if($product->featured_image_id)
                                    <img src="{{ file_path($product->featured_image_id) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                    </svg>
                                @endif
                            </div>
                            <p class="text-xs font-medium text-gray-800 truncate group-hover:text-indigo-600">{{ $product->name }}</p>
                            <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ number_format($product->sale_price ?? $product->price ?? 0, 2) }}</p>
                        </button>
                    @empty
                        <div class="col-span-full text-center py-16">
                            <p class="text-sm text-gray-400">No products found.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Right: Cart --}}
            <div class="col-span-12 lg:col-span-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col h-full sticky top-4">

                    {{-- Customer --}}
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 min-w-0">
                                <x-searchable-select field="customerId" :value="$customerId"
                                    :options="$customerOptions" placeholder="Walk-in (no customer)" search-placeholder="Search customer…" />
                            </div>
                            <button type="button" wire:click="openNewCustomerModal" title="Register new customer"
                                class="w-9 h-9 shrink-0 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-600 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Cart items --}}
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 max-h-[40vh]">
                        @forelse($items as $i => $item)
                            @php($variants = $variantOptions->get((int) $item['product_id']))
                            <div class="rounded-lg border border-gray-200 px-3 py-2.5">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-11 h-11 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if($item['image_url'] ?? null)
                                            <img src="{{ $item['image_url'] }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-sm font-medium text-gray-800 truncate">{{ $item['label'] }}</span>
                                            <button type="button" wire:click="removeItem({{ $i }})" class="text-gray-300 hover:text-red-500 transition shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>

                                        @if($variants && $variants->isNotEmpty())
                                            <select wire:change="selectVariantForItem({{ $i }}, $event.target.value)"
                                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-2 py-1 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <option value="">— No variant —</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" @selected($item['variant_id'] == $variant->id)>{{ $variant->sku }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-between mt-2 gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" wire:click="decrementQuantity({{ $i }})"
                                            class="w-6 h-6 flex items-center justify-center rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 transition">−</button>
                                        <span class="w-8 text-center text-sm font-medium text-gray-800">{{ rtrim(rtrim($item['quantity'], '0'), '.') ?: $item['quantity'] }}</span>
                                        <button type="button" wire:click="incrementQuantity({{ $i }})"
                                            class="w-6 h-6 flex items-center justify-center rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 transition">+</button>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <input wire:model.live="items.{{ $i }}.unit_price" type="number" step="0.01"
                                            min="{{ $this->minAllowedPrice((float) ($item['purchase_price'] ?: 0)) ?? 0 }}"
                                            class="w-20 rounded-md border border-gray-300 px-1.5 py-1 text-sm text-right font-semibold text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                </div>
                                @if($item['purchase_price'] !== '')
                                    <p class="text-right text-[11px] text-gray-400 mt-0.5">min {{ number_format($this->minAllowedPrice((float) $item['purchase_price']) ?? 0, 2) }}</p>
                                @endif
                                @error('items.' . $i . '.unit_price') <p class="text-right text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                <p class="text-right text-xs text-gray-400 mt-1">= {{ number_format((float) $item['quantity'] * (float) $item['unit_price'], 2) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 text-center py-8">Cart is empty. Tap a product to add it.</p>
                        @endforelse
                    </div>

                    {{-- Coupon --}}
                    <div class="px-4 py-3 border-t border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 min-w-0">
                                <x-searchable-select field="couponCode" :value="$couponCode"
                                    :options="$couponOptions" placeholder="No coupon" search-placeholder="Search coupon code…" />
                            </div>
                            @if($couponCode !== '')
                                <button type="button" wire:click="removeCoupon" class="shrink-0 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Remove</button>
                            @endif
                        </div>
                        @if($couponError)
                            <p class="text-xs text-red-500 mt-1">{{ $couponError }}</p>
                        @elseif($shippingDiscount > 0)
                            <p class="text-xs text-emerald-600 mt-1">Discount of {{ number_format($shippingDiscount, 2) }} applied.</p>
                        @endif
                    </div>

                    {{-- Totals + Payment --}}
                    <div class="px-4 py-4 border-t border-gray-100 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="text-gray-700 font-medium">{{ number_format($this->itemsSubtotal, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3 text-sm">
                            <label for="pos-discount" class="text-gray-500 shrink-0">Discount</label>
                            <input id="pos-discount" wire:model.live="discountAmount" type="number" step="0.01" min="0" placeholder="0.00"
                                class="w-28 rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-right focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        @error('discountAmount') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                        @if($shippingDiscount > 0)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Coupon Discount</span>
                                <span class="text-emerald-600 font-medium">−{{ number_format($shippingDiscount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between text-lg pt-2 border-t border-gray-100">
                            <span class="font-semibold text-gray-800">Total</span>
                            <span class="font-bold text-indigo-600">{{ number_format($this->grandTotal, 2) }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <select wire:model="paymentMethod" class="rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                            </select>
                            <input wire:model.live="amountTendered" type="number" step="0.01" min="0" placeholder="Tendered"
                                class="rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        @error('amountTendered') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                        @if($amountTendered !== '' && (float) $amountTendered >= $this->grandTotal)
                            <div class="flex items-center justify-between text-sm bg-emerald-50 rounded-lg px-3 py-2">
                                <span class="text-emerald-700">Change Due</span>
                                <span class="text-emerald-700 font-semibold">{{ number_format($this->changeDue, 2) }}</span>
                            </div>
                        @endif

                        <button wire:click="completeSale" type="button"
                            class="w-full px-4 py-3.5 text-base font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm disabled:opacity-50"
                            @disabled(empty($items))>
                            Complete Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Register Customer Modal --}}
        <div x-cloak x-data="{ open: @entangle('newCustomerModal') }" x-show="open" x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-gray-900">Register New Customer</h2>
                    </div>
                    <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form wire:submit.prevent="createCustomer" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input wire:model="newCustomerName" type="text" autofocus
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newCustomerName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Phone <span class="text-red-500">*</span></label>
                        <input wire:model="newCustomerPhone" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newCustomerPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Email</label>
                        <input wire:model="newCustomerEmail" type="email"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newCustomerEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Register & Select</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
