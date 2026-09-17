<div x-data x-init="$store.pageName = { name: 'Order Ledgers', slug: 'accounts-order-ledgers' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">অর্ডার লেজার (Order Ledgers)</h1>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by order # or customer…"
            class="px-3 py-2 text-sm rounded-lg border border-gray-300 w-64 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 text-sm font-medium text-gray-800">#{{ $order->id }}</td>
                            <td class="px-5 py-3">
                                <p class="text-sm text-gray-700">{{ $order->customer?->full_name ?? 'Guest' }}</p>
                                <p class="text-xs text-gray-400">{{ $order->customer?->phone }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {{ $order->status->badgeClass() }}">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.accounts.reports.order-ledger', ['orderId' => $order->id]) }}"
                                    class="text-sm text-indigo-600 hover:text-indigo-700">View Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400">No order ledger activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $orders->links() }}</div>
    </div>
</div>
