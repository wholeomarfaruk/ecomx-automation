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
                            <th class="px-6 py-3 text-right">Action</th>
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
                                <td class="px-6 py-3 text-right">
                                    <button wire:click="viewLog({{ $log->id }}, 'api')" type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">No API calls logged yet.</td></tr>
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
                            <th class="px-6 py-3 text-right">Action</th>
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
                                <td class="px-6 py-3 text-right">
                                    <button wire:click="viewLog({{ $log->id }}, 'webhook')" type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No webhooks received yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($webhookLogs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $webhookLogs->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Log Detail Drawer --}}
    <div x-data="{ open: @entangle('drawerOpen') }">
        <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 bg-gray-900/40 z-40"
            wire:click="closeDrawer" @click="open = false"></div>
        <div x-cloak x-show="open"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed top-0 right-0 h-screen w-full max-w-lg bg-white shadow-2xl z-50 overflow-y-auto">
            @if ($viewingLog)
                <div class="p-6 space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                {{ $viewLogType === 'api' ? $viewingLog->action : ($viewingLog->event_type ?? 'Webhook Event') }}
                            </h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $viewingLog->courier->name ?? '—' }} · {{ $viewingLog->created_at->format('d M, Y H:i:s') }}</p>
                        </div>
                        <button wire:click="closeDrawer" @click="open = false" type="button"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    @if ($viewLogType === 'api')
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $viewingLog->success ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                                {{ $viewingLog->success ? 'Success' : 'Failed' }}
                            </span>
                            @if ($viewingLog->http_status)
                                <span class="inline-flex text-[11px] font-mono px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">HTTP {{ $viewingLog->http_status }}</span>
                            @endif
                            <span class="inline-flex text-[11px] font-mono px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $viewingLog->duration_ms }}ms</span>
                            @if ($viewingLog->shipment)
                                <a href="{{ route('admin.settings.advance.courier.shipments.show', $viewingLog->shipment->id) }}" wire:navigate
                                    class="text-[11px] font-medium text-indigo-600 hover:text-indigo-700 ml-auto">
                                    View Shipment #{{ $viewingLog->shipment->id }} →
                                </a>
                            @endif
                        </div>

                        @if ($viewingLog->error_message)
                            <div class="rounded-lg bg-red-50 border border-red-100 px-3 py-2.5">
                                <p class="text-xs font-semibold text-red-700 mb-0.5">{{ $viewingLog->error_code ?? 'Error' }}</p>
                                <p class="text-xs text-red-600">{{ $viewingLog->error_message }}</p>
                            </div>
                        @endif

                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Request Sent</p>
                            <pre class="text-xs bg-gray-50 border border-gray-200 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-words">{{ json_encode(json_decode($viewingLog->request_payload ?? 'null'), JSON_PRETTY_PRINT) ?: '—' }}</pre>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Response Received</p>
                            <pre class="text-xs bg-gray-50 border border-gray-200 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-words">{{ json_encode(json_decode($viewingLog->response_payload ?? 'null'), JSON_PRETTY_PRINT) ?: '—' }}</pre>
                        </div>
                    @else
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full
                                {{ $viewingLog->status === 'processed' ? 'bg-emerald-100 text-emerald-700' : ($viewingLog->status === 'unmatched' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600') }}">
                                {{ ucfirst($viewingLog->status) }}
                            </span>
                            <span class="inline-flex text-[11px] font-mono px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $viewingLog->ip_address }}</span>
                            @if ($viewingLog->signature_status)
                                <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $viewingLog->signature_status === 'valid' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                                    Secret {{ $viewingLog->signature_status === 'valid' ? 'verified' : 'invalid' }}
                                </span>
                            @endif
                            @if ($viewingLog->shipment)
                                <a href="{{ route('admin.settings.advance.courier.shipments.show', $viewingLog->shipment->id) }}" wire:navigate
                                    class="text-[11px] font-medium text-indigo-600 hover:text-indigo-700 ml-auto">
                                    View Shipment #{{ $viewingLog->shipment->id }} →
                                </a>
                            @endif
                        </div>

                        @if ($viewingLog->error_message)
                            <div class="rounded-lg bg-red-50 border border-red-100 px-3 py-2.5">
                                <p class="text-xs text-red-600">{{ $viewingLog->error_message }}</p>
                            </div>
                        @endif

                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Headers</p>
                            <pre class="text-xs bg-gray-50 border border-gray-200 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-words">{{ json_encode($viewingLog->headers, JSON_PRETTY_PRINT) ?: '—' }}</pre>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Payload Received</p>
                            <pre class="text-xs bg-gray-50 border border-gray-200 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-words">{{ json_encode($viewingLog->payload, JSON_PRETTY_PRINT) ?: '—' }}</pre>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

</div>
