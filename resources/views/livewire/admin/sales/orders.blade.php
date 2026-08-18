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

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by order #, customer name, or phone…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterPaymentStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Payment Statuses</option>
                @foreach($paymentStatuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterSource"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Sources</option>
                @foreach($sources as $source)
                    <option value="{{ $source->value }}">{{ $source->label() }}</option>
                @endforeach
            </select>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input wire:model.live="dateFrom" type="text" placeholder="From" readonly
                    class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-32 cursor-pointer">
            </div>
            <span class="text-gray-400 text-sm">–</span>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input wire:model.live="dateTo" type="text" placeholder="To" readonly
                    class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-32 cursor-pointer">
            </div>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            onclick="window.location.href='{{ route('admin.sales.orders.show', $order->id) }}'">
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-gray-800">#{{ $order->id }}</span>
                                <span class="block text-xs text-gray-400">{{ $order->created_at->format('d M, Y') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="block text-sm text-gray-600">{{ $order->customer?->full_name ?? 'Guest' }}</span>
                                <span class="block text-xs text-gray-400">{{ $order->customer?->phone ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs text-gray-500">{{ $order->source->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm text-gray-600">{{ $order->items_count }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($order->total_amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
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
</div>
