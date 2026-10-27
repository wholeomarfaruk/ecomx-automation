<div>
    @if ($show && $this->order)
        @php
            $order = $this->order;
            $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
            $chargesEditable = ! $order->salePosted();
            $lines = $order->items->map(fn ($i) => [
                'id'    => $i->id,
                'qty'   => (float) $i->quantity,
                'total' => $i->is_gift ? 0.0 : (float) $i->total_amount,
                'gift'  => (bool) $i->is_gift,
            ])->values();
        @endphp
        {{-- The sums mirror ReceiveReturn::totalFor() — kept in the browser so toggling is instant; confirm() re-checks on the server. --}}
        <div class="fixed inset-0 z-[9998] flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4 sm:items-center"
            x-on:keydown.escape.window="$wire.set('show', false)"
            x-data="{
                lines: @js($lines),
                itemsTotal: @js((float) $lines->sum('total')),
                shipDisc: @js((float) $order->shipping_discount),
                tax: @js((float) $order->tax_amount),
                extra: @js((float) $order->charges_amount),
                paid: @js((float) $order->paid_amount),
                origShipping: @js((float) $order->shipping_amount),
                origDiscount: @js((float) $order->discount_amount),
                tol: @js(\App\Actions\Sales\ReceiveReturn::TOLERANCE),
                n(v) { return parseFloat(v) || 0 },
                round(v) { return Math.round(v * 100) / 100 },
                money(v) { return v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
                r(l) { return Math.min(Math.max(this.n($wire.returned[l.id]), 0), l.qty) },
                setR(l, v) { $wire.returned[l.id] = String(Math.min(Math.max(v, 0), l.qty)) },
                get shipping() { return Math.max(this.n($wire.shipping), 0) },
                get discount() { return Math.min(Math.max(this.n($wire.discount), 0), this.itemsTotal) },
                get factor() { return this.itemsTotal > 0 ? 1 - this.discount / this.itemsTotal : 1 },
                get returnedNet() { return this.lines.reduce((s, l) => s + (l.gift || l.qty <= 0 ? 0 : l.total * this.r(l) / l.qty), 0) * this.factor },
                get keptGross() { return this.lines.reduce((s, l) => s + (l.gift || l.qty <= 0 ? 0 : l.total * (l.qty - this.r(l)) / l.qty), 0) },
                get netShipping() { return this.shipping - Math.max(0, Math.min(this.shipping, this.shipDisc)) },
                get total() { return this.round(this.itemsTotal - this.discount - this.returnedNet + this.netShipping + this.tax + this.extra) },
                get diff() { return this.round(this.paid - this.total) },
                get matches() { return Math.abs(this.diff) <= this.tol },
                get allBack() { return this.lines.every(l => this.r(l) >= l.qty) },
                get due() { return this.allBack ? 0 : Math.max(0, this.round(this.total - this.paid)) },
                get chargesChanged() { return Math.abs(this.shipping - this.origShipping) > 0.004 || Math.abs(this.discount - this.origDiscount) > 0.004 },
                {{-- Delivery charge that makes the total equal what was paid. --}}
                get fixShipping() { const x = this.round(this.shipping + this.diff); return ! this.matches && x >= 0 ? x : null },
                {{-- Discount that brings the kept items down to what was paid: total = kept − d·kept/itemsTotal + rest. --}}
                get fixDiscount() {
                    if (this.matches || this.diff > 0 || this.keptGross <= 0) return null;
                    const d = this.round((this.keptGross + this.netShipping + this.tax + this.extra - this.paid) * this.itemsTotal / this.keptGross);
                    return d >= 0 && d <= this.itemsTotal ? d : null;
                },
                useShipping() {
                    const x = this.fixShipping;
                    $wire.shipping = String(x);
                    if (! $wire.reason) $wire.reason = 'Courier collected ' + this.money(this.paid) + ' — delivery charge ' + this.money(this.origShipping) + ' → ' + this.money(x);
                },
                useDiscount() {
                    const d = this.fixDiscount;
                    $wire.discount = String(d);
                    if (! $wire.reason) $wire.reason = 'Courier collected ' + this.money(this.paid) + ' — discount ' + this.money(this.origDiscount) + ' → ' + this.money(d);
                },
            }">
            <div class="my-8 w-full max-w-2xl rounded-2xl bg-white shadow-xl">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-6 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Receive return — Order #{{ $order->id }}</h3>
                        <p class="text-xs text-gray-500">{{ $order->customer?->full_name }}{{ $order->customer?->phone ? ' · ' . $order->customer->phone : '' }}</p>
                    </div>
                    <button type="button" wire:click="$set('show', false)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-4">
                    {{-- Why this opened --}}
                    @if ($issue)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
                            <p>{{ $issue }}</p>
                            @if ($suggested)
                                <p class="mt-1 text-xs text-amber-700">Kept / Returned below is suggested from the amount collected — check it against the parcel.</p>
                            @endif
                        </div>
                    @endif

                    {{-- Items --}}
                    <div>
                        <p class="mb-2 text-xs font-medium text-gray-600">What came back?</p>
                        <div class="divide-y divide-gray-100 rounded-xl border border-gray-200">
                            @foreach ($order->items as $item)
                                @php $thumb = $item->variant?->display_image ?? $item->product?->featured_image; @endphp
                                <div wire:key="rr-item-{{ $item->id }}" class="flex flex-wrap items-center gap-3 px-3 py-2.5"
                                    :class="r(lines[{{ $loop->index }}]) >= lines[{{ $loop->index }}].qty ? 'bg-red-50/40' : ''">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-gray-50">
                                        @if ($thumb)
                                            <img src="{{ $thumb }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm text-gray-800">{{ $item->product_name }}@if ($item->is_gift) <span class="text-[10px] font-medium uppercase text-emerald-500">Gift</span>@endif</p>
                                        <p class="text-xs text-gray-400">{{ $item->variant_name ? $item->variant_name . ' · ' : '' }}{{ number_format((float) $item->total_amount, 2) }} × {{ $num($item->quantity) }}</p>
                                    </div>
                                    @if ((float) $item->quantity <= 1)
                                        <div class="inline-flex rounded-lg border border-gray-200 p-0.5 text-xs font-medium">
                                            <button type="button" @click="setR(lines[{{ $loop->index }}], 0)" class="rounded-md px-3 py-1 transition"
                                                :class="r(lines[{{ $loop->index }}]) === 0 ? 'bg-emerald-50 text-emerald-700' : 'text-gray-500 hover:text-gray-700'">Kept</button>
                                            <button type="button" @click="setR(lines[{{ $loop->index }}], lines[{{ $loop->index }}].qty)" class="rounded-md px-3 py-1 transition"
                                                :class="r(lines[{{ $loop->index }}]) >= lines[{{ $loop->index }}].qty ? 'bg-red-50 text-red-600' : 'text-gray-500 hover:text-gray-700'">Returned</button>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-2 text-xs">
                                            <span class="text-gray-500">Returned</span>
                                            <div class="inline-flex items-center rounded-lg border border-gray-200">
                                                <button type="button" @click="setR(lines[{{ $loop->index }}], r(lines[{{ $loop->index }}]) - 1)" :disabled="r(lines[{{ $loop->index }}]) <= 0"
                                                    class="h-7 w-7 rounded-l-lg text-gray-500 hover:bg-gray-50 disabled:opacity-40">−</button>
                                                <span class="w-8 text-center font-medium text-gray-800" x-text="r(lines[{{ $loop->index }}])"></span>
                                                <button type="button" @click="setR(lines[{{ $loop->index }}], r(lines[{{ $loop->index }}]) + 1)" :disabled="r(lines[{{ $loop->index }}]) >= lines[{{ $loop->index }}].qty"
                                                    class="h-7 w-7 rounded-r-lg text-gray-500 hover:bg-gray-50 disabled:opacity-40">+</button>
                                            </div>
                                            <span class="text-gray-400">of {{ $num($item->quantity) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Delivery charge / discount --}}
                    @if ($chargesEditable)
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="rr-shipping" class="mb-1.5 block text-xs font-medium text-gray-600">Delivery charge</label>
                                <input id="rr-shipping" wire:model="shipping" type="number" step="0.01" min="0"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <button type="button" x-show="fixShipping !== null" x-cloak @click="useShipping()"
                                    class="mt-1 text-xs font-medium text-indigo-600 hover:text-indigo-700"
                                    x-text="'Match collected → ' + money(fixShipping ?? 0)"></button>
                                @error('shipping') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="rr-discount" class="mb-1.5 block text-xs font-medium text-gray-600">Discount (৳)</label>
                                <input id="rr-discount" wire:model="discount" type="number" step="0.01" min="0"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <button type="button" x-show="fixDiscount !== null" x-cloak @click="useDiscount()"
                                    class="mt-1 text-xs font-medium text-indigo-600 hover:text-indigo-700"
                                    x-text="'Cover the difference → ' + money(fixDiscount ?? 0)"></button>
                                @error('discount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2" x-show="chargesChanged" x-cloak>
                                <label for="rr-reason" class="mb-1.5 block text-xs font-medium text-gray-600">Reason <span class="text-red-500">*</span></label>
                                <input id="rr-reason" wire:model="reason" type="text" maxlength="255" placeholder="e.g. Narayanganj delivery charge is 120"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <p class="mt-1 text-[11px] text-gray-400">Kept in the order's timeline with the old and new amounts.</p>
                            </div>
                        </div>
                    @else
                        <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">This order's sale is already posted to accounts — the delivery charge and discount can't change here. Refund any difference from the order page.</p>
                    @endif

                    {{-- Live result --}}
                    <div class="rounded-xl border px-4 py-3 text-sm" :class="matches ? 'border-emerald-200 bg-emerald-50/60' : 'border-gray-200 bg-gray-50'">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1">
                            <span class="text-gray-500">Total <span class="font-semibold text-gray-900" x-text="money(total)"></span></span>
                            <span class="text-gray-500">Paid <span class="font-semibold text-gray-900" x-text="money(paid)"></span></span>
                            <span x-show="matches" class="font-medium text-emerald-600">✓ Matches what was paid</span>
                            <span x-show="! matches && diff < 0 && ! allBack" x-cloak class="font-medium text-red-500" x-text="money(-diff) + ' will stay due'"></span>
                            <span x-show="! matches && diff < 0 && allBack" x-cloak class="font-medium text-amber-600" x-text="money(-diff) + ' less than the delivery charge — not owed once returned'"></span>
                            <span x-show="! matches && diff > 0" x-cloak class="font-medium text-amber-600" x-text="'Customer paid ' + money(diff) + ' more — refund it from the order page'"></span>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">
                            Order becomes <span class="font-medium text-gray-700" x-text="allBack ? 'Returned' : 'Delivered (partial)'"></span>
                            · payment <span class="font-medium text-gray-700" x-text="paid <= 0 ? (allBack ? 'Failed' : 'unchanged') : (due > 0 ? 'Partial' : 'Paid')"></span>
                        </p>
                    </div>

                    @error('receive') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('returned.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Footer --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-6 py-4">
                    <a href="{{ route('admin.sales.orders.show', $order->id) }}" wire:navigate class="text-xs font-medium text-gray-500 hover:text-gray-700">Open full order →</a>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="$set('show', false)"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="confirm" wire:loading.attr="disabled" wire:target="confirm"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">Confirm return received</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
