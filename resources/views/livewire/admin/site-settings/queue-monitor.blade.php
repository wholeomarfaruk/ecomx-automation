<div wire:poll.10s class="px-6 py-5 border-b border-gray-100 space-y-4">
    @php
        $statusMap = [
            'healthy' => ['bg-emerald-50 border-emerald-200', 'bg-emerald-500', 'text-emerald-800', 'Queue is working',
                'A worker checked in '.($health['worker_seen_at']?->diffForHumans() ?? '').' and jobs are being processed.'],
            'slow'    => ['bg-amber-50 border-amber-200', 'bg-amber-500', 'text-amber-800', 'Queue is falling behind',
                'A worker is running, but the oldest waiting job has been waiting '.\Carbon\CarbonInterval::seconds($health['oldest_wait_seconds'])->cascade()->forHumans(['short' => true]).'.'],
            'stuck'   => ['bg-red-50 border-red-200', 'bg-red-500', 'text-red-800', 'Queue is stuck — jobs are not being processed',
                $health['worker_seen_at']
                    ? 'Jobs are waiting but no worker has checked in since '.$health['worker_seen_at']->diffForHumans().'. Check the cron / worker command and its --queue list.'
                    : 'Jobs are waiting but no worker has ever checked in. Check the cron / worker command and its --queue list.'],
            'idle'    => ['bg-amber-50 border-amber-200', 'bg-amber-500', 'text-amber-800', 'No worker seen recently',
                'Nothing is waiting right now, but the last worker check-in was '.$health['worker_seen_at']?->diffForHumans().'. Send a test job to confirm.'],
            'unknown' => ['bg-amber-50 border-amber-200', 'bg-amber-500', 'text-amber-800', 'No worker heartbeat yet',
                'No worker has reported in since monitoring was enabled. Send a test job — if it stays waiting, no worker is running.'],
            'sync'    => ['bg-gray-50 border-gray-200', 'bg-gray-400', 'text-gray-700', 'Sync mode — no worker needed',
                'QUEUE_CONNECTION=sync runs every job immediately inside the request.'],
        ];
        [$boxClass, $dotClass, $textClass, $title, $description] = $statusMap[$health['status']];
        $pulse = in_array($health['status'], ['healthy', 'stuck'], true);
    @endphp

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Live Monitor</p>
            <p class="text-xs text-gray-400 mt-0.5">Auto-refreshes every 10s · updated {{ now()->format('h:i:s A') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="$refresh" type="button"
                class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" wire:loading.class="animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Refresh
            </button>
            <button wire:click="sendTestJob" type="button" wire:loading.attr="disabled" wire:target="sendTestJob"
                class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition disabled:opacity-60">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                Send test job
            </button>
        </div>
    </div>

    {{-- Status banner --}}
    <div class="rounded-xl border px-4 py-3 flex items-start gap-3 {{ $boxClass }}">
        <span class="relative flex h-3 w-3 mt-1 shrink-0">
            @if ($pulse)
                <span class="absolute inline-flex h-full w-full rounded-full opacity-60 animate-ping {{ $dotClass }}"></span>
            @endif
            <span class="relative inline-flex h-3 w-3 rounded-full {{ $dotClass }}"></span>
        </span>
        <div class="min-w-0">
            <p class="text-sm font-semibold {{ $textClass }}">{{ $title }}</p>
            <p class="text-xs {{ $textClass }} opacity-80 mt-0.5">{{ $description }}</p>
        </div>
    </div>

    {{-- Test job result --}}
    @if ($health['test'])
        <div class="rounded-lg border border-gray-100 px-4 py-2.5 text-xs flex items-center gap-2 flex-wrap">
            <span class="font-medium text-gray-700">Test job</span>
            <span class="text-gray-400">sent {{ $health['test']['dispatched_at']->format('h:i:s A') }}</span>
            @if ($health['test']['processed_at'])
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">
                    ✓ Processed in {{ $health['test']['took_seconds'] }}s
                </span>
                <span class="text-gray-400">at {{ $health['test']['processed_at']->format('h:i:s A') }}</span>
            @elseif ($health['test']['waiting_seconds'] > 120)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">
                    ✗ Still waiting after {{ \Carbon\CarbonInterval::seconds($health['test']['waiting_seconds'])->cascade()->forHumans(['short' => true]) }} — no worker is picking up the default queue
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">
                    Waiting for a worker… {{ $health['test']['waiting_seconds'] }}s
                </span>
                <span class="text-gray-400">(a cron worker can take up to a minute)</span>
            @endif
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="border border-gray-100 rounded-xl p-3">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Worker last seen</p>
            @if ($health['driver'] === 'sync')
                <p class="text-sm font-semibold text-gray-500 mt-1">Not needed</p>
            @elseif ($health['worker_seen_at'])
                <p class="text-sm font-semibold mt-1 {{ $health['worker_seen_at']->diffInSeconds(now()) <= $staleAfterMinutes * 60 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $health['worker_seen_at']->diffForHumans() }}
                </p>
                <p class="text-[11px] text-gray-400">{{ $health['worker_seen_at']->format('d M, h:i:s A') }}</p>
            @else
                <p class="text-sm font-semibold text-red-600 mt-1">Never</p>
            @endif
        </div>

        <div class="border border-gray-100 rounded-xl p-3">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Waiting jobs</p>
            @if ($health['driver'] === 'database')
                <p class="text-xl font-semibold mt-0.5 {{ $health['ready'] > 0 && $health['oldest_wait_seconds'] > 120 ? 'text-red-600' : 'text-gray-800' }}">{{ number_format($health['ready']) }}</p>
                <p class="text-[11px] text-gray-400">
                    @if ($health['ready'] > 0)
                        oldest {{ \Carbon\CarbonInterval::seconds($health['oldest_wait_seconds'])->cascade()->forHumans(['short' => true]) }}
                    @else
                        queue is empty
                    @endif
                    @if ($health['running'] || $health['delayed'])
                        · {{ $health['running'] }} running · {{ $health['delayed'] }} retrying later
                    @endif
                </p>
            @else
                <p class="text-sm font-semibold text-gray-500 mt-1">—</p>
                <p class="text-[11px] text-gray-400">only tracked for the database driver</p>
            @endif
        </div>

        <div class="border border-gray-100 rounded-xl p-3">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Today</p>
            <p class="text-xl font-semibold text-gray-800 mt-0.5">
                {{ number_format($health['processed_today']) }}
                <span class="text-xs font-medium text-gray-400">done</span>
            </p>
            <p class="text-[11px] {{ $health['failed_today'] ? 'text-red-600 font-medium' : 'text-gray-400' }}">{{ number_format($health['failed_today']) }} failed</p>
        </div>

        <div class="border border-gray-100 rounded-xl p-3">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Last job processed</p>
            @if ($health['last_processed'])
                <p class="text-sm font-semibold text-gray-800 mt-1 truncate" title="{{ $health['last_processed']['job'] }}">{{ $health['last_processed']['job'] }}</p>
                <p class="text-[11px] text-gray-400">{{ $health['last_processed']['at']->diffForHumans() }} · {{ $health['last_processed']['queue'] }}</p>
            @else
                <p class="text-sm font-semibold text-gray-500 mt-1">None yet</p>
            @endif
        </div>
    </div>

    {{-- Per-queue breakdown --}}
    @if (count($health['queues']) > 0)
        <div class="border border-gray-100 rounded-xl overflow-hidden">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="text-left font-medium px-3 py-2">Queue</th>
                        <th class="text-right font-medium px-3 py-2">Waiting</th>
                        <th class="text-right font-medium px-3 py-2">Running</th>
                        <th class="text-right font-medium px-3 py-2">Retrying later</th>
                        <th class="text-right font-medium px-3 py-2">Oldest waiting</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($health['queues'] as $queue)
                        <tr>
                            <td class="px-3 py-2 font-mono text-gray-700">{{ $queue['queue'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $queue['ready'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $queue['running'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $queue['delayed'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-500">
                                {{ $queue['oldest_ready_at'] ? \Carbon\Carbon::createFromTimestamp($queue['oldest_ready_at'], config('app.timezone'))->diffForHumans() : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-[11px] text-gray-400 -mt-2">A worker only processes the queues named in its <code class="bg-gray-100 px-1 rounded">--queue=</code> list — every queue above must be in it.</p>
    @endif

    {{-- Failed jobs --}}
    @if ($health['failed']['supported'])
        <div class="border border-gray-100 rounded-xl overflow-hidden">
            <div class="px-3 py-2 bg-gray-50 flex items-center justify-between gap-2 flex-wrap">
                <p class="text-xs font-medium text-gray-600">
                    Failed jobs
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[11px] {{ $health['failed']['count'] ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-600' }}">{{ number_format($health['failed']['count']) }}</span>
                </p>
                @if ($health['failed']['count'])
                    <div class="flex items-center gap-2">
                        <button wire:click="retryAllFailed" type="button" wire:confirm="Push every failed job back onto the queue?"
                            class="text-[11px] font-medium px-2.5 py-1 rounded-md bg-white border border-gray-200 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                            Retry all
                        </button>
                        <button wire:click="flushFailed" type="button" wire:confirm="Delete every failed job? This cannot be undone."
                            class="text-[11px] font-medium px-2.5 py-1 rounded-md bg-white border border-gray-200 text-gray-700 hover:bg-red-50 hover:text-red-700 transition">
                            Delete all
                        </button>
                    </div>
                @endif
            </div>
            @forelse ($health['failed']['recent'] as $failed)
                <div class="px-3 py-2 border-t border-gray-100 flex items-start justify-between gap-3" wire:key="failed-{{ $failed['key'] }}">
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-800">
                            {{ $failed['job'] }}
                            <span class="font-normal text-gray-400">· {{ $failed['queue'] }} · {{ $failed['failed_at']->diffForHumans() }}</span>
                        </p>
                        <p class="text-[11px] text-red-600 break-words mt-0.5">{{ $failed['error'] }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button wire:click="retryFailed('{{ $failed['key'] }}')" type="button"
                            class="text-[11px] font-medium px-2 py-1 rounded-md text-indigo-600 hover:bg-indigo-50 transition">Retry</button>
                        <button wire:click="forgetFailed('{{ $failed['key'] }}')" type="button" wire:confirm="Delete this failed job?"
                            class="text-[11px] font-medium px-2 py-1 rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 transition">Delete</button>
                    </div>
                </div>
            @empty
                <p class="px-3 py-3 border-t border-gray-100 text-xs text-gray-400">No failed jobs.</p>
            @endforelse
            @if ($health['failed']['count'] > count($health['failed']['recent']))
                <p class="px-3 py-2 border-t border-gray-100 text-[11px] text-gray-400">Showing the latest {{ count($health['failed']['recent']) }} of {{ number_format($health['failed']['count']) }}.</p>
            @endif
        </div>
    @endif
</div>
