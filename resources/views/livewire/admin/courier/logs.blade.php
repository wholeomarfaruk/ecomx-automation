<div x-data x-init="$store.pageName = { name: 'Courier', slug: 'courier' }" class="space-y-6">

    @include('livewire.admin.courier.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
            <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                <button wire:click="$set('type', 'api')" type="button"
                    class="px-3 py-1.5 rounded-md text-xs font-medium {{ $type === 'api' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500' }}">
                    API Calls
                </button>
                <button wire:click="$set('type', 'webhook')" type="button"
                    class="px-3 py-1.5 rounded-md text-xs font-medium {{ $type === 'webhook' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500' }}">
                    Webhooks
                </button>
            </div>

            @if ($type === 'api')
                <select wire:model.live="outcome" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Outcomes</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                </select>
            @else
                <select wire:model.live="outcome" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="processed">Processed</option>
                    <option value="unmatched">Unmatched</option>
                    <option value="failed">Failed</option>
                </select>
            @endif
        </div>

        @if ($type === 'api')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                            <th class="px-6 py-3">Courier</th>
                            <th class="px-6 py-3">Action</th>
                            <th class="px-6 py-3">Outcome</th>
                            <th class="px-6 py-3">Duration</th>
                            <th class="px-6 py-3">Error</th>
                            <th class="px-6 py-3">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($apiLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ $log->courier->name ?? '—' }}</td>
                                <td class="px-6 py-3 text-gray-600 font-mono text-xs">{{ $log->action }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $log->success ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                                        {{ $log->success ? 'Success' : 'Failed' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $log->duration_ms }}ms</td>
                                <td class="px-6 py-3 text-xs text-red-500">{{ $log->error_message ?? '—' }}</td>
                                <td class="px-6 py-3 text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No API calls logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($apiLogs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $apiLogs->links() }}</div>
            @endif
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                            <th class="px-6 py-3">Courier</th>
                            <th class="px-6 py-3">Event Type</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">IP Address</th>
                            <th class="px-6 py-3">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($webhookLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ $log->courier->name ?? 'Unknown' }}</td>
                                <td class="px-6 py-3 text-gray-600 font-mono text-xs">{{ $log->event_type ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full
                                        {{ $log->status === 'processed' ? 'bg-emerald-100 text-emerald-700' : ($log->status === 'unmatched' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600') }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $log->ip_address }}</td>
                                <td class="px-6 py-3 text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">No webhooks received yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($webhookLogs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $webhookLogs->links() }}</div>
            @endif
        @endif
    </div>

</div>
