<div x-data x-init="$store.pageName = { name: 'Courier', slug: 'courier' }" class="space-y-6">

    @include('livewire.admin.courier.partials.tabs')

    {{-- ── Key Metrics ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Active Couriers</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $activeCourierCount }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Active Accounts</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $activeAccountCount }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Shipments Today</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $todayCount }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">This Month</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $monthCount }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Delivery Success Rate + Status Breakdown ── --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-700">Delivery Success Rate</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $successRate !== null ? $successRate . '%' : 'No data yet' }}</p>
                </div>
                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-600 rounded-full transition-all" style="width: {{ $successRate ?? 0 }}%"></div>
                </div>
                @if ($failedCount > 0)
                    <p class="text-xs text-red-500 mt-2">{{ $failedCount }} shipment(s) failed or returned</p>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Status Breakdown</h2>
                </div>
                <div class="px-6 py-5 space-y-3">
                    @php
                        $barColors = [
                            'pending' => 'bg-gray-400',
                            'picked_up' => 'bg-blue-500',
                            'in_transit' => 'bg-indigo-500',
                            'out_for_delivery' => 'bg-purple-500',
                            'delivered' => 'bg-emerald-500',
                            'failed' => 'bg-red-500',
                            'returned' => 'bg-orange-500',
                            'cancelled' => 'bg-slate-400',
                        ];
                    @endphp
                    @forelse (\App\Enums\Sales\CourierStatus::cases() as $status)
                        @php $count = $statusBreakdown[$status->value] ?? 0; @endphp
                        @if ($count > 0)
                            <div class="flex items-center gap-3">
                                <span class="w-32 shrink-0 text-xs font-medium text-gray-600">{{ $status->label() }}</span>
                                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $barColors[$status->value] ?? 'bg-gray-400' }}"
                                        style="width: {{ $statusBreakdown->sum() > 0 ? round($count / $statusBreakdown->sum() * 100) : 0 }}%"></div>
                                </div>
                                <span class="w-10 text-right text-xs font-mono text-gray-500">{{ $count }}</span>
                            </div>
                        @endif
                    @empty
                        <p class="text-xs text-gray-400">No shipments yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- ── Recent Shipments ── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Shipments</h2>
                    <a href="{{ route('admin.settings.advance.courier.shipments') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View all →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                                <th class="px-6 py-3">Order</th>
                                <th class="px-6 py-3">Courier</th>
                                <th class="px-6 py-3">Tracking</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentShipments as $shipment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">#{{ $shipment->order_id }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ $shipment->courier->name }}</td>
                                    <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $shipment->tracking_number ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $shipment->statusEnum()->badgeClass() }}">
                                            {{ $shipment->statusEnum()->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-xs text-gray-400">{{ $shipment->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">No shipments booked yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Courier Performance ── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden h-fit">
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Couriers</h2>
                <p class="text-xs text-gray-400">Shipment volume per courier</p>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach ($courierPerformance as $courier)
                    <div class="px-6 py-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full {{ $courier->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $courier->name }}</p>
                                <p class="text-xs text-gray-400">{{ $courier->is_active ? 'Active' : 'Not configured' }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-mono font-semibold text-gray-700">{{ $courier->shipments_count }}</span>
                    </div>
                @endforeach
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                <a href="{{ route('admin.settings.advance.courier.couriers') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Manage couriers →</a>
            </div>
        </div>
    </div>

</div>
