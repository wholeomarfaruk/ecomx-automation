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
        <a href="{{ route('admin.sales.orders.create') }}" wire:navigate
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Create Order
        </a>
    </div>

    {{-- View tabs --}}
    <div class="flex items-center gap-1 border-b border-gray-200 mb-6">
        @foreach ([
            ['key' => 'orders', 'label' => 'Orders'],
            ['key' => 'products', 'label' => 'Ordered Products'],
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
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Due</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Payment</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Courier</th>
                        <th class="sticky right-0 bg-gray-50/40 px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition group">
                            <td class="px-5 py-3 cursor-pointer" onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                                <span class="text-sm font-medium text-gray-800">#{{ $order->id }}</span>
                                <span class="block text-xs text-gray-400">{{ $order->created_at->format('d M, Y') }}</span>
                            </td>
                            <td class="px-5 py-3 cursor-pointer" onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                                <span class="block text-sm text-gray-600">{{ $order->customer?->full_name ?? 'Guest' }}</span>
                                <span class="block text-xs text-gray-400">{{ $order->customer?->phone ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3 cursor-pointer" onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                                <span class="text-xs text-gray-500">{{ $order->source->label() }}</span>
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
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $order->payment_status->badgeClass() }}">
                                    {{ $order->payment_status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $order->status->badgeClass() }}">
                                    {{ $order->status->label() }}
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

                                            <button wire:click="viewOrder({{ $order->id }})" @click="open = false" type="button"
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
                                            <a href="{{ route('admin.sales.orders.show', $order->id) }}#courier" wire:navigate
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 0h-12"/>
                                                </svg>
                                                {{ $order->courier_status ? 'Manage Courier' : 'Book Courier' }}
                                            </a>

                                            <div class="my-1 border-t border-gray-100"></div>

                                            <div class="px-4 py-2">
                                                <label class="block text-[11px] font-medium text-gray-400 uppercase tracking-wide mb-1">Order Status</label>
                                                <select wire:change="updateOrderStatus({{ $order->id }}, $event.target.value)" @click.stop
                                                    class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                    @foreach($statuses as $s)
                                                        <option value="{{ $s->value }}" @selected($order->status === $s)>{{ $s->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="px-4 py-2">
                                                <label class="block text-[11px] font-medium text-gray-400 uppercase tracking-wide mb-1">Payment Status</label>
                                                <select wire:change="updatePaymentStatus({{ $order->id }}, $event.target.value)" @click.stop
                                                    class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                    @foreach($paymentStatuses as $ps)
                                                        <option value="{{ $ps->value }}" @selected($order->payment_status === $ps)>{{ $ps->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-16 text-center">
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

    {{-- View Details Modal --}}
    <div x-cloak x-data="{ modalOpen: @entangle('viewModal') }" x-show="modalOpen" x-transition
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
                                    <div>
                                        <p class="text-gray-800">{{ $item->product_name }}</p>
                                        @if($item->variant_name)
                                            <p class="text-xs text-gray-400">{{ $item->variant_name }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-gray-600">{{ $item->quantity }} × {{ number_format($item->unit_price, 2) }}</p>
                                        <p class="text-xs font-medium text-gray-800">{{ number_format($item->total_amount, 2) }}</p>
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
