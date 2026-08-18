<div x-data x-init="$store.pageName = { name: 'Cart Detail', slug: 'customers-cart-detail' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.customers.carts') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Cart #{{ $cart->id }}</h1>
                <p class="text-xs text-gray-400">{{ $cart->created_at->format('M d, Y H:i') }}</p>
            </div>
        </div>
        @php
            $statusStyles = [
                'active'    => 'bg-indigo-50 text-indigo-600',
                'converted' => 'bg-emerald-50 text-emerald-600',
                'abandoned' => 'bg-red-50 text-red-500',
            ];
        @endphp
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium capitalize {{ $statusStyles[$cart->status] ?? 'bg-gray-100 text-gray-500' }}">
            {{ $cart->status }}
        </span>
    </div>

    {{-- Customer / Device --}}
    <div class="grid grid-cols-2 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Customer</p>
            <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $cart->customer?->full_name ?? 'Guest' }}</p>
            <p class="text-xs text-gray-400">{{ $cart->customer?->phone ?? '' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Device</p>
            <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $cart->device?->fingerprint ?? '—' }}</p>
            <p class="text-xs text-gray-400">{{ $cart->device?->platform ?? '' }}</p>
        </div>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-4">Items</h2>

        <div class="space-y-3">
            @forelse($cart->items as $item)
                <div class="rounded-lg border border-gray-200 px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            @if($item->combo)
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm font-medium text-gray-800">{{ $item->combo->name ?? 'Custom Combo' }}</span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-indigo-50 text-indigo-500">Combo</span>
                                </div>
                            @elseif($item->product)
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm font-medium text-gray-800">{{ $item->product->name }}</span>
                                    @if($item->is_gift)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-emerald-50 text-emerald-500">Gift</span>
                                    @endif
                                </div>
                                @if($item->variant)
                                    <span class="text-xs text-gray-400 font-mono">{{ $item->variant->sku }}</span>
                                @endif
                            @else
                                <span class="text-sm text-gray-400 italic">Item unavailable</span>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-medium text-gray-800">{{ number_format($item->price, 2) }} × {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</p>
                            <p class="text-xs text-gray-400">{{ number_format($item->price * $item->quantity, 2) }}</p>
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
                </div>
            @empty
                <p class="text-sm text-gray-400">This cart has no items.</p>
            @endforelse
        </div>

        <div class="flex items-center justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
            <span class="text-sm text-gray-500">Subtotal</span>
            <span class="text-lg font-semibold text-gray-900">{{ number_format($cart->subtotal, 2) }}</span>
        </div>
    </div>
</div>
