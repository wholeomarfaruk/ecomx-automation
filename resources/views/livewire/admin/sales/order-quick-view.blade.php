<div>
    {{-- View Details Modal --}}
    <div x-cloak x-data="{ modalOpen: @entangle('viewModal'), loadingId: null }"
        @open-order-view.window="modalOpen = true; loadingId = $event.detail.orderId"
        x-show="modalOpen" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="modalOpen = false">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <h2 class="text-base font-semibold text-gray-900">
                    Order @if($viewingOrder) #{{ $viewingOrder->id }} @endif Details
                </h2>
                <button wire:click="closeViewModal" @click="modalOpen = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div x-show="loadingId && Number($wire.viewOrderId) !== Number(loadingId)" class="px-6 py-10 text-center text-sm text-gray-400">
                <svg class="inline h-4 w-4 animate-spin mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                Loading order…
            </div>
            @if($viewingOrder)
                <div class="px-6 py-5 space-y-5 overflow-y-auto">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $viewingOrder->status->badgeClass() }}">
                            {{ $viewingOrder->status->label() }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $viewingOrder->payment_status->badgeClass() }}">
                            {{ $viewingOrder->payment_status->label() }}
                        </span>
                        @if($viewingOrder->courier_status)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $viewingOrder->courier_status->badgeClass() }}">
                                {{ $viewingOrder->courier_status->label() }}
                            </span>
                        @endif
                        <span class="text-xs text-gray-400 ml-auto">{{ $viewingOrder->created_at->format('d M, Y H:i') }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-gray-200 p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Customer</p>
                            <p class="text-sm text-gray-800">{{ $viewingOrder->customer?->full_name ?? 'Guest' }}</p>
                            <p class="text-xs text-gray-500">{{ $viewingOrder->customer?->phone ?? '—' }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Shipping Address</p>
                            <p class="text-sm text-gray-800">{{ $viewingOrder->shippingAddress?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $viewingOrder->shippingAddress?->full_address ?? '—' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Items ({{ $viewingOrder->items->count() }})</p>
                        <div class="space-y-2">
                            @foreach($viewingOrder->items as $item)
                                <div class="flex items-center justify-between text-sm rounded-lg border border-gray-100 px-3 py-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @php $thumb = $item->variant?->display_image ?? $item->product?->featured_image; @endphp
                                        <div class="w-10 h-10 shrink-0 rounded-md border border-gray-100 bg-gray-50 overflow-hidden flex items-center justify-center">
                                            @if($thumb)
                                                <img src="{{ $thumb }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                            @else
                                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z"/></svg>
                                            @endif
                                        </div>
                                    <div class="min-w-0">
                                        <p class="text-gray-800">{{ $item->product_name }}</p>
                                        @if($item->variant_name)
                                            <p class="text-xs text-gray-400">{{ $item->variant_name }}</p>
                                        @endif
                                    </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-gray-600">{{ $item->quantity }} × {{ number_format($item->unit_price, 2) }}</p>
                                        <p class="text-xs font-medium text-gray-800">{{ number_format($item->total_amount, 2) }}</p>
                                        @if($item->discount_amount > 0)
                                            <p class="text-xs text-emerald-600">Offer −{{ number_format($item->discount_amount, 2) }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="rounded-xl border border-gray-200 p-4 space-y-1.5">
                            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="text-gray-800">{{ number_format($viewingOrder->subtotal, 2) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Discount</span><span class="text-gray-800">-{{ number_format($viewingOrder->discount_amount, 2) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Shipping</span><span class="text-gray-800">{{ number_format($viewingOrder->shipping_amount, 2) }}</span></div>
                            <div class="flex justify-between pt-1.5 border-t border-gray-100"><span class="font-medium text-gray-700">Total</span><span class="font-semibold text-gray-900">{{ number_format($viewingOrder->total_amount, 2) }}</span></div>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4 space-y-1.5">
                            <div class="flex justify-between"><span class="text-gray-500">Paid</span><span class="text-emerald-600">{{ number_format($viewingOrder->paid_amount, 2) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Due</span><span class="{{ $viewingOrder->due_amount > 0 ? 'text-red-500 font-medium' : 'text-gray-800' }}">{{ number_format($viewingOrder->due_amount, 2) }}</span></div>
                            @if($viewingOrder->courierShipments->isNotEmpty())
                                <div class="flex justify-between pt-1.5 border-t border-gray-100">
                                    <span class="text-gray-500">Courier</span>
                                    <span class="text-gray-800">{{ $viewingOrder->courierShipments->first()->courier->name ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Tracking #</span>
                                    <span class="font-mono text-xs text-gray-800">{{ $viewingOrder->courier_tracking_number ?? '—' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($viewingOrder->customer_note)
                        <div class="rounded-xl bg-amber-50 border border-amber-100 p-3">
                            <p class="text-xs font-semibold text-amber-700 mb-1">Customer Note</p>
                            <p class="text-sm text-amber-800">{{ $viewingOrder->customer_note }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0">
                    <button wire:click="closeViewModal" @click="modalOpen = false" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Close
                    </button>
                    <a href="{{ route('admin.sales.orders.show', $viewingOrder->id) }}" wire:navigate
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        Full Details
                    </a>
                </div>
            @else
                <div class="px-6 py-16 text-center text-sm text-gray-400">Loading…</div>
            @endif
        </div>
    </div>
</div>
