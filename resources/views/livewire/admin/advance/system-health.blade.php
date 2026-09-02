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
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
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
            <div class="flex items-center gap-2 shrink-0">
                <button wire:click="clearCache" type="button"
                    wire:confirm="Clear the application cache? Cached settings, views, and other cached data will be rebuilt on next use."
                    class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Clear Cache
                </button>
                <button wire:click="clearConfig" type="button"
                    wire:confirm="Clear the configuration cache? This forces config files (including .env) to be re-read on the next request."
                    class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    Clear Config
                </button>
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
