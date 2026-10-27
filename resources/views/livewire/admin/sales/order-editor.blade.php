<div>
    {{-- Edit Order card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">Edit Order</h2>
            @unless($editable)
                <span class="text-[11px] text-gray-400">Locked — {{ $order->status->label() }}</span>
            @endunless
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button type="button" wire:click="openItemsModal" @disabled(! $editable)
                class="px-3 py-2 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition disabled:opacity-40 disabled:cursor-not-allowed">Edit Items</button>
            <button type="button" wire:click="openChargesModal" @disabled(! $chargesEditable)
                class="px-3 py-2 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition disabled:opacity-40 disabled:cursor-not-allowed">Charges &amp; Discount</button>
            <button type="button" wire:click="openCustomerModal" @disabled(! $editable)
                class="px-3 py-2 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition disabled:opacity-40 disabled:cursor-not-allowed">Customer &amp; Address</button>
            <button type="button" wire:click="openNotesModal"
                class="px-3 py-2 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">Source &amp; Notes</button>
        </div>

        @unless($editable)
            <p class="text-[11px] text-gray-400 mt-3">
                Items and customer can be edited while the order is Pending, Confirmed or Processing. After that, use returns/refunds.
                @if($chargesEditable)
                    Charges &amp; discount can still be corrected (with a reason, kept in the timeline).
                @endif
            </p>
        @endunless

        @if($order->customer_note || $order->admin_note)
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                @if($order->customer_note)
                    <div>
                        <p class="text-[11px] font-medium text-gray-500">Customer note</p>
                        <p class="text-xs text-gray-700 whitespace-pre-line">{{ $order->customer_note }}</p>
                    </div>
                @endif
                @if($order->admin_note)
                    <div>
                        <p class="text-[11px] font-medium text-gray-500">Admin note</p>
                        <p class="text-xs text-gray-700 whitespace-pre-line">{{ $order->admin_note }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Edit Items Modal --}}
    <div x-cloak x-data="{ open: @entangle('itemsModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Edit Items</h2>
                    <p class="text-xs text-gray-400">Changing a line resets its line discount. Packed lines are locked — Unpack one to edit it.</p>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="saveItems" class="overflow-y-auto px-6 py-5 space-y-4">
                @if($itemsModal)
                    <x-searchable-select wire:key="edit-order-product-picker-{{ count($editItems) }}"
                        field="productPickerId" :value="$productPickerId"
                        :options="$productOptions" :images="$productImages"
                        placeholder="— Add a product —" search-placeholder="Search by name or code…" />
                @endif

                @error('editItems') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                <div class="space-y-3">
                    @foreach($editItems as $i => $line)
                        <div wire:key="edit-line-{{ $line['id'] ?? 'new-' . $i }}" class="rounded-lg border px-4 py-3 {{ $line['locked'] ? 'border-gray-100 bg-gray-50' : 'border-gray-200' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-sm font-medium text-gray-800">{{ $line['label'] }}</span>
                                        @if($line['kind'] === 'combo')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-indigo-50 text-indigo-500">Combo</span>
                                        @endif
                                        @if($line['is_gift'])
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-emerald-50 text-emerald-500">Gift</span>
                                        @endif
                                        @if(! $line['id'])
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-amber-50 text-amber-600">New</span>
                                        @endif
                                        @if($line['locked'])
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-gray-200 text-gray-500">Locked</span>
                                        @endif
                                        @if(! empty($line['unpackable']))
                                            <button type="button" wire:click="unpackLine({{ $i }})" wire:loading.attr="disabled" wire:target="unpackLine({{ $i }})"
                                                wire:confirm="Unpack this item? Its units go back to their batch so you can edit it — pack it again after."
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 transition disabled:opacity-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                                Unpack
                                            </button>
                                        @endif
                                    </div>

                                    @if($line['kind'] === 'product')
                                        @php($variants = $variantOptions->get((int) $line['product_id']))
                                        @if($variants && $variants->isNotEmpty())
                                            <select wire:change="selectVariantForLine({{ $i }}, $event.target.value)" @disabled($line['locked'])
                                                class="mt-1.5 w-56 rounded-lg border border-gray-300 px-2 py-1.5 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-100">
                                                <option value="">— No variant —</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" @selected($line['variant_id'] == $variant->id)>{{ $variant->sku }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    @endif
                                </div>

                                @unless($line['locked'])
                                    <button type="button" wire:click="removeLine({{ $i }})" title="Remove"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endunless
                            </div>

                            <div class="grid grid-cols-4 gap-3 mt-3">
                                <div>
                                    <label class="block text-[11px] text-gray-500 mb-1">Quantity</label>
                                    <input wire:model.blur="editItems.{{ $i }}.quantity" type="number" step="0.001" min="0.001" @disabled($line['locked'])
                                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-100">
                                    @error("editItems.{$i}.quantity") <p class="text-[11px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-500 mb-1">Unit Price</label>
                                    <input wire:model.blur="editItems.{{ $i }}.unit_price" type="number" step="0.01" min="0" @disabled($line['locked'] || $line['is_gift'])
                                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-100">
                                    @error("editItems.{$i}.unit_price") <p class="text-[11px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-500 mb-1">Purchase Price</label>
                                    <input wire:model="editItems.{{ $i }}.purchase_price" type="number" step="0.01" min="0" placeholder="—" @disabled($line['locked'])
                                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-100">
                                </div>
                                <div class="flex items-end justify-end pb-1.5">
                                    <span class="text-sm font-medium text-gray-800">{{ number_format($line['is_gift'] ? 0 : (float) $line['quantity'] * (float) $line['unit_price'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span class="text-sm text-gray-600">Items total: <strong class="text-gray-900">{{ number_format($editItemsTotal, 2) }}</strong></span>
                    <div class="flex items-center gap-2">
                        <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveItems" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-60">Save Items</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Charges & Discount Modal --}}
    <div x-cloak x-data="{ open: @entangle('chargesModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Charges &amp; Discount</h2>
                    <p class="text-xs text-gray-400">Posted to accounts when the order is completed.</p>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="saveCharges" class="overflow-y-auto px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Shipping Charge</label>
                        <input wire:model="shippingAmount" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('shippingAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Tax / VAT</label>
                        <input wire:model="taxAmount" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('taxAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Discount</label>
                        <div class="flex gap-2">
                            <select wire:model.live="discountType" class="w-32 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="fixed">Amount (৳)</option>
                                <option value="percent">Percent (%)</option>
                            </select>
                            <input wire:model="discountValue" type="number" step="0.01" min="0" class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Percent is of the items subtotal ({{ number_format((float) $order->subtotal, 2) }}) and saved as an amount.</p>
                        @error('discountValue') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if(! $editable)
                    {{-- After shipping this is a correction (e.g. the courier's real delivery charge) — reason required, logged. --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Reason for correction <span class="text-red-500">*</span></label>
                        <input wire:model="adjustReason" type="text" maxlength="255" placeholder="e.g. Narayanganj delivery charge is 120, courier collected 2,370"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <p class="text-[11px] text-gray-400 mt-1">Order already {{ strtolower($order->status->label()) }} — the change and this reason are kept in the timeline.</p>
                        @error('adjustReason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($editable && $order->offers->isNotEmpty())
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Applied Offers</label>
                        <div class="space-y-2">
                            @foreach($order->offers as $appliedOffer)
                                <div wire:key="applied-offer-{{ $appliedOffer->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-700 truncate">{{ $appliedOffer->name }}</p>
                                        <p class="text-[11px] text-emerald-600">
                                            @if((float) $appliedOffer->discount_amount > 0) Items −{{ number_format((float) $appliedOffer->discount_amount, 2) }} @endif
                                            @if((float) $appliedOffer->shipping_discount > 0) Shipping −{{ number_format((float) $appliedOffer->shipping_discount, 2) }} @endif
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removeOffer({{ $appliedOffer->id }})"
                                        wire:confirm="Remove offer &quot;{{ $appliedOffer->name }}&quot; from this order? Its discount will be added back to the total."
                                        class="shrink-0 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">Remove</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($editable)
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Coupon <span class="text-gray-400 font-normal">(shipping discount — applies to the saved shipping charge)</span></label>
                    <div class="flex gap-2">
                        <input wire:model="couponCode" type="text" placeholder="Coupon code" @disabled($order->coupon_code)
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50">
                        @if($order->coupon_code)
                            <button type="button" wire:click="removeCoupon" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">Remove</button>
                        @else
                            <button type="button" wire:click="applyCoupon" wire:loading.attr="disabled" wire:target="applyCoupon" class="px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">Apply</button>
                        @endif
                    </div>
                    @if($order->coupon_code)
                        <p class="text-[11px] text-emerald-600 mt-1">{{ $order->coupon_code }} applied — shipping discount {{ number_format((float) $order->shipping_discount, 2) }}</p>
                    @endif
                    @error('couponCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-medium text-gray-600">Extra Charges</label>
                        <button type="button" wire:click="addChargeRow" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">+ Add charge</button>
                    </div>
                    <div class="space-y-2">
                        @forelse($charges as $c => $charge)
                            <div wire:key="charge-row-{{ $c }}">
                                <div class="flex items-center gap-2">
                                    <input wire:model="charges.{{ $c }}.label" type="text" placeholder="e.g. Gift wrap, COD fee"
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <input wire:model="charges.{{ $c }}.amount" type="number" step="0.01" min="0" placeholder="Amount"
                                        class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <button type="button" wire:click="removeChargeRow({{ $c }})" class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                @error("charges.{$c}.label") <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                @error("charges.{$c}.amount") <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">No extra charges.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveCharges" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-60">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Customer & Address Modal --}}
    <div x-cloak x-data="{ open: @entangle('customerModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="if (! $wire.newCustomerModal) open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Customer &amp; Address</h2>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="saveCustomer" class="overflow-y-auto px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Customer</label>
                    <div class="flex items-center gap-2">
                        @if($customerModal)
                            <x-searchable-select wire:key="edit-order-customer-{{ $customerId ?: 'guest' }}" class="flex-1"
                                field="customerId" :value="$customerId" :options="$customerOptions"
                                placeholder="Guest order (no customer)" search-placeholder="Search by name or phone…" />
                        @endif
                        <button type="button" wire:click="openNewCustomerModal" title="Add new customer"
                            class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </button>
                    </div>
                    @error('customerId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                @if($customerId)
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Billing Address</label>
                            <select wire:model="billingAddressId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— None —</option>
                                @foreach($customerAddresses as $address)
                                    <option value="{{ $address->id }}">{{ $address->name }} — {{ Str::limit($address->full_address, 50) }}</option>
                                @endforeach
                            </select>
                            @error('billingAddressId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Shipping Address</label>
                            <select wire:model="shippingAddressId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— None —</option>
                                @foreach($customerAddresses as $address)
                                    <option value="{{ $address->id }}">{{ $address->name }} — {{ Str::limit($address->full_address, 50) }}</option>
                                @endforeach
                            </select>
                            @error('shippingAddressId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveCustomer" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-60">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Notes Modal --}}
    <div x-cloak x-data="{ open: @entangle('notesModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Source &amp; Notes</h2>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="saveNotes" class="overflow-y-auto px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Order Source</label>
                    <select wire:model="source" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach($sources as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    @error('source') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Customer Note</label>
                    <textarea wire:model="customerNote" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Admin Note <span class="text-gray-400 font-normal">(internal)</span></label>
                    <textarea wire:model="adminNote" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveNotes" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-60">Save</button>
                </div>
            </form>
        </div>
    </div>

    @include('livewire.admin.sales.partials.quick-add-customer-modal')
</div>
