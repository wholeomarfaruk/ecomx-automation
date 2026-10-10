<div x-data x-init="$store.pageName = { name: 'Orders', slug: 'sales-orders' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-4 gap-3 flex-1">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Orders</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Revenue (Paid)</p>
                <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ number_format($totalRevenue, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Pending Orders</p>
                <p class="text-xl font-semibold text-amber-600 mt-0.5">{{ $pendingCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Due</p>
                <p class="text-xl font-semibold text-red-500 mt-0.5">{{ number_format($dueTotal, 2) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if(auth()->user()?->hasRole('superadmin') || auth()->user()?->can('order.create'))
                <a href="{{ route('admin.sales.orders.bulk', ['intake' => 1]) }}" wire:navigate
                    title="Paste a customer message, chat or screenshot — single or many orders"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-linear-to-r from-violet-600 to-indigo-600 rounded-xl hover:opacity-90 transition shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                    </svg>
                    AI Order
                </a>
                <a href="{{ route('admin.sales.orders.bulk') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5"/>
                    </svg>
                    Bulk Order
                </a>
            @endif
            <a href="{{ route('admin.sales.orders.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Create Order
            </a>
        </div>
    </div>

    {{-- View tabs --}}
    <div class="flex items-center gap-1 border-b border-gray-200 mb-6">
        @foreach ([
            ['key' => 'orders', 'label' => 'Orders'],
            ['key' => 'products', 'label' => 'Ordered Products'],
            ['key' => 'packed', 'label' => 'Packed'],
            ['key' => 'autosaved', 'label' => 'Autosaved Orders'],
        ] as $tabItem)
            <button type="button" wire:click="$set('view', '{{ $tabItem['key'] }}')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                    {{ $view === $tabItem['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $tabItem['label'] }}
            </button>
        @endforeach
    </div>

    @if ($view === 'orders')
    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by order #, customer name, phone, product, or SKU…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            @include('livewire.admin.sales.partials.order-filters')
            @if($search || $filterStatus || $filterPaymentStatus || $filterSource || $dateFrom || $dateTo)
                <button wire:click="resetFilters" type="button" class="text-sm text-gray-500 hover:text-gray-700 transition">Clear</button>
            @endif

            {{-- Export (follows the search + filters above) --}}
            <div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
                <button @click="open = !open" type="button"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <svg wire:loading.remove wire:target="exportExcel" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    <svg wire:loading wire:target="exportExcel" class="h-4 w-4 animate-spin text-gray-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Export
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div x-cloak x-show="open" @click.outside="open = false" x-transition
                    class="absolute right-0 mt-1 w-56 bg-white rounded-xl shadow-xl border border-gray-200 py-1 text-sm z-30">
                    <button wire:click="exportExcel" @click="open = false" wire:loading.attr="disabled" type="button"
                        class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5"/>
                        </svg>
                        <span>
                            Excel Export
                            <span class="block text-[11px] text-gray-400">{{ ($search || $filterStatus || $filterPaymentStatus || $filterSource || $dateFrom || $dateTo) ? 'Filtered orders only' : 'All orders' }}</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Result count --}}
        @php $isFiltered = $search || $filterStatus || $filterPaymentStatus || $filterSource || $dateFrom || $dateTo; @endphp
        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5 border-b border-gray-100 {{ $isFiltered ? 'bg-indigo-50/60' : 'bg-white' }}">
            <p class="text-sm text-gray-600">
                @if ($orders->total() > 0)
                    <span class="font-semibold text-gray-900">{{ number_format($orders->total()) }}</span>
                    {{ $isFiltered ? 'matching' : 'total' }} {{ Str::plural('order', $orders->total()) }}
                    <span class="text-gray-400">· showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }}</span>
                @else
                    <span class="font-semibold text-gray-900">0</span> orders found
                @endif
            </p>
            @if ($isFiltered)
                <button type="button" wire:click="resetFilters" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Clear filters</button>
            @endif
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="pl-5 pr-2 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">SL</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Due</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Payment</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Fulfillment</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Courier</th>
                        <th class="sticky right-0 bg-gray-50/40 px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition group">
                            <td class="pl-5 pr-2 py-3 text-sm text-gray-400 tabular-nums">{{ $orders->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 cursor-pointer" onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                                <span class="text-sm font-medium text-gray-800">#{{ $order->id }}</span>
                                @php $placedAt = local_time($order->created_at); @endphp
                                <span class="block text-xs text-gray-400 whitespace-nowrap">{{ $placedAt?->format('d M, Y h:i A') }}</span>
                                @if ($placedAt && $placedAt->isToday())
                                    <span class="block text-[11px] text-indigo-500">{{ $placedAt->diffForHumans() }}</span>
                                @elseif ($placedAt && $placedAt->isYesterday())
                                    <span class="block text-[11px] text-indigo-500">Yesterday</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 cursor-pointer" onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                                <span class="block text-sm text-gray-600">{{ $order->customer?->full_name ?? 'Guest' }}</span>
                                <span class="block text-xs text-gray-400">{{ $order->customer?->phone ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3" @click.stop>
                                <select wire:change="updateOrderSource({{ $order->id }}, $event.target.value)" title="Change source"
                                    class="cursor-pointer text-xs text-gray-600 bg-transparent rounded-md border-0 py-1 pl-1 pr-6 hover:bg-gray-100 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                                    @foreach($sources as $src)
                                        <option value="{{ $src->value }}" @selected($order->source === $src)>{{ $src->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-3 text-center cursor-pointer" onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                                <span class="text-sm text-gray-600">{{ $order->items_count }}</span>
                            </td>
                            <td class="px-5 py-3 text-right cursor-pointer" onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($order->total_amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-3 text-right cursor-pointer" onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                                <span class="text-sm {{ $order->due_amount > 0 ? 'text-red-500 font-medium' : 'text-gray-400' }}">{{ number_format($order->due_amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-3 text-center" @click.stop>
                                <select wire:change="updatePaymentStatus({{ $order->id }}, $event.target.value)"
                                    class="appearance-none cursor-pointer text-center px-2.5 py-1 rounded-full text-xs font-medium border-0 focus:ring-2 focus:ring-indigo-400 focus:outline-none {{ $order->payment_status->badgeClass() }}">
                                    @foreach($paymentStatuses as $ps)
                                        <option value="{{ $ps->value }}" @selected($order->payment_status === $ps)>{{ $ps->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-3 text-center" @click.stop>
                                <select wire:change="updateOrderStatus({{ $order->id }}, $event.target.value)"
                                    class="appearance-none cursor-pointer text-center px-2.5 py-1 rounded-full text-xs font-medium border-0 focus:ring-2 focus:ring-indigo-400 focus:outline-none {{ $order->status->badgeClass() }}">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s->value }}" @selected($order->status === $s)>{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $order->fulfillment_status->badgeClass() }}">
                                    {{ $order->fulfillment_status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($order->courier_status)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $order->courier_status->badgeClass() }}">
                                        {{ $order->courier_status->label() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-400">
                                        Not booked
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 bg-white group-hover:bg-gray-50/60 px-5 py-3">
                                <div x-data="{
                                        open: false,
                                        top: 0,
                                        right: 0,
                                        toggle() {
                                            const r = this.$refs.btn.getBoundingClientRect();
                                            this.top   = r.bottom + window.scrollY + 4;
                                            this.right = window.innerWidth - r.right;
                                            this.open  = !this.open;
                                        }
                                    }"
                                    class="flex justify-end">

                                    <button x-ref="btn" @click="toggle()" type="button"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/>
                                        </svg>
                                    </button>

                                    <template x-teleport="body">
                                        <div x-show="open"
                                             @click.outside="open = false"
                                             @keydown.escape.window="open = false"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             :style="`position: absolute; top: ${top}px; right: ${right}px; z-index: 9999;`"
                                             class="w-64 bg-white rounded-xl shadow-xl border border-gray-200 py-1 text-sm origin-top-right">

                                            @if($order->status === \App\Enums\Sales\OrderStatus::RETURNING)
                                                <button wire:click="markReturnReceived({{ $order->id }})" @click="open = false" type="button"
                                                    wire:confirm="Parcel for Order #{{ $order->id }} is back in your hands? The order becomes Returned and its stock is put back."
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-orange-700 hover:bg-orange-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                                                    </svg>
                                                    Return received
                                                </button>
                                            @endif
                                            <button @click="open = false; $dispatch('open-order-view', { orderId: {{ $order->id }} })" type="button"
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                </svg>
                                                View Details
                                            </button>
                                            <a href="{{ route('admin.sales.orders.show', $order->id) }}" wire:navigate
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/>
                                                </svg>
                                                Full Details
                                            </a>
                                            @if($order->courier_status)
                                                <a href="{{ route('admin.sales.orders.show', $order->id) }}#courier" wire:navigate
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 0h-12"/>
                                                    </svg>
                                                    Manage Courier
                                                </a>
                                            @else
                                                <button @click="open = false; $dispatch('open-courier-booking', { orderId: {{ $order->id }} })" type="button"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 0h-12"/>
                                                    </svg>
                                                    Book Courier
                                                </button>
                                            @endif
                                            @if(auth()->user()?->hasRole('superadmin') || auth()->user()?->can('order.edit'))
                                                <button @click="open = false; $dispatch('open-order-notification', { orderId: {{ $order->id }} })" type="button"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                                                    </svg>
                                                    Send Notification
                                                </button>
                                            @endif
                                            @if(auth()->user()?->hasRole('superadmin') || auth()->user()?->can('order.delete'))
                                                <div class="my-1 border-t border-gray-100"></div>
                                                <button type="button"
                                                    @click="open = false; Swal.fire({
                                                        title: 'Delete order #{{ $order->id }}?',
                                                        html: 'This permanently deletes the order with its items, payments and courier records.<br><br>Stock it holds is put back and its accounting entries are voided.<br><br><b>This cannot be undone.</b>',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#ef4444',
                                                        confirmButtonText: 'Delete permanently'
                                                    }).then(r => { if (r.isConfirmed) $wire.deleteOrder({{ $order->id }}) })"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-red-600 hover:bg-red-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                    </svg>
                                                    Delete Order
                                                </button>
                                            @endif
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.684 2.674-7.14a1.06 1.06 0 0 0-.999-1.335H5.85m4.5 8.475H5.85m0 0-.383-1.437M12 14.25l3.75-3.75M12 14.25l-3.75-3.75M12 14.25V6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No orders found</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Try adjusting filters, or create a new order</p>
                                    </div>
                                    <a href="{{ route('admin.sales.orders.create') }}" wire:navigate
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                        Create First Order
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
    @endif

    @if ($view === 'products')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="relative flex-1 min-w-[200px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="productSearch" type="text" placeholder="Search by product name or SKU…"
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                @include('livewire.admin.sales.partials.order-filters')
                @if ($productSearch || $filterStatus || $filterPaymentStatus || $filterSource || $dateFrom || $dateTo)
                    <button wire:click="resetFilters" type="button" class="text-sm text-gray-500 hover:text-gray-700 transition">Clear</button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">SKU</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Units Ordered</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Orders</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Revenue</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orderedProducts as $row)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <span class="text-sm font-medium text-gray-800">{{ $row->product_name }}</span>
                                    @if ($row->variant_name)
                                        <span class="block text-xs text-gray-400">{{ $row->variant_name }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-500 font-mono">{{ $row->sku ?? '—' }}</td>
                                <td class="px-5 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($row->total_quantity, ($row->total_quantity == (int) $row->total_quantity) ? 0 : 2) }}</td>
                                <td class="px-5 py-3 text-right text-sm text-gray-600">{{ number_format($row->order_count) }}</td>
                                <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($row->total_revenue, 2) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.sales.orders', ['view' => 'orders', 'search' => $row->sku ?? $row->product_name]) }}"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                        See Orders →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center text-sm text-gray-500">No ordered products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orderedProducts->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">
                    {{ $orderedProducts->links() }}
                </div>
            @endif
        </div>
    @endif

    @if ($view === 'packed')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by order #, customer name or phone…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="packState" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All packed</option>
                <option value="full">Fully packed (ready to ship)</option>
                <option value="partial">Partially packed</option>
            </select>
            <select wire:model.live="filterSource" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All sources</option>
                @foreach($sources as $src)
                    <option value="{{ $src->value }}">{{ $src->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Packed</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Courier</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($packedOrders as $packed)
                        @php($isFull = (float) $packed->packed_qty + 0.001 >= (float) $packed->packable_qty)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.sales.orders.show', $packed->id) }}" wire:navigate class="text-sm font-medium text-gray-800 hover:text-indigo-600">#{{ $packed->id }}</a>
                                <span class="block text-xs text-gray-400">{{ $packed->created_at->format('d M, Y') }} · {{ $packed->source->label() }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="block text-sm text-gray-600">{{ $packed->customer?->full_name ?? 'Guest' }}</span>
                                <span class="block text-xs text-gray-400">{{ $packed->customer?->phone ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $isFull ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                                    {{ $isFull ? 'Fully packed' : 'Partially packed' }}
                                </span>
                                <span class="block text-xs text-gray-400 mt-1">{{ rtrim(rtrim(number_format((float) $packed->packed_qty, 3), '0'), '.') }} / {{ rtrim(rtrim(number_format((float) $packed->packable_qty, 3), '0'), '.') }} units</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($packed->total_amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $packed->status->badgeClass() }}">{{ $packed->status->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($packed->courier_status)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $packed->courier_status->badgeClass() }}">{{ $packed->courier_status->label() }}</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-400">Not booked</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.sales.orders.print', [$packed->id, 'packing-slip']) }}" target="_blank" class="text-xs font-medium text-gray-500 hover:text-gray-700">Packing slip</a>
                                <a href="{{ route('admin.sales.orders.show', $packed->id) }}" wire:navigate class="ml-3 text-xs font-medium text-indigo-600 hover:text-indigo-700">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">No packed orders{{ $packState === 'full' ? ' ready to ship' : '' }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($packedOrders->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $packedOrders->links() }}</div>
        @endif
    </div>
    @endif

    @if ($view === 'autosaved')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-16">
            <div class="flex flex-col items-center gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700">Autosaved Orders — Coming Soon</p>
                    <p class="text-xs text-gray-400 mt-1 max-w-sm">
                        Customers who reached checkout and filled in the order form but didn't complete the order will show up here.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- View details: its own component, so it doesn't re-render this whole page. --}}
    <livewire:admin.sales.order-quick-view />

    {{-- Courier booking: its own component, so it doesn't re-render this whole page. --}}
    <livewire:admin.sales.courier-booking-modal />
    <livewire:admin.sales.send-order-notification-modal />
</div>
