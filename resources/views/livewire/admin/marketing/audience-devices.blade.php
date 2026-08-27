<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Devices</h1>
        <p class="text-sm text-gray-500 mt-1">{{ number_format($totalDevices) }} devices tracked in total.</p>
    </div>

    {{-- Type breakdown --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach ($typeBreakdown as $type)
            <button type="button" wire:click="$set('deviceType', '{{ $type->device_type }}')"
                class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-left hover:border-indigo-300 transition
                    {{ $deviceType === $type->device_type ? 'ring-2 ring-indigo-500 border-indigo-300' : '' }}">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ $type->device_type ?? 'Unknown' }}</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($type->total) }}</p>
            </button>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Fingerprint or IP…"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            @if ($deviceType || $search)
                <button wire:click="$set('deviceType', '')" type="button" class="text-sm text-gray-500 hover:text-gray-700 pb-2">Clear filter</button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fingerprint</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type / OS / Browser</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Events</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($devices as $device)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            onclick="window.location.href='{{ route('admin.marketing.journeys.detail', ['deviceId' => $device->id]) }}'">
                            <td class="px-5 py-3">
                                <span class="text-xs font-mono text-gray-600">{{ Str::limit($device->fingerprint, 20) }}</span>
                                <span class="block text-xs text-gray-400">{{ $device->ip_address }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-700">{{ $device->device_type ?? '—' }}</span>
                                <span class="block text-xs text-gray-400">{{ $device->operating_system }} · {{ $device->browser }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-700">{{ $device->customer?->full_name ?? 'Anonymous' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($device->marketing_events_count) }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-600">{{ $device->last_active_at?->diffForHumans() ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500">No devices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($devices->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $devices->links() }}</div>
        @endif
    </div>
</div>
