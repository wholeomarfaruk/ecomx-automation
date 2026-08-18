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
        <div class="flex items-center gap-2">
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

                            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2">
                                <label class="text-xs text-gray-500 shrink-0">Returned Qty</label>
                                <input wire:model="returnedQuantities.{{ $item->id }}" type="number" step="0.001" min="0" max="{{ $item->quantity }}"
                                    class="w-24 rounded-lg border border-gray-300 px-2 py-1 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <span class="text-xs text-gray-400">of {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
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
                    <div class="flex items-center gap-8 text-sm">
                        <span class="text-gray-500">Shipping</span>
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
                    <button wire:click="openPaymentModal" type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add Payment
                    </button>
                </div>

                <div class="space-y-2">
                    @forelse($order->payments as $payment)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-2.5">
                            <div>
                                <span class="text-sm text-gray-700 capitalize">{{ $payment->payment_method }}</span>
                                @if($payment->transaction_id)
                                    <span class="text-xs text-gray-400 font-mono ml-2">{{ $payment->transaction_id }}</span>
                                @endif
                                <span class="block text-xs text-gray-400">{{ $payment->paid_at?->format('d M, Y H:i') ?? $payment->created_at->format('d M, Y H:i') }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($payment->amount, 2) }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $payment->status->badgeClass() }} ml-2">
                                    {{ $payment->status->label() }}
                                </span>
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
        </div>

        {{-- Right --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">

            {{-- Customer --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Customer</h2>
                <p class="text-sm font-medium text-gray-800">{{ $order->customer?->full_name ?? 'Guest' }}</p>
                <p class="text-xs text-gray-400">{{ $order->customer?->phone ?? '' }}</p>

                @if($order->shippingAddress)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-1">Shipping Address</p>
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
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Courier</h2>
                <div class="space-y-3">
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
            </div>
        </div>
    </div>

    {{-- Payment Modal --}}
    <div x-cloak x-data="{ open: @entangle('paymentModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Add Payment</h2>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="addPayment" class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Method</label>
                    <input wire:model="paymentMethod" type="text" placeholder="cash, card, bkash, nagad…"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('paymentMethod') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Transaction ID</label>
                    <input wire:model="transactionId" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
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
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
