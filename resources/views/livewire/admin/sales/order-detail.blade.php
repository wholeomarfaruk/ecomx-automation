<div x-data x-init="$store.pageName = { name: 'Order Detail', slug: 'sales-order-detail' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sales.orders') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Order #{{ $order->id }}</h1>
                <p class="text-xs text-gray-400">{{ $order->created_at->format('M d, Y H:i') }} · {{ $order->source->label() }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.sales.orders.print', [$order->id, 'invoice']) }}" target="_blank"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500 border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition">Invoice</a>
            <a href="{{ route('admin.sales.orders.print', [$order->id, 'packing-slip']) }}" target="_blank"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500 border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition">Packing Slip</a>
            <button type="button" wire:click="duplicateOrder" wire:confirm="Create a new Pending order with the same customer, items and charges?"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500 border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition">Duplicate</button>
            @if(auth()->user()?->hasRole('superadmin') || auth()->user()?->can('order.edit'))
                <button type="button" @click="$dispatch('open-order-notification', { orderId: {{ $order->id }} })"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500 border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                    </svg>
                    Send Notification
                </button>
            @endif
            <a href="{{ route('admin.accounts.reports.order-ledger', ['orderId' => $order->id]) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500 border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Ledger
            </a>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium {{ $order->payment_status->badgeClass() }}">
                {{ $order->payment_status->label() }}
            </span>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium {{ $order->status->badgeClass() }}">
                {{ $order->status->label() }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- Left --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">

            {{-- Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Items</h2>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                        <div class="rounded-lg border border-gray-200 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-medium text-gray-800">{{ $item->product_name }}</span>
                                        @if($item->is_gift)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-emerald-50 text-emerald-500">Gift</span>
                                        @endif
                                        @if($item->combo_id)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-indigo-50 text-indigo-500">Combo</span>
                                        @endif
                                    </div>
                                    @if($item->variant_name)
                                        <span class="text-xs text-gray-400 font-mono">{{ $item->variant_name }}</span>
                                    @endif
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-medium text-gray-800">{{ number_format($item->unit_price, 2) }} × {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</p>
                                    <p class="text-xs text-gray-400">{{ number_format($item->total_amount, 2) }}</p>
                                    @if($item->discount_amount > 0)
                                        <p class="text-xs text-emerald-600">Offer −{{ number_format($item->discount_amount, 2) }}</p>
                                    @endif
                                </div>
                            </div>

                            @if($item->combo && $item->combo->items->isNotEmpty())
                                <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                                    @foreach($item->combo->items as $comboItem)
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span>
                                                {{ $comboItem->product->name ?? 'Unknown product' }}
                                                @if($comboItem->variant)
                                                    <span class="font-mono text-gray-400">({{ $comboItem->variant->sku }})</span>
                                                @endif
                                            </span>
                                            <span>{{ number_format($comboItem->price, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-x-6 gap-y-2">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-500 shrink-0">Delivered Qty</label>
                                    @if($item->batchAllocations->isNotEmpty())
                                        <span class="text-xs font-medium text-gray-700">{{ rtrim(rtrim(number_format($item->delivered_quantity, 3), '0'), '.') }}</span>
                                        <span class="text-xs text-gray-400">of {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }} (packed)</span>
                                    @else
                                        <input wire:model="deliveredQuantities.{{ $item->id }}" type="number" step="0.001" min="0" max="{{ $item->quantity }}"
                                            class="w-24 rounded-lg border border-gray-300 px-2 py-1 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        <span class="text-xs text-gray-400">of {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-500 shrink-0">Returned Qty</label>
                                    <input wire:model="returnedQuantities.{{ $item->id }}" type="number" step="0.001" min="0" max="{{ $item->quantity }}"
                                        class="w-24 rounded-lg border border-gray-300 px-2 py-1 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <span class="text-xs text-gray-400">of {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</span>
                                </div>
                                @if($item->product_id)
                                    <button wire:click="openPackModal({{ $item->id }})" type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375C2.754 3.75 2.25 4.254 2.25 4.875v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                        </svg>
                                        {{ $item->batchAllocations->isNotEmpty() ? 'Re-pack' : 'Pack from Batch' }}
                                    </button>
                                @endif
                            </div>

                            @if($item->batchAllocations->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($item->batchAllocations as $allocation)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600">
                                            {{ $allocation->batch->batch_no ?? '—' }} ({{ rtrim(rtrim(number_format($allocation->quantity, 3), '0'), '.') }} @ {{ number_format($allocation->unit_cost, 2) }})
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
                    <button wire:click="saveDeliveries" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Save Deliveries
                    </button>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Return / RTO Charge</label>
                        <input wire:model="returnCharge" type="number" step="0.01" min="0" placeholder="0.00"
                            class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Paid From</label>
                        <x-searchable-select field="returnChargeCashAccountId" :value="$returnChargeCashAccountId"
                            :options="$cashAccounts->mapWithKeys(fn ($a) => [(string) $a->id => $a->code . ' — ' . $a->name])"
                            placeholder="— Select account —" search-placeholder="Search accounts…" />
                        @error('returnChargeCashAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button wire:click="saveReturns" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Save Returns
                    </button>
                </div>

                <div class="flex flex-col items-end gap-1 mt-4 pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-8 text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="text-gray-700 font-medium">{{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-8 text-sm">
                        <span class="text-gray-500">Discount</span>
                        <span class="text-gray-700">−{{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                    @foreach($order->offers as $appliedOffer)
                        <div class="flex items-center gap-8 text-xs">
                            <span class="text-gray-400" title="Already deducted in the item totals / shipping discount">Offer applied: {{ $appliedOffer->name }}</span>
                            <span class="text-emerald-600">−{{ number_format($appliedOffer->discount_amount + $appliedOffer->shipping_discount, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-8 text-sm">
                        <span class="text-gray-500">
                            Shipping
                            @if(! empty($order->shipping_meta['zone']))
                                <span class="text-gray-400">({{ $order->shipping_meta['zone'] }} · {{ $order->shipping_meta['method'] }})</span>
                            @endif
                        </span>
                        <span class="text-gray-700">+{{ number_format($order->shipping_amount, 2) }}</span>
                    </div>
                    @if($order->shipping_discount > 0)
                        <div class="flex items-center gap-8 text-sm">
                            <span class="text-gray-500">Shipping Discount{{ $order->coupon_code ? " ({$order->coupon_code})" : '' }}</span>
                            <span class="text-emerald-600">−{{ number_format($order->shipping_discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-8 text-sm">
                        <span class="text-gray-500">Tax</span>
                        <span class="text-gray-700">+{{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    @foreach($order->charges as $charge)
                        <div class="flex items-center gap-8 text-sm">
                            <span class="text-gray-500">{{ $charge->label }}</span>
                            <span class="text-gray-700">+{{ number_format($charge->amount, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-8 text-base pt-1.5 border-t border-gray-100 mt-1">
                        <span class="font-semibold text-gray-800">Total</span>
                        <span class="font-bold text-indigo-600">{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payments --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Payments</h2>
                    <div class="flex items-center gap-2">
                        @if($order->paid_amount > 0)
                            <button wire:click="openRefundModal" type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                                </svg>
                                Refund
                            </button>
                        @endif
                        <button wire:click="openPaymentModal" type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Add Payment
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    @forelse($order->payments as $payment)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-2.5">
                            <div>
                                <span class="text-sm text-gray-700">{{ $payment->payment_method?->label() ?? '—' }}</span>
                                @if($payment->type->value === 'refund')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-50 text-red-500 ml-1">Refund</span>
                                @endif
                                @if($payment->transaction_id)
                                    <span class="text-xs text-gray-400 font-mono ml-2">{{ $payment->transaction_id }}</span>
                                @endif
                                <span class="block text-xs text-gray-400">{{ $payment->paid_at?->format('d M, Y H:i') ?? $payment->created_at->format('d M, Y H:i') }}</span>
                                @if($payment->note)
                                    <span class="block text-xs text-gray-500 mt-0.5 whitespace-pre-line">{{ $payment->note }}</span>
                                @endif
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-medium {{ $payment->type->value === 'refund' ? 'text-red-500' : 'text-gray-800' }}">
                                    {{ $payment->type->value === 'refund' ? '−' : '' }}{{ number_format($payment->amount, 2) }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $payment->status->badgeClass() }} ml-2">
                                    {{ $payment->status->label() }}
                                </span>
                                @if($payment->type->value === 'payment')
                                    <div class="flex items-center justify-end gap-1.5 mt-1.5">
                                        @if($payment->status->value !== 'paid')
                                            <button type="button" wire:click="confirmPayment({{ $payment->id }})" wire:loading.attr="disabled"
                                                wire:confirm="Confirm this payment of {{ number_format($payment->amount, 2) }} as received?"
                                                class="px-2 py-0.5 text-[11px] font-medium text-emerald-700 bg-emerald-50 rounded hover:bg-emerald-100 transition">Confirm</button>
                                        @endif
                                        <button type="button" wire:click="editPayment({{ $payment->id }})"
                                            class="px-2 py-0.5 text-[11px] font-medium text-indigo-600 bg-indigo-50 rounded hover:bg-indigo-100 transition">Edit</button>
                                        @if(! in_array($payment->status->value, ['paid', 'failed']))
                                            <button type="button" wire:click="rejectPayment({{ $payment->id }})" wire:loading.attr="disabled"
                                                wire:confirm="Reject this payment? It will be marked Failed."
                                                class="px-2 py-0.5 text-[11px] font-medium text-red-600 bg-red-50 rounded hover:bg-red-100 transition">Reject</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No payments recorded yet.</p>
                    @endforelse
                </div>

                <div class="flex items-center justify-end gap-8 mt-4 pt-4 border-t border-gray-100 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Paid</span>
                        <span class="font-medium text-emerald-600">{{ number_format($order->paid_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Due</span>
                        <span class="font-medium {{ $order->due_amount > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ number_format($order->due_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Timeline (activity log for this order) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Timeline</h2>
                    @if(auth()->user()?->hasRole('superadmin') || auth()->user()?->can('order.edit'))
                        <button type="button" wire:click="sendPurchaseToMeta({{ $order->id }})" wire:loading.attr="disabled" wire:target="sendPurchaseToMeta"
                            wire:confirm="Send a Purchase event for Order #{{ $order->id }} to Meta Conversions API (action source: chat)?"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                            </svg>
                            Send Purchase to Meta
                        </button>
                    @endif
                </div>
                <ol class="relative border-l border-gray-200 ml-2 space-y-4">
                    @foreach($timeline as $entry)
                        <li class="ml-4" x-data="{ open: false }">
                            <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full border-2 border-white {{ match ($entry->event) { 'created', 'capi_sent' => 'bg-emerald-500', 'capi_failed' => 'bg-red-500', default => 'bg-indigo-400' } }}"></span>
                            <p class="text-sm text-gray-800">{{ $entry->description }}</p>
                            <p class="text-xs text-gray-400">
                                {{ local_time($entry->created_at)?->format('d M Y, h:i A') }}
                                · {{ $entry->causer?->name ?? 'System' }}
                                @if($entry->properties->has('changes'))
                                    · <button type="button" @click="open = !open" class="text-indigo-500 hover:text-indigo-600" x-text="open ? 'Hide details' : 'Details'"></button>
                                @endif
                            </p>
                            @if($entry->properties->has('changes'))
                                <pre x-show="open" x-cloak class="mt-2 p-2 rounded-lg bg-gray-50 text-[11px] text-gray-600 whitespace-pre-wrap break-words">{{ json_encode($entry->properties->get('changes'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            @endif
                        </li>
                    @endforeach
                    <li class="ml-4">
                        <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full border-2 border-white bg-gray-400"></span>
                        <p class="text-sm text-gray-800">Order placed ({{ $order->source->label() }})</p>
                        <p class="text-xs text-gray-400">{{ local_time($order->placed_at ?? $order->created_at)?->format('d M Y, h:i A') }}</p>
                    </li>
                </ol>
            </div>
        </div>

        {{-- Right --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">
            @livewire('admin.sales.order-editor', ['orderId' => $order->id], key('order-editor-' . $order->id))


            {{-- Customer --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-800">Customer</h2>
                    @if($order->customer)
                        <a href="{{ route('admin.accounts.reports.customer-ledger', ['customerId' => $order->customer->id]) }}"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition">
                            View Ledger →
                        </a>
                    @endif
                </div>
                <p class="text-sm font-medium text-gray-800">{{ $order->customer?->full_name ?? 'Guest' }}</p>
                <p class="text-xs text-gray-400">{{ $order->customer?->phone ?? '' }}</p>

                @if($order->shippingAddress)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-1">Shipping Address</p>
                        <p class="text-xs font-medium text-gray-800">{{ $order->shippingAddress->name }}</p>
                        <p class="text-xs text-gray-400 mb-1">{{ $order->shippingAddress->phone }}</p>
                        <p class="text-xs text-gray-600">{{ $order->shippingAddress->full_address }}</p>
                    </div>
                @endif
            </div>

            {{-- Status --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Status</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Order Status</label>
                        <select wire:model="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($statuses as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Status</label>
                        <select wire:model="paymentStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($paymentStatuses as $ps)
                                <option value="{{ $ps->value }}">{{ $ps->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Fulfillment Status</label>
                        <select wire:model="fulfillmentStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($fulfillmentStatuses as $fs)
                                <option value="{{ $fs->value }}">{{ $fs->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button wire:click="updateStatus" type="button"
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        Update Status
                    </button>
                </div>
            </div>

            {{-- Courier --}}
            <div id="courier" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 scroll-mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Courier</h2>
                    @if($canManageCourier && $order->courierShipments->isEmpty())
                        <button @click="$dispatch('open-courier-booking', { orderId: {{ $orderId }} })" type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Book Shipment
                        </button>
                    @endif
                </div>

                @if($order->courierShipments->isNotEmpty())
                    {{-- Booked shipment history — an order can have more than one
                         attempt (e.g. a failed SteadFast booking followed by a
                         successful Pathao one), so every attempt is shown, newest first. --}}
                    <div class="space-y-4">
                        @foreach($order->courierShipments as $shipment)
                            @php
                                $statusEnum = $shipment->statusEnum();
                                $courierCaps = $shipment->courier->capabilities ?? [];
                            @endphp
                            <div class="rounded-xl border border-gray-200 p-4">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $shipment->courier->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $shipment->courierAccount->name ?? '—' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium {{ $statusEnum->badgeClass() }}">
                                        {{ $statusEnum->label() }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-3 text-xs mb-3">
                                    <div>
                                        <p class="text-gray-400">Tracking Number</p>
                                        <p class="font-mono text-gray-700">{{ $shipment->tracking_number ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-400">Consignment ID</p>
                                        <p class="font-mono text-gray-700">{{ $shipment->consignment_id ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-400">COD Amount</p>
                                        <p class="text-gray-700">{{ number_format((float) $shipment->cod_amount, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-400">Booked</p>
                                        <p class="text-gray-700">{{ $shipment->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>

                                @if($trackingUrl = $shipment->trackingUrl())
                                    <div x-data="{ copied: false }" class="flex items-center gap-2 rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 mb-3 text-xs">
                                        <span class="text-gray-400 shrink-0">Tracking link</span>
                                        <a href="{{ $trackingUrl }}" target="_blank" rel="noopener" class="font-mono text-indigo-600 hover:underline truncate">{{ $trackingUrl }}</a>
                                        <button type="button" class="ml-auto shrink-0 px-2 py-0.5 rounded font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition"
                                            @click="navigator.clipboard.writeText(@js($trackingUrl)); copied = true; setTimeout(() => copied = false, 1500)"
                                            x-text="copied ? 'Copied' : 'Copy'">Copy</button>
                                    </div>
                                @endif

                                @if($shipment->error_message)
                                    <p class="text-xs text-red-500 bg-red-50 rounded-lg px-3 py-2 mb-3">{{ $shipment->error_message }}</p>
                                @endif

                                @if($canManageCourier && ! in_array($statusEnum, [\App\Enums\Sales\CourierStatus::CANCELLED, \App\Enums\Sales\CourierStatus::DELIVERED], true))
                                    <div class="flex items-center gap-2 mb-3">
                                        @if($courierCaps['status_sync'] ?? false)
                                            <button wire:click="syncCourierShipment({{ $shipment->id }})" wire:loading.attr="disabled" type="button"
                                                class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                                Sync Tracking
                                            </button>
                                        @endif
                                        @if($courierCaps['shipment_cancel'] ?? false)
                                            <button wire:click="cancelCourierShipment({{ $shipment->id }})" wire:loading.attr="disabled" type="button"
                                                wire:confirm="Cancel this shipment?"
                                                class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                                Cancel Shipment
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                @if($shipment->trackingEvents->isNotEmpty())
                                    <details class="mt-1">
                                        <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700 font-medium">
                                            Tracking Timeline ({{ $shipment->trackingEvents->count() }})
                                        </summary>
                                        <div class="mt-3 space-y-3 border-l-2 border-gray-100 pl-4">
                                            @foreach($shipment->trackingEvents->sortByDesc('event_at') as $event)
                                                @php $eventStatus = \App\Enums\Sales\CourierStatus::tryFrom($event->status); @endphp
                                                <div class="relative">
                                                    <span class="absolute -left-5 top-1 w-2 h-2 rounded-full bg-indigo-400"></span>
                                                    <p class="text-xs font-medium text-gray-700">{{ $eventStatus?->label() ?? $event->status }}</p>
                                                    @if($event->message)
                                                        <p class="text-xs text-gray-500">{{ $event->message }}</p>
                                                    @endif
                                                    @if($event->location)
                                                        <p class="text-[11px] text-gray-400">{{ $event->location }}</p>
                                                    @endif
                                                    <p class="text-[11px] text-gray-400">{{ $event->event_at?->format('M d, Y H:i') }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($canManageCourier)
                        <button @click="$dispatch('open-courier-booking', { orderId: {{ $orderId }} })" type="button"
                            class="w-full mt-4 px-4 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            Book with a different courier
                        </button>
                    @endif
                @else
                    <p class="text-xs text-gray-400 mb-4">No courier shipment has been booked for this order yet.</p>
                @endif

                {{-- Manual override — for couriers without a driver yet (e.g.
                     Sundarban, SA Paribahan) or correcting a value by hand. --}}
                <details class="mt-5 pt-4 border-t border-gray-100">
                    <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600 font-medium">Manual override</summary>
                    <div class="space-y-3 mt-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Provider</label>
                            <input wire:model="courierProvider" type="text" placeholder="e.g. Pathao, Steadfast, RedX"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Tracking Number</label>
                            <input wire:model="courierTrackingNumber" type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Courier Charge</label>
                            <input wire:model="courierCharge" type="number" step="0.01" min="0"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Courier Status</label>
                            <select wire:model="courierStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— Not set —</option>
                                @foreach($courierStatuses as $cs)
                                    <option value="{{ $cs->value }}">{{ $cs->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button wire:click="updateCourier" type="button"
                            class="w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                            Update Courier
                        </button>

                        @if($order->courier_meta)
                            <details class="mt-2">
                                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">Raw courier data</summary>
                                <pre class="mt-2 text-[10px] bg-gray-50 rounded-lg p-3 overflow-x-auto text-gray-600">{{ json_encode($order->courier_meta, JSON_PRETTY_PRINT) }}</pre>
                            </details>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </div>

    {{-- Payment Modal --}}
    <div x-cloak x-data="{ open: @entangle('paymentModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">{{ $editingPaymentId ? 'Edit Payment' : 'Add Payment' }}</h2>
                    @if($editingPaidPayment)
                        <p class="text-xs text-gray-400 mt-0.5">Already paid — only the transaction ID and note can be changed.</p>
                    @endif
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="savePayment" class="px-6 py-5 space-y-4">
                @unless($editingPaidPayment)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Method <span class="text-red-500">*</span></label>
                        <select wire:model="paymentMethod" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->value }}">{{ $pm->label() }}</option>
                            @endforeach
                        </select>
                        @error('paymentMethod') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @if($accountsEnabled)
                        <div wire:key="payment-account-{{ $editingPaymentId ?? 'new' }}">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Received Into <span class="text-red-500">*</span></label>
                            <x-searchable-select field="paymentAccountId" :value="$paymentAccountId"
                                :options="$cashAccounts->mapWithKeys(fn ($a) => [(string) $a->id => $a->code . ' — ' . $a->name])"
                                placeholder="— Select cash/bank account —" search-placeholder="Search accounts…" />
                            @error('paymentAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                @endunless
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Transaction ID</label>
                    <input wire:model="transactionId" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('transactionId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                @unless($editingPaidPayment)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Amount</label>
                        <input wire:model="paymentAmount" type="number" step="0.01" min="0.01"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('paymentAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="paymentStatusNew" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($paymentStatuses as $ps)
                                <option value="{{ $ps->value }}">{{ $ps->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Note</label>
                    <textarea wire:model="paymentNote" rows="2" placeholder="Optional"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('paymentNote') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">{{ $editingPaymentId ? 'Save Payment' : 'Add Payment' }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Refund Modal --}}
    <div x-cloak x-data="{ open: @entangle('refundModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Refund</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Up to {{ number_format($order->paid_amount, 2) }} paid so far.</p>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="refundOrder" class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Refund Amount <span class="text-red-500">*</span></label>
                    <input wire:model="refundAmount" type="number" step="0.01" min="0.01" max="{{ $order->paid_amount }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('refundAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input wire:model.live="refundAsStoreCredit" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Refund as store credit instead of cash</span>
                    </label>
                    <p class="text-xs text-gray-400 mt-1">Credits the customer's balance for a future order — no cash moves out.</p>
                </div>

                @unless($refundAsStoreCredit)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Refund From <span class="text-red-500">*</span></label>
                        <x-searchable-select field="refundCashAccountId" :value="$refundCashAccountId"
                            :options="$cashAccounts->mapWithKeys(fn ($a) => [(string) $a->id => $a->code . ' — ' . $a->name])"
                            placeholder="— Select cash/bank account —" search-placeholder="Search accounts…" />
                        @error('refundCashAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endunless

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Note</label>
                    <input wire:model="refundNote" type="text" placeholder="Reason for the refund"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('refundNote') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Record Refund</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Pack Items Modal --}}
    <div x-cloak x-data="{ open: @entangle('packModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-visible" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 rounded-t-2xl overflow-hidden">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Pack from Batch</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Pick which batch(es) this shipment's units came from.</p>
                </div>
                <button wire:click="closePackModal" @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="savePacking" class="px-6 py-5 space-y-3">
                @error('packAllocations') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                @foreach($packAllocations as $index => $row)
                    <div class="flex items-end gap-2" wire:key="pack-row-{{ $index }}">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Batch</label>
                            <x-searchable-select field="packAllocations.{{ $index }}.batch_id" :value="$row['batch_id']"
                                :options="$packableBatches->mapWithKeys(fn ($b) => [(string) $b->id => $b->batch_no . ' — ' . rtrim(rtrim(number_format($b->quantity, 3), '0'), '.') . ' left @ ' . number_format($b->purchase_price ?? 0, 2)])"
                                placeholder="— Select batch —" search-placeholder="Search batches…" />
                        </div>
                        <div class="w-28">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Quantity</label>
                            <input wire:model="packAllocations.{{ $index }}.quantity" type="number" step="0.001" min="0"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        @if(count($packAllocations) > 1)
                            <button wire:click="removePackRow({{ $index }})" type="button"
                                class="mb-0.5 w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach

                <button wire:click="addPackRow" type="button"
                    class="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition">
                    + Add another batch
                </button>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                    <button wire:click="closePackModal" @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Save Packing</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Confirm Cancel/Return with Payment on Order --}}
    <div x-cloak x-data="{ open: @entangle('confirmReverseModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">This order has a payment</h2>
                </div>
                <button wire:click="cancelReverseModal" @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-3">
                <p class="text-sm text-gray-700">
                    The customer has paid <span class="font-semibold">{{ number_format($order->paid_amount, 2) }}</span> on this order.
                </p>
                <p class="text-sm text-gray-600">
                    If you cancel/return it, the sale and cost will be reversed and the paid amount will be credited to the customer's account (store credit) — no cash will be refunded automatically.
                </p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100">
                <button wire:click="cancelReverseModal" @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="confirmReverseAndUpdateStatus" type="button" class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Confirm, Force Cancel/Return</button>
            </div>
        </div>
    </div>

    {{-- Courier booking: its own component, so it doesn't re-render this whole page. --}}
    <livewire:admin.sales.courier-booking-modal />
    <livewire:admin.sales.send-order-notification-modal />
</div>
