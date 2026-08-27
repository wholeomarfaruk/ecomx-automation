<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Customer Tracking</h1>
        <p class="text-sm text-gray-500 mt-1">Full marketing profile per customer: first touch, last touch, activity, revenue.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search name, phone, or email…"
            class="w-full max-w-sm rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">First Touch</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Touch</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Events</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Purchases</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Revenue</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            onclick="window.location.href='{{ route('admin.marketing.journeys.detail', ['customerId' => $customer->id]) }}'">
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-gray-900">{{ $customer->full_name }}</span>
                                <span class="block text-xs text-gray-400">{{ $customer->phone }}</span>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-600 capitalize">{{ $customer->_first_source ?? '—' }}</td>
                            <td class="px-3 py-3 text-sm text-gray-600 capitalize">{{ $customer->_last_source ?? '—' }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($customer->_events_count) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($customer->_purchases) }}</td>
                            <td class="px-3 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($customer->_revenue, 2) }}</td>
                            <td class="px-5 py-3 text-sm text-gray-500">{{ $customer->_last_seen?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-gray-500">No tracked customers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
