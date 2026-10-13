<div>
    @if ($show && $this->order)
        @php
            $order = $this->order;
            $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
        @endphp
        <div class="fixed inset-0 z-[9998] flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4 sm:items-center" x-data x-on:keydown.escape.window="$wire.set('show', false)">
            <div class="my-8 w-full max-w-3xl rounded-2xl bg-white shadow-xl">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-6 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Pack order #{{ $order->id }}</h3>
                        <p class="text-xs text-gray-500">{{ $order->customer?->full_name }} · প্রতিটা product কোন batch থেকে যাচ্ছে বাছুন — stock সেখান থেকেই কাটবে, আর sale-এর cost সেই batch-এর দামে হবে।</p>
                    </div>
                    <button type="button" wire:click="$set('show', false)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="border-b border-gray-100 bg-gray-50/60 px-6 py-3">
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5v15m3-15v15m4.5-15v15m3-15v15m4.5-15v15m-1.5-15v15"/></svg>
                        <input type="text" wire:model="scan" wire:keydown.enter.prevent="scanCode" x-init="$nextTick(() => $el.focus())" autocomplete="off"
                            placeholder="Scan barcode — প্রতিটা piece-এর label scan করুন"
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 font-mono text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    @error('scan') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="max-h-[65vh] space-y-4 overflow-y-auto px-6 py-4">
                    @forelse ($this->items as $item)
                        @php
                            $opts = $this->batches[$item->id] ?? [];
                            $packed = (float) $item->batchAllocations->sum('quantity');
                            $picked = collect($alloc[$item->id] ?? [])->sum(fn ($r) => (float) ($r['quantity'] ?: 0));
                        @endphp
                        <div wire:key="pk-{{ $item->id }}" class="rounded-xl border border-gray-200 p-4">
                            <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $item->product_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->variant_name ?: $item->sku }}</div>
                                </div>
                                <div class="text-right text-xs">
                                    <div class="font-semibold {{ abs($picked - (float) $item->quantity) < 0.0005 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $num($picked) }} / {{ $num($item->quantity) }} picked</div>
                                    @if ($packed > 0)<div class="text-gray-400">now packed {{ $num($packed) }}</div>@endif
                                    @if (! empty($scanned[$item->id]))<div class="text-indigo-600">{{ $scanned[$item->id] }} scanned</div>@endif
                                </div>
                            </div>
                            @if (! $opts)
                                <p class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">এই item-এর কোনো batch-এ stock নেই — আগে Stock In করুন।</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($alloc[$item->id] ?? [] as $i => $row)
                                        <div class="flex items-center gap-2" wire:key="pk-{{ $item->id }}-{{ $i }}">
                                            <select wire:model.live="alloc.{{ $item->id }}.{{ $i }}.batch_id" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <option value="">— Batch —</option>
                                                @foreach ($opts as $b)
                                                    <option value="{{ $b['id'] }}">{{ $b['batch_no'] }} — {{ $num($b['available']) }} available{{ $b['expiry'] ? ' · exp ' . $b['expiry'] : '' }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" min="0" step="0.001" wire:model.live.debounce.400ms="alloc.{{ $item->id }}.{{ $i }}.quantity" placeholder="Qty" class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            <button type="button" wire:click="removeRow({{ $item->id }}, {{ $i }})" class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600" aria-label="Remove">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addRow({{ $item->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">+ Another batch</button>
                                </div>
                            @endif
                            @error("alloc.{$item->id}") <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">এই order-এ pack করার মতো product নেই (gift / combo line আলাদা)।</p>
                    @endforelse
                    @error('alloc') <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <button type="button" wire:click="autoPick" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Auto-pick (earliest expiry first)</button>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('show', false)" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700">Cancel</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Pack</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
