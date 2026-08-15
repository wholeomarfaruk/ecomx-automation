<div x-data x-init="$store.pageName = { name: 'SMS Configuration', slug: 'sms-configuration' }" class="space-y-6">

    @include('livewire.admin.sms.partials.tabs')

    {{-- ── Usage Analytics ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Usage Analytics</h2>
            <p class="text-xs text-gray-400">Daily trend over the last 14 days</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Date</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Total</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Sent</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Failed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($dailyTrend as $row)
                        <tr>
                            <td class="px-6 py-3 text-gray-700">{{ $row->date }}</td>
                            <td class="px-6 py-3 text-gray-500 font-mono">{{ $row->total }}</td>
                            <td class="px-6 py-3 text-gray-500 font-mono">{{ $row->sent }}</td>
                            <td class="px-6 py-3 text-gray-500 font-mono">{{ $row->failed }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (count($byGateway))
            <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap gap-2">
                @foreach ($byGateway as $row)
                    <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                        {{ $row->driver_key }}: {{ $row->total }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Queue Monitor ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Queue Monitor</h2>
                <p class="text-xs text-gray-400">Pending and failed SMS jobs</p>
            </div>
            @if ($failedJobs->count())
                <button wire:click="retryFailed" wire:confirm="Retry all failed SMS jobs?"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Retry All Failed
                </button>
            @endif
        </div>
        <div class="px-6 py-5 grid grid-cols-2 gap-4">
            <div class="p-4 rounded-xl border border-gray-100">
                <p class="text-xs font-medium text-gray-400 mb-1">Pending</p>
                <p class="text-xl font-semibold text-gray-900 font-mono">{{ $pending ?? 'N/A' }}</p>
            </div>
            <div class="p-4 rounded-xl border border-gray-100">
                <p class="text-xs font-medium text-gray-400 mb-1">Failed</p>
                <p class="text-xl font-semibold {{ $failedJobs->count() > 0 ? 'text-red-600' : 'text-gray-900' }} font-mono">{{ $failedJobs->count() }}</p>
            </div>
        </div>
        @if ($failedJobs->count())
            <div class="divide-y divide-gray-100 border-t border-gray-100">
                @foreach ($failedJobs as $job)
                    <div class="px-6 py-3">
                        <p class="text-xs text-gray-500 font-mono">UUID: {{ $job->uuid }}</p>
                        <p class="text-xs text-gray-400">Failed at {{ $job->failed_at }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Developer Info ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Developer Information</h2>
            <p class="text-xs text-gray-400">Read-only details about the active gateway driver</p>
        </div>
        <div class="px-6 py-5">
            @if ($meta)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
                    <div>
                        <p class="text-xs font-medium text-gray-400 mb-1">Gateway</p>
                        <p class="text-sm text-gray-700">{{ $meta['label'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 mb-1">Driver Version</p>
                        <p class="text-sm text-gray-700 font-mono">{{ $meta['version'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 mb-1">Driver Key</p>
                        <p class="text-sm text-gray-700 font-mono">{{ $meta['key'] }}</p>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Supported Features</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($features as $feature => $supported)
                            <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full
                                {{ $supported ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ str($feature)->headline() }}: {{ $supported ? 'Yes' : 'No' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">No active gateway configured.</p>
            @endif
        </div>
    </div>

</div>
