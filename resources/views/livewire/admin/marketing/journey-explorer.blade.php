<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
        <p class="text-sm text-gray-500 mt-1">Search any device or customer, then view their full browsing-to-purchase timeline.</p>
    </div>

    {{-- Search --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" wire:model.live.debounce.400ms="query" placeholder="{{ $placeholder }}"
                    class="w-full pl-10 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <p class="text-xs text-gray-400 shrink-0">
                {{ number_format($trackedCustomerCount) }} tracked {{ Str::plural('customer', $trackedCustomerCount) }}
                @if ($trackedDeviceCount > 0)
                    · {{ number_format($trackedDeviceCount) }} tracked {{ Str::plural('device', $trackedDeviceCount) }}
                @endif
            </p>
        </div>
    </div>

    {{-- Customers table --}}
    @if ($mode !== 'anonymous')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">
                    {{ $query !== '' ? 'Matching customers' : 'Recently active customers' }}
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Contact</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Updated</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($customers as $customer)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ strtoupper(substr($customer->full_name ?? '?', 0, 1)) }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-800">{{ $customer->full_name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-600">{{ $customer->phone ?? $customer->email ?? '—' }}</td>
                                <td class="px-3 py-3 text-sm text-gray-500">{{ $customer->updated_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.marketing.journeys.detail', ['customerId' => $customer->id]) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                        View Details
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-400">
                                    {{ $query !== '' ? 'No matching customers.' : 'No tracked customers yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Devices table --}}
    @if ($mode !== 'customers')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">
                    {{ $query !== '' ? 'Matching devices' : 'Recently active devices' }}
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Device</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type / OS</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Active</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($devices as $device)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3" />
                                            </svg>
                                        </span>
                                        <span class="text-sm font-mono text-gray-800">{{ Str::limit($device->fingerprint, 22) }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-600">{{ $device->device_type ?? '—' }} · {{ $device->operating_system ?? '—' }}</td>
                                <td class="px-3 py-3">
                                    @if ($device->customer_id)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Linked</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Anonymous</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-500">{{ $device->last_active_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.marketing.journeys.detail', ['deviceId' => $device->id]) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                        View Details
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">
                                    {{ $query !== '' ? 'No matching devices.' : 'No tracked devices yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
