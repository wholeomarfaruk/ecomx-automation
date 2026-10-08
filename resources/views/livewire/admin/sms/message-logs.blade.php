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
                        <th class="text-right px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Action</th>
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
                            <td class="px-6 py-3 text-right">
                                <button wire:click="viewLog({{ $log->id }})" type="button"
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
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">No messages logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $logs->links() }}</div>
    </div>

    {{-- Log Detail Drawer (same pattern as Courier > Logs) --}}
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
                            <h2 class="text-base font-semibold text-gray-900 font-mono">{{ $viewingLog->to }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $viewingLog->driver_key }} · {{ $viewingLog->created_at->format('d M, Y H:i:s') }}</p>
                        </div>
                        <button wire:click="closeDrawer" @click="open = false" type="button"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full
                            {{ in_array($viewingLog->status, ['sent', 'delivered']) ? 'bg-emerald-100 text-emerald-700' : ($viewingLog->status === 'failed' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500') }}">
                            {{ ucfirst($viewingLog->status) }}
                        </span>
                        @if ($viewingLog->context)
                            <span class="inline-flex text-[11px] font-mono px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $viewingLog->context }}</span>
                        @endif
                        @if ($viewingLog->message_id)
                            <span class="inline-flex text-[11px] font-mono px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">ID {{ $viewingLog->message_id }}</span>
                        @endif
                        @if ($viewingLog->cost !== null)
                            <span class="inline-flex text-[11px] font-mono px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Cost {{ $viewingLog->cost }}</span>
                        @endif
                        <span class="inline-flex text-[11px] font-mono px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Retries {{ $viewingLog->retry_count }}</span>
                    </div>

                    @if ($viewingLog->error_message)
                        <div class="rounded-lg bg-red-50 border border-red-100 px-3 py-2.5">
                            <p class="text-xs font-semibold text-red-700 mb-0.5">{{ $viewingLog->error_code ?? 'Error' }}</p>
                            <p class="text-xs text-red-600">{{ $viewingLog->error_message }}</p>
                        </div>
                    @endif

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Message</p>
                        <p class="text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded-lg p-4 whitespace-pre-wrap break-words">{{ $viewingLog->message }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Request Sent</p>
                        <pre class="text-xs bg-gray-50 border border-gray-200 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-words">{{ $viewingLog->request_payload ? json_encode($viewingLog->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '— (not recorded for older messages)' }}</pre>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Response Received</p>
                        <pre class="text-xs bg-gray-50 border border-gray-200 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-words">{{ $viewingLog->raw_response ? json_encode($viewingLog->raw_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '—' }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
