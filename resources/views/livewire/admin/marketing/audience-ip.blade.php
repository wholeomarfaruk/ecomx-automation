<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">IP Analysis</h1>
        <p class="text-sm text-gray-500 mt-1">Supporting signal only — an IP is not a visitor identity. Useful for spotting shared connections or suspicious repeat activity.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search IP address…"
            class="w-full max-w-sm rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">IP Address</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Devices</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Customers</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Events</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ips as $ip)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            onclick="window.location.href='{{ route('admin.marketing.audience.devices', ['search' => $ip->ip_address]) }}'">
                            <td class="px-5 py-3 text-sm font-mono text-gray-700">{{ $ip->ip_address }}</td>
                            <td class="px-3 py-3 text-right">
                                <span class="text-sm {{ $ip->device_count > 3 ? 'text-amber-600 font-semibold' : 'text-gray-700' }}">
                                    {{ number_format($ip->device_count) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <span class="text-sm {{ $ip->customer_count > 1 ? 'text-amber-600 font-semibold' : 'text-gray-700' }}">
                                    {{ number_format($ip->customer_count) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($ip->event_count) }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ \Illuminate\Support\Carbon::parse($ip->last_seen)->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500">No IP activity found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ips->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $ips->links() }}</div>
        @endif
    </div>
</div>
