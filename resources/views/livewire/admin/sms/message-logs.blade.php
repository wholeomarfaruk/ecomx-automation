<div x-data x-init="$store.pageName = { name: 'SMS Configuration', slug: 'sms-configuration' }" class="space-y-6">

    @include('livewire.admin.sms.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Message Logs</h2>
                <p class="text-xs text-gray-400">Sent, failed, pending, and delivered messages</p>
            </div>
            <select wire:model.live="statusFilter"
                class="rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="sent">Sent</option>
                <option value="delivered">Delivered</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">To</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Gateway</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Status</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Error</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Retries</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Sent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-6 py-3 text-gray-700 font-mono">{{ $log->to }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $log->driver_key }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full
                                    {{ in_array($log->status, ['sent', 'delivered']) ? 'bg-amber-100 text-amber-700' : ($log->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 text-xs">{{ $log->error_message ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $log->retry_count }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $log->sent_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No messages logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $logs->links() }}</div>
    </div>

</div>
