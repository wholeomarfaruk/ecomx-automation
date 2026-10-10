<div x-data="{
        booking: false, stop: false, current: null, done: 0, total: 0, ok: 0, failed: 0, finished: false,
        async bookAll(risky) {
            if (this.booking) return;
            const ids = await $wire.bookableIds();
            if (!ids.length) return;
            const html = `Book <b>${ids.length}</b> parcel(s) with the courier picked on each row?`
                + (risky ? `<br><br><span style='color:#dc2626'><b>${risky}</b> row(s) have a risky fraud result.</span>` : '');
            const answer = await Swal.fire({ title: 'Book couriers', html, icon: risky ? 'warning' : 'question', showCancelButton: true, confirmButtonColor: '#4f46e5', confirmButtonText: 'Book all' });
            if (!answer.isConfirmed) return;
            this.run(ids);
        },
        async bookOne(id) {
            if (this.booking) return;
            this.run([id]);
        },
        async run(ids) {
            Object.assign(this, { booking: true, stop: false, finished: false, done: 0, ok: 0, failed: 0, total: ids.length });
            for (const id of ids) {
                if (this.stop) break;
                this.current = id;
                try {
                    const res = await $wire.bookItem(id);
                    res && res.ok ? this.ok++ : this.failed++;
                } catch (e) {
                    this.failed++;
                }
                this.done++;
            }
            Object.assign(this, { booking: false, current: null, finished: true });
        },
    }"
    x-init="$store.pageName = { name: 'Bulk Courier', slug: 'sales-courier-bulk' }"
    class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Bulk Courier Entry</h1>
            <p class="text-sm text-gray-500">Add orders to the sheet with <span class="font-semibold text-indigo-600">+</span>, adjust anything, then book every parcel in one click. The sheet is saved as a draft automatically.</p>
        </div>
        <a href="{{ route('admin.sales.orders') }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-700">← Orders</a>
    </div>

    @if ($couriers->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No active courier account can book shipments yet — set one up under Advance → Courier.
        </div>
    @endif

    {{-- ══════════ Ready to ship ══════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-800 mr-auto">
                Ready to ship
                <span class="ml-1 text-xs font-normal text-gray-400">{{ number_format($orders->total()) }} not booked</span>
            </h2>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Order #, name, phone…"
                    class="w-56 rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterStatus" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterSource" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All sources</option>
                @foreach ($sources as $src)
                    <option value="{{ $src->value }}">{{ $src->label() }}</option>
                @endforeach
            </select>
            @if ($search !== '' || $filterStatus !== '' || $filterSource !== '')
                <button type="button" wire:click="clearFilters"
                    class="px-2 py-2 text-xs font-medium text-gray-500 hover:text-indigo-600">Clear</button>
            @endif
            @php
                $addable = $orders->getCollection()
                    ->reject(fn ($o) => in_array($o->id, $sheetOrderIds, true) || $lockedByOthers->has($o->id))
                    ->pluck('id')->values();
            @endphp
            <button type="button" wire:click="addPage('{{ $addable->implode(',') }}')" @disabled($addable->isEmpty())
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 disabled:opacity-40 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add all on this page ({{ $addable->count() }})
            </button>
        </div>

        <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
            <table class="min-w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50">
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="w-14 px-4 py-2.5"></th>
                        <th class="px-4 py-2.5 text-left">Order</th>
                        <th class="px-4 py-2.5 text-left">Customer</th>
                        <th class="px-4 py-2.5 text-left">Address</th>
                        <th class="px-4 py-2.5 text-left">Source</th>
                        <th class="px-4 py-2.5 text-center">Items</th>
                        <th class="px-4 py-2.5 text-right">Due (COD)</th>
                        <th class="px-4 py-2.5 text-center">Status</th>
                        <th class="w-14 px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        @php
                            $inSheet = in_array($order->id, $sheetOrderIds, true);
                            $lockedBy = $lockedByOthers->get($order->id);
                            $fc = $fraudEnabled ? $fraudChecks->get(\App\Services\FraudShield\FraudShield::orderPhone($order)) : null;
                            $placedAt = local_time($order->created_at);
                        @endphp
                        <tr wire:key="ready-{{ $order->id }}" class="{{ $inSheet ? 'bg-indigo-50/40' : 'hover:bg-gray-50/60' }}">
                            <td class="px-4 py-2.5">
                                @if ($inSheet)
                                    <span title="On the sheet" class="w-8 h-8 inline-flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    </span>
                                @elseif ($lockedBy)
                                    <span title="On {{ $lockedBy }}'s sheet" class="w-8 h-8 inline-flex items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                    </span>
                                @else
                                    <button type="button" wire:click="addOrder({{ $order->id }})" wire:loading.attr="disabled" wire:target="addOrder({{ $order->id }})" title="Add to sheet"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-full bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    </button>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <a href="{{ route('admin.sales.orders.show', $order->id) }}" wire:navigate class="font-medium text-gray-800 hover:text-indigo-600">#{{ $order->id }}</a>
                                <span class="block text-[11px] text-gray-400">{{ $placedAt?->format('d M, h:i A') }}</span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="block text-gray-700">{{ $order->shippingAddress?->name ?? $order->customer?->full_name ?? 'Guest' }}</span>
                                <span class="block text-xs text-gray-400">{{ $order->shippingAddress?->phone ?? $order->customer?->phone }}</span>
                                @if ($fc)
                                    <span class="block mt-1">@include('livewire.admin.sales.partials.fraud-badge', ['fc' => $fc, 'orderId' => $order->id])</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 max-w-xs">
                                <span class="block text-xs text-gray-500 line-clamp-2">{{ $order->shippingAddress?->full_address }}</span>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600">{{ $order->source?->label() ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-gray-600">{{ $order->items_count }}</td>
                            <td class="px-4 py-2.5 text-right font-medium text-gray-800 tabular-nums">{{ number_format((float) $order->due_amount, 2) }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                                @if ($lockedBy)
                                    <span class="block text-[10px] text-gray-400 mt-0.5">on {{ $lockedBy }}'s sheet</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                {{-- Menu is teleported to <body> so the scrolling table can't clip it. --}}
                                <div x-data="{ open: false, top: 0, right: 0,
                                        toggle() { const r = this.$refs.btn.getBoundingClientRect(); this.top = r.bottom + window.scrollY + 4; this.right = window.innerWidth - r.right; this.open = !this.open; } }"
                                    class="flex justify-end">
                                    <button x-ref="btn" @click="toggle()" type="button" title="Actions"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/></svg>
                                    </button>
                                    <template x-teleport="body">
                                        <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window="open = false"
                                            x-transition.opacity.duration.100ms
                                            :style="`position: absolute; top: ${top}px; right: ${right}px; z-index: 9999;`"
                                            class="w-52 bg-white rounded-xl shadow-xl border border-gray-200 py-1 text-sm">
                                            <button type="button" @click="open = false; $dispatch('open-order-view', { orderId: {{ $order->id }} })"
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition"><svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg> View details</button>
                                            @if ($fraudEnabled)
                                                <button type="button" @click="open = false; $dispatch('open-fraud-check', { orderId: {{ $order->id }} })"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition"><svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg> Fraud Check</button>
                                            @endif
                                            @if (! $inSheet && ! $lockedBy)
                                                <button type="button" @click="open = false" wire:click="addOrder({{ $order->id }})"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-indigo-700 hover:bg-indigo-50 transition"><svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg> Add to sheet</button>
                                            @endif
                                            <div class="my-1 border-t border-gray-100"></div>
                                            <a href="{{ route('admin.sales.orders.show', $order->id) }}" target="_blank"
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition"><svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg> Open order page</a>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-12 text-center text-sm text-gray-400">No orders waiting for a courier.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $orders->links() }}</div>
        @endif
    </div>

    {{-- ══════════ Courier sheet ══════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-sm font-semibold text-gray-800 mr-auto">
                    Courier sheet
                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">Draft · auto-saved</span>
                </h2>

                <div class="flex items-center gap-1.5">
                    <select wire:model="bulkCourierId" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">Courier for all rows…</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="applyCourierToAll" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 border border-gray-200 hover:bg-gray-50 transition">Apply</button>
                </div>

                @if ($stats['booked'] > 0)
                    <button type="button" wire:click="completeSheet" wire:confirm="Finish this sheet? Booked rows go to history; anything not booked stays on a new sheet."
                        class="px-3 py-2 rounded-lg text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition">Complete sheet</button>
                @endif
                @if ($stats['open'] > 0)
                    <button type="button" wire:click="clearSheet" wire:confirm="Remove every unbooked row from the sheet?"
                        class="px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 transition">Clear</button>
                @endif

                <button type="button" x-show="!booking" @click="bookAll({{ $stats['risky'] }})" @disabled($stats['open'] === 0 || $couriers->isEmpty())
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm disabled:opacity-40 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 0h-12"/></svg>
                    Book all ({{ $stats['open'] }})
                </button>
                <button type="button" x-show="booking" x-cloak @click="stop = true"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-red-600 bg-red-50 border border-red-200 hover:bg-red-100 transition">
                    <span x-text="stop ? 'Stopping…' : 'Stop'"></span>
                </button>
            </div>

            {{-- Totals --}}
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-lg bg-gray-50 text-gray-600"><b class="text-gray-900">{{ $stats['open'] }}</b> to book</span>
                <span class="px-2.5 py-1 rounded-lg bg-gray-50 text-gray-600">COD <b class="text-gray-900">{{ number_format($stats['cod'], 2) }}</b></span>
                @if ($stats['booked'])
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700"><b>{{ $stats['booked'] }}</b> booked</span>
                @endif
                @if ($stats['failed'])
                    <span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600"><b>{{ $stats['failed'] }}</b> failed</span>
                @endif
                @if ($stats['risky'])
                    <span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600"><b>{{ $stats['risky'] }}</b> risky (fraud)</span>
                @endif
                @foreach ($stats['byCourier'] as $name => $count)
                    <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700">{{ $name }}: <b>{{ $count }}</b></span>
                @endforeach
            </div>

            {{-- Booking progress --}}
            <div x-show="booking || finished" x-cloak class="rounded-lg border px-3 py-2"
                :class="booking ? 'border-indigo-100 bg-indigo-50/50' : (failed ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50')">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-700" x-text="booking ? `Booking ${done + 1} of ${total}…` : `Done — ${ok} booked, ${failed} failed`"></span>
                    <button type="button" x-show="!booking" @click="finished = false" class="text-gray-400 hover:text-gray-600">Dismiss</button>
                </div>
                <div class="mt-1.5 h-1.5 rounded-full bg-white overflow-hidden">
                    <div class="h-full rounded-full bg-indigo-500 transition-all" :style="`width: ${total ? Math.round(done / total * 100) : 0}%`"></div>
                </div>
            </div>
        </div>

        {{-- Grid: fixed column widths (table-fixed + colgroup) — wider than the screen and
             scrolls sideways instead of squeezing cells; # + Order stay pinned left, actions right. --}}
        <div class="courier-sheet overflow-auto max-h-[70vh] overscroll-x-contain">
            <table class="table-fixed text-sm border-separate border-spacing-0" style="width: 2044px">
                <colgroup>
                    <col style="width: 44px">  {{-- # --}}
                    <col style="width: 92px">  {{-- order --}}
                    <col style="width: 170px"> {{-- name --}}
                    <col style="width: 140px"> {{-- phone --}}
                    <col style="width: 92px">  {{-- fraud --}}
                    <col style="width: 300px"> {{-- address --}}
                    <col style="width: 100px"> {{-- cod --}}
                    <col style="width: 80px">  {{-- weight --}}
                    <col style="width: 66px">  {{-- qty --}}
                    <col style="width: 150px"> {{-- courier --}}
                    <col style="width: 280px"> {{-- items --}}
                    <col style="width: 200px"> {{-- note --}}
                    <col style="width: 166px"> {{-- result --}}
                    <col style="width: 164px"> {{-- actions --}}
                </colgroup>
                <thead class="sticky top-0 z-20">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">
                        <th class="sticky left-0 z-30 bg-gray-50 border-b border-gray-200 px-3 py-2.5">#</th>
                        <th class="sticky z-30 bg-gray-50 border-b border-r border-gray-200 px-3 py-2.5 shadow-[4px_0_6px_-4px_rgba(0,0,0,0.12)]" style="left: 44px">Order</th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Name <span class="text-red-400">*</span></th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Phone <span class="text-red-400">*</span></th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Fraud</th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Address <span class="text-red-400">*</span></th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">COD</th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Kg</th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Qty</th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Courier <span class="text-red-400">*</span></th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Items</th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Note for courier</th>
                        <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Result</th>
                        <th class="sticky right-0 z-30 bg-gray-50 border-b border-l border-gray-200 px-3 py-2.5 shadow-[-4px_0_6px_-4px_rgba(0,0,0,0.12)]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $key = 'r' . $item->id;
                            $booked = $item->isBooked();
                            $failed = $item->status === 'failed';
                            $fc = $fraudEnabled ? $fraudChecks->get(\App\Services\FraudShield\FraudShield::normalizePhone($item->recipient_phone)) : null;
                            $rowBg = $booked ? 'bg-emerald-50' : ($failed ? 'bg-red-50' : 'bg-white');
                            $td = 'border-b border-gray-100 px-1.5 py-1.5';
                        @endphp
                        <tr wire:key="sheet-{{ $item->id }}" class="group">
                            <td class="sticky left-0 z-10 border-b border-gray-100 px-3 py-1.5 text-xs text-gray-400 tabular-nums {{ $rowBg }}"
                                :class="current === {{ $item->id }} && '!bg-indigo-50'">{{ $loop->iteration }}</td>
                            <td class="sticky z-10 border-b border-r border-gray-100 px-3 py-1.5 whitespace-nowrap shadow-[4px_0_6px_-4px_rgba(0,0,0,0.12)] {{ $rowBg }}" style="left: 44px"
                                :class="current === {{ $item->id }} && '!bg-indigo-50'">
                                <a href="{{ route('admin.sales.orders.show', $item->order_id) }}" wire:navigate class="font-medium text-gray-800 hover:text-indigo-600">#{{ $item->order_id }}</a>
                                @if ($item->order)
                                    <span class="block text-[10px] leading-tight text-gray-400">{{ $item->order->status->label() }}</span>
                                @endif
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <input wire:model.blur="rows.{{ $key }}.recipient_name" type="text" placeholder="Name" title="{{ $item->recipient_name }}" @disabled($booked) class="cell">
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <input wire:model.blur="rows.{{ $key }}.recipient_phone" type="text" inputmode="tel" placeholder="01XXXXXXXXX" @disabled($booked) class="cell font-mono">
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                @if ($fc)
                                    @include('livewire.admin.sales.partials.fraud-badge', ['fc' => $fc, 'orderId' => $item->order_id])
                                @else
                                    <span class="text-xs text-gray-300 px-1">—</span>
                                @endif
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <input wire:model.blur="rows.{{ $key }}.recipient_address" type="text" placeholder="Full address" title="{{ $item->recipient_address }}" @disabled($booked) class="cell">
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <input wire:model.blur="rows.{{ $key }}.cod_amount" type="number" min="0" step="0.01" @disabled($booked) class="cell text-right tabular-nums">
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <input wire:model.blur="rows.{{ $key }}.weight" type="number" min="0.01" step="0.01" @disabled($booked) class="cell text-right tabular-nums">
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <input wire:model.blur="rows.{{ $key }}.quantity" type="number" min="1" step="1" @disabled($booked) class="cell text-right tabular-nums">
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <select wire:model.live="rows.{{ $key }}.courier_id" @disabled($booked) class="cell {{ $item->courier_id ? '' : '!border-amber-300' }}">
                                    <option value="">— Pick —</option>
                                    @foreach ($couriers as $courier)
                                        <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                                    @endforeach
                                    @if ($item->courier && ! $couriers->contains('id', $item->courier_id))
                                        <option value="{{ $item->courier_id }}">{{ $item->courier->name }} (unavailable)</option>
                                    @endif
                                </select>
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <input wire:model.blur="rows.{{ $key }}.description" type="text" placeholder="Item description" title="{{ $item->description }}" @disabled($booked) class="cell text-xs">
                            </td>
                            <td class="{{ $td }} {{ $rowBg }}">
                                <input wire:model.blur="rows.{{ $key }}.instruction" type="text" placeholder="Optional" @disabled($booked) class="cell text-xs">
                            </td>
                            <td class="border-b border-gray-100 px-3 py-1.5 {{ $rowBg }}">
                                <template x-if="current === {{ $item->id }}">
                                    <span class="inline-flex items-center gap-1.5 text-xs text-indigo-600">
                                        <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                        Booking…
                                    </span>
                                </template>
                                <div x-show="current !== {{ $item->id }}" class="min-w-0">
                                    @if ($booked)
                                        <div class="flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            <span class="text-xs font-mono font-medium text-gray-800 truncate select-all" title="{{ $item->tracking_number }}">{{ $item->tracking_number }}</span>
                                        </div>
                                        <span class="block text-[10px] leading-tight truncate {{ $item->error_message ? 'text-amber-600' : 'text-gray-400' }}" title="{{ $item->error_message }}">
                                            {{ $item->error_message ?: ($item->courier?->name . ' · ' . local_time($item->booked_at)?->format('h:i A')) }}
                                        </span>
                                    @elseif ($failed)
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-red-100 text-red-700">Failed</span>
                                        <span class="block text-[11px] leading-tight text-red-600 truncate" title="{{ $item->error_message }}">{{ $item->error_message }}</span>
                                    @else
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-gray-100 text-gray-500">Ready</span>
                                    @endif
                                </div>
                            </td>
                            <td class="sticky right-0 z-10 border-b border-l border-gray-100 px-2 py-1.5 shadow-[-4px_0_6px_-4px_rgba(0,0,0,0.12)] {{ $rowBg }}"
                                :class="current === {{ $item->id }} && '!bg-indigo-50'">
                                @unless ($booked)
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="$dispatch('open-order-view', { orderId: {{ $item->order_id }} })" title="View order details"
                                            class="w-7 h-7 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        </button>
                                        <button type="button" @click="bookOne({{ $item->id }})" :disabled="booking"
                                            class="px-2.5 py-1.5 rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 transition">
                                            {{ $failed ? 'Retry' : 'Book' }}
                                        </button>
                                        <button type="button" wire:click="resetItem({{ $item->id }})" wire:confirm="Re-fill this row from the order? Your edits on it are lost." :disabled="booking" title="Reset from order"
                                            class="w-7 h-7 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 disabled:opacity-40 transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                        </button>
                                        <button type="button" wire:click="removeItem({{ $item->id }})" :disabled="booking" title="Remove from sheet"
                                            class="w-7 h-7 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 disabled:opacity-40 transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                @else
                                    <a href="{{ route('admin.sales.orders.show', $item->order_id) }}" wire:navigate class="block text-right text-xs font-medium text-emerald-700 hover:underline">View order →</a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-5 py-14 text-center">
                                <p class="text-sm text-gray-500">The sheet is empty.</p>
                                <p class="text-xs text-gray-400 mt-1">Click <span class="font-semibold text-indigo-600">+</span> on an order above to add it here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .courier-sheet .cell { display: block; width: 100%; height: 2.25rem; border: 1px solid #eceef1; border-radius: .5rem; background: rgba(255,255,255,.85); padding: .4375rem .625rem; font-size: .8125rem; line-height: 1.25rem; color: #1f2937; text-overflow: ellipsis; white-space: nowrap; transition: border-color .12s, box-shadow .12s; }
        .courier-sheet select.cell { padding-right: 1.75rem; }
        .courier-sheet .cell:hover { border-color: #d1d5db; }
        .courier-sheet .cell:focus { outline: none; border-color: #6366f1; background: #fff; box-shadow: 0 0 0 1px #6366f1; }
        .courier-sheet .cell:disabled { color: #6b7280; background: transparent; border-color: transparent; cursor: default; }
        .courier-sheet .cell::placeholder { color: #c4c8cf; }
        /* Always-visible scrollbars on the sheet (both axes). */
        .courier-sheet { scrollbar-width: auto; scrollbar-color: #cbd5e1 #f1f5f9; }
        .courier-sheet::-webkit-scrollbar { width: 12px; height: 12px; }
        .courier-sheet::-webkit-scrollbar-track { background: #f1f5f9; }
        .courier-sheet::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; border: 3px solid #f1f5f9; }
        .courier-sheet::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    {{-- ══════════ History ══════════ --}}
    @if ($history->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Completed sheets</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach ($history as $batch)
                    <div wire:key="history-{{ $batch->id }}">
                        <button type="button" wire:click="showHistory({{ $historyBatchId === $batch->id ? 'null' : $batch->id }})"
                            class="w-full flex flex-wrap items-center gap-x-6 gap-y-1 px-5 py-3 text-left text-sm hover:bg-gray-50 transition">
                            <span class="font-medium text-gray-800">Sheet #{{ $batch->id }}</span>
                            <span class="text-gray-500">{{ $batch->booked_count }} booked</span>
                            <span class="text-gray-500">COD {{ number_format((float) $batch->booked_cod, 2) }}</span>
                            <span class="text-xs text-gray-400 ml-auto">{{ $batch->creator?->name }} · {{ local_time($batch->completed_at)?->format('d M Y, h:i A') }}</span>
                        </button>
                        @if ($historyBatchId === $batch->id)
                            <div class="px-5 pb-4 overflow-x-auto">
                                <table class="min-w-full text-xs">
                                    <thead>
                                        <tr class="text-gray-400 uppercase tracking-wide">
                                            <th class="py-2 pr-4 text-left">Order</th>
                                            <th class="py-2 pr-4 text-left">Recipient</th>
                                            <th class="py-2 pr-4 text-left">Courier</th>
                                            <th class="py-2 pr-4 text-left">Tracking</th>
                                            <th class="py-2 text-right">COD</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach ($historyItems as $h)
                                            <tr>
                                                <td class="py-1.5 pr-4"><a href="{{ route('admin.sales.orders.show', $h->order_id) }}" wire:navigate class="text-indigo-600 hover:underline">#{{ $h->order_id }}</a></td>
                                                <td class="py-1.5 pr-4 text-gray-700">{{ $h->recipient_name }} <span class="text-gray-400">{{ $h->recipient_phone }}</span></td>
                                                <td class="py-1.5 pr-4 text-gray-600">{{ $h->courier?->name }}</td>
                                                <td class="py-1.5 pr-4 font-mono text-gray-700">{{ $h->tracking_number ?? '—' }}</td>
                                                <td class="py-1.5 text-right tabular-nums">{{ number_format((float) $h->cod_amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Own components: opening them never re-renders this page; they show a loader at once and fill in. --}}
    <livewire:admin.sales.order-quick-view />
    @if ($fraudEnabled)
        <livewire:admin.sales.fraud-check-modal />
    @endif
</div>
