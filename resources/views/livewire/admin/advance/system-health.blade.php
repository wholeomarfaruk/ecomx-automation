<div x-data x-init="$store.pageName = { name: 'System Health', slug: 'system-health' }" class="space-y-6">

    @php
        $badge = fn ($ok) => match (true) {
            $ok === true => 'bg-amber-100 text-amber-700',
            $ok === false => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-500',
        };
        $badgeLabel = fn ($ok, $okLabel = 'Healthy', $failLabel = 'Issue') => match (true) {
            $ok === true => $okLabel,
            $ok === false => $failLabel,
            default => 'Unknown',
        };
    @endphp

    {{-- ══════════════════ SUMMARY GRID ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">System Health</h2>
                <p class="text-xs text-gray-400">Live checks across runtime, services, and host resources</p>
            </div>
        </div>

        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            {{-- PHP Version --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">PHP Version</p>
                <p class="text-sm text-gray-700 font-mono">{{ $phpVersion }}</p>
            </div>

            {{-- Laravel Version --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Laravel Version</p>
                <p class="text-sm text-gray-700 font-mono">{{ $laravelVersion }}</p>
            </div>

            {{-- MySQL Version --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">MySQL Version</p>
                <div class="flex items-center gap-2">
                    <p class="text-sm text-gray-700 font-mono">{{ $mysqlVersion['value'] }}</p>
                    <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $badge($mysqlVersion['ok']) }}">
                        {{ $badgeLabel($mysqlVersion['ok'], 'OK', 'Failed') }}
                    </span>
                </div>
            </div>

            {{-- Redis Status --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Redis Status</p>
                <div class="flex items-center gap-2">
                    <p class="text-sm text-gray-700">{{ $redisStatus['value'] }}</p>
                    <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $badge($redisStatus['ok']) }}">
                        {{ $badgeLabel($redisStatus['ok']) }}
                    </span>
                </div>
            </div>

            {{-- Queue Status --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Queue Status</p>
                <p class="text-sm text-gray-700 font-mono">{{ $queueStatus['driver'] }}</p>
                <p class="text-xs text-gray-400 mt-1">Failed jobs: {{ $queueStatus['failed'] ?? 'N/A' }}</p>
            </div>

            {{-- Scheduler Status --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Scheduler Status</p>
                <div class="flex items-center gap-2">
                    <p class="text-sm text-gray-700">Last run: {{ $schedulerStatus['value'] }}</p>
                    <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $badge($schedulerStatus['ok']) }}">
                        {{ $badgeLabel($schedulerStatus['ok']) }}
                    </span>
                </div>
            </div>

            {{-- Storage Permission --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Storage Permission</p>
                <div class="flex items-center gap-2">
                    <p class="text-sm text-gray-700">{{ $storagePermission['value'] }}</p>
                    <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $badge($storagePermission['ok']) }}">
                        {{ $badgeLabel($storagePermission['ok']) }}
                    </span>
                </div>
            </div>

            {{-- Cache Permission --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Cache Permission</p>
                <div class="flex items-center gap-2">
                    <p class="text-sm text-gray-700">{{ $cachePermission['value'] }}</p>
                    <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $badge($cachePermission['ok']) }}">
                        {{ $badgeLabel($cachePermission['ok']) }}
                    </span>
                </div>
            </div>

            {{-- Disk Usage --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Disk Usage</p>
                <p class="text-sm text-gray-700">{{ $diskUsage['value'] }}</p>
                @if ($diskUsage['percent'] !== null)
                    <div class="w-full h-1.5 bg-gray-100 rounded-full mt-2 overflow-hidden">
                        <div class="h-full rounded-full {{ $diskUsage['ok'] ? 'bg-emerald-500' : 'bg-red-500' }}"
                            style="width: {{ min($diskUsage['percent'], 100) }}%"></div>
                    </div>
                @endif
            </div>

            {{-- Memory Usage --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Memory Usage</p>
                <p class="text-sm text-gray-700">{{ $memoryUsage['value'] }}</p>
            </div>

            {{-- CPU Usage --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">CPU Usage</p>
                <p class="text-sm text-gray-700">{{ $cpuUsage['value'] }}</p>
            </div>

            {{-- Environment Health --}}
            <div class="border border-gray-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Environment Health</p>
                <div class="space-y-1">
                    @foreach ($environmentHealth['checks'] as $label => $ok)
                        <div class="flex items-center gap-2">
                            <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $badge($ok) }}">
                                {{ $badgeLabel($ok) }}
                            </span>
                            <span class="text-xs text-gray-600">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

</div>
