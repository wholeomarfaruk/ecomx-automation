<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Returning Customers</h1>
        <p class="text-sm text-gray-500 mt-1">Customers with more than one order, and what brought them back.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Orders</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Revenue</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Brought back by</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            onclick="window.location.href='{{ route('admin.marketing.journeys.detail', ['customerId' => $row['customer']->id]) }}'">
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-gray-900">{{ $row['customer']->full_name }}</span>
                                <span class="block text-xs text-gray-400">{{ $row['customer']->phone }}</span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                    {{ $row['orders'] }}x
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($row['revenue'], 2) }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600 capitalize">{{ $row['brought_back_by'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No repeat customers yet</p>
                                <p class="text-xs text-gray-400 mt-0.5">Purchase events need to carry an order_id for repeat-order detection to work.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
