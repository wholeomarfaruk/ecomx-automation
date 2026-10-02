{{-- Live: refresh every 10s while the toggle is on and the tab is visible --}}
<div x-data="{
        timer: null,
        init() {
            this.$store.pageName = { name: 'Visits Url', slug: 'visits' };
            this.timer = setInterval(() => { if (this.$wire.live && document.visibilityState === 'visible') this.$wire.$refresh(); }, 10000);
        },
        destroy() { clearInterval(this.timer); },
    }" class="space-y-6">

    @php
        $statusBadge = fn (int $code) => match (true) {
            $code >= 500 => 'bg-red-100 text-red-700',
            $code >= 400 => 'bg-amber-100 text-amber-700',
            $code >= 300 => 'bg-sky-100 text-sky-700',
            default      => 'bg-emerald-100 text-emerald-700',
        };
        $durationClass = fn (int $ms) => match (true) {
            $ms >= $slowMs => 'text-red-600 font-semibold',
            $ms >= 300     => 'text-amber-600',
            default        => 'text-emerald-600',
        };
        $hasFilters = $search || $statusGroup || $statusCode || $visitor || $method || $area || $type || $slowOnly || $deviceId || $ip;
        $inputClass = 'rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:bg-white focus:ring-0 focus:outline-none transition';
    @endphp

    {{-- Header: period, live refresh, export --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                @foreach (['1h' => 'Last hour', '24h' => '24 hours', '7d' => '7 days', '30d' => '30 days', 'custom' => 'Custom'] as $key => $label)
                    <button wire:click="$set('period', '{{ $key }}')" type="button"
                        class="px-3 py-1.5 rounded-md text-xs font-medium {{ $period === $key ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @if ($period === 'custom')
                <input type="date" wire:model.live="dateFrom" class="{{ $inputClass }}" aria-label="From">
                <span class="text-gray-400 text-sm">to</span>
                <input type="date" wire:model.live="dateTo" class="{{ $inputClass }}" aria-label="To">
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" wire:model.live="live" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="inline-flex items-center gap-1.5">
                    @if ($live)<span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>@endif
                    Live (10s)
                </span>
            </label>
            <button wire:click="export" type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export CSV
            </button>
            @if (auth()->user()->hasRole('superadmin'))
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                        Purge
                    </button>
                    <div x-cloak x-show="open" @click.outside="open = false"
                        class="absolute right-0 mt-2 w-52 rounded-lg border border-gray-200 bg-white shadow-lg z-20 py-1 text-sm">
                        @foreach ([7 => 'Older than 7 days', 3 => 'Older than 3 days', 1 => 'Older than 1 day', 0 => 'Everything'] as $days => $label)
                            <button type="button" @click="open = false" wire:click="purgeOlderThan({{ $days }})"
                                wire:confirm="Delete request logs: {{ strtolower($label) }}? This can't be undone."
                                class="block w-full text-left px-4 py-2 text-gray-600 hover:bg-gray-50">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @unless ($loggingEnabled)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Request logging is turned off (<code>REQUEST_LOG_ENABLED=false</code>) — nothing new is being recorded.
        </div>
    @endunless

    {{-- Stats for the selected period; the error/slow cards are shortcuts to those filters --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Requests</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ number_format((int) $stats->total) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Visitors</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ number_format((int) $stats->visitors) }}</p>
        </div>
        <button type="button" wire:click="quickFilter('active')" class="text-left bg-white rounded-xl border border-gray-200 px-5 py-4 hover:border-emerald-300 transition">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Active now</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($activeNow) }}</p>
        </button>
        <button type="button" wire:click="quickFilter('5xx')" class="text-left bg-white rounded-xl border border-gray-200 px-5 py-4 hover:border-red-300 transition">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Server errors 5xx</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format((int) $stats->server_errors) }}</p>
        </button>
        <button type="button" wire:click="quickFilter('4xx')" class="text-left bg-white rounded-xl border border-gray-200 px-5 py-4 hover:border-amber-300 transition">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Client errors 4xx</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ number_format((int) $stats->client_errors) }}</p>
        </button>
        <button type="button" wire:click="quickFilter('slow')" class="text-left bg-white rounded-xl border border-gray-200 px-5 py-4 hover:border-red-300 transition">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Slow ≥ {{ number_format($slowMs) }}ms</p>
            <p class="mt-1 text-2xl font-bold text-red-500">{{ number_format((int) $stats->slow) }}</p>
        </button>
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Avg response</p>
            <p class="mt-1 text-2xl font-bold {{ $durationClass((int) $stats->avg_ms) }}">{{ number_format((int) $stats->avg_ms) }}<span class="text-sm font-medium text-gray-400">ms</span></p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        {{-- Tabs --}}
        <div class="px-5 pt-4 border-b border-gray-100 flex gap-5 overflow-x-auto">
            @foreach (['requests' => 'All requests', 'errors' => 'Errors', 'slow' => 'Slowest', 'pages' => 'Top pages'] as $key => $label)
                <button wire:click="$set('tab', '{{ $key }}')" type="button"
                    class="pb-3 text-sm font-medium border-b-2 whitespace-nowrap {{ $tab === $key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($tab === 'requests')
            {{-- Filters --}}
            <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-3">
                <div class="relative w-full lg:flex-1 lg:min-w-64">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search URL, route, Livewire action, error…" autocomplete="off"
                        class="w-full {{ $inputClass }} pl-9">
                </div>

                <select wire:model.live="statusGroup" class="{{ $inputClass }}">
                    <option value="">All statuses</option>
                    <option value="2xx">2xx Success</option>
                    <option value="3xx">3xx Redirect</option>
                    <option value="errors">4xx + 5xx (all errors)</option>
                    <option value="4xx">4xx Client error</option>
                    <option value="5xx">5xx Server error</option>
                </select>

                <input wire:model.live.debounce.400ms="statusCode" type="text" inputmode="numeric" maxlength="3" placeholder="Code e.g. 404"
                    class="w-32 {{ $inputClass }}">

                <select wire:model.live="visitor" class="{{ $inputClass }}">
                    <option value="">All visitors</option>
                    <option value="active">Active now</option>
                    <option value="inactive">Inactive</option>
                </select>

                <select wire:model.live="type" class="{{ $inputClass }}">
                    <option value="">All types</option>
                    <option value="page">Page load</option>
                    <option value="livewire">Livewire action</option>
                    <option value="ajax">AJAX / JSON</option>
                    <option value="other">Other (form post…)</option>
                </select>

                <select wire:model.live="area" class="{{ $inputClass }}">
                    <option value="">All areas</option>
                    <option value="storefront">Storefront</option>
                    <option value="admin">Admin</option>
                    <option value="api">API</option>
                </select>

                <select wire:model.live="method" class="{{ $inputClass }}">
                    <option value="">All methods</option>
                    @foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>

                <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" wire:model.live="slowOnly" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    Slow only
                </label>

                @if ($hasFilters)
                    <button wire:click="clearFilters" type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700 underline">Clear filters</button>
                @endif
            </div>

            @if ($deviceId || $ip)
                <div class="px-5 py-2 border-b border-gray-100 flex flex-wrap gap-2 bg-gray-50">
                    @if ($deviceId)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 text-indigo-700 px-3 py-1 text-xs font-medium">
                            Device #{{ $deviceId }}
                            <button type="button" wire:click="$set('deviceId', '')" aria-label="Remove device filter">✕</button>
                        </span>
                    @endif
                    @if ($ip)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 text-indigo-700 px-3 py-1 text-xs font-medium">
                            IP {{ $ip }}
                            <button type="button" wire:click="$set('ip', '')" aria-label="Remove IP filter">✕</button>
                        </span>
                    @endif
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                            <th class="px-5 py-3">Time</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Request</th>
                            <th class="px-5 py-3 text-right">Time taken</th>
                            <th class="px-5 py-3">Visitor</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            @php $isActive = $log->device_id && $activeDeviceIds->has($log->device_id); @endphp
                            <tr wire:key="log-{{ $log->id }}" class="hover:bg-gray-50 align-top {{ $log->status_code >= 500 ? 'bg-red-50/40' : '' }}">
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <p class="text-xs text-gray-700">{{ local_time($log->created_at)->format('d M, H:i:s') }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $log->created_at->diffForHumans() }}</p>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <button type="button" wire:click="filterBy('status', '{{ $log->status_code }}')" title="Show only {{ $log->status_code }}"
                                        class="inline-flex text-[11px] font-bold font-mono px-2 py-0.5 rounded-full {{ $statusBadge($log->status_code) }}">
                                        {{ $log->status_code }}
                                    </button>
                                </td>
                                <td class="px-5 py-3 max-w-xl">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-[10px] font-bold font-mono text-gray-500 bg-gray-100 rounded px-1.5 py-0.5">{{ $log->method }}</span>
                                        <span class="text-[10px] font-medium text-gray-500 bg-gray-100 rounded px-1.5 py-0.5">{{ $log->type }}</span>
                                        @if ($log->area !== 'storefront')
                                            <span class="text-[10px] font-medium text-violet-700 bg-violet-100 rounded px-1.5 py-0.5">{{ $log->area }}</span>
                                        @endif
                                        <button type="button" wire:click="filterBy('path', @js($log->path))" class="font-mono text-xs text-gray-800 hover:text-indigo-600 break-all text-left">{{ $log->path }}</button>
                                    </div>
                                    @if ($log->livewire_action)
                                        <p class="mt-1 font-mono text-[11px] text-indigo-600 break-all">{{ $log->livewire_action }}</p>
                                    @endif
                                    @if ($log->exception_class)
                                        <p class="mt-1 text-[11px] text-red-600 break-words">{{ class_basename($log->exception_class) }}: {{ \Illuminate\Support\Str::limit($log->exception_message, 140) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <p class="font-mono text-xs {{ $durationClass($log->duration_ms) }}">{{ number_format($log->duration_ms) }}ms</p>
                                    <p class="text-[11px] text-gray-400">{{ $log->query_count }} queries</p>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-gray-300' }}" title="{{ $isActive ? 'Active now' : 'Inactive' }}"></span>
                                        @if ($log->device_id)
                                            <button type="button" wire:click="filterBy('device', '{{ $log->device_id }}')" class="text-xs text-gray-700 hover:text-indigo-600">
                                                {{ $log->device?->browser ?? 'Device' }} · {{ $log->device?->operating_system ?? '#' . $log->device_id }}
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">No device</span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-1.5 pl-3.5">
                                        @if ($log->ip_address)
                                            <button type="button" wire:click="filterBy('ip', '{{ $log->ip_address }}')" class="font-mono text-[11px] text-gray-400 hover:text-indigo-600">{{ $log->ip_address }}</button>
                                        @endif
                                        @if ($log->user)
                                            <span class="text-[11px] text-gray-500">· {{ $log->user->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button wire:click="viewLog({{ $log->id }})" type="button"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">No requests match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
            @endif

        @elseif ($tab === 'errors')
            <p class="px-5 pt-4 text-xs text-gray-500">Every URL that answered 4xx/5xx in this period, grouped by URL and code — worst first.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">URL / error</th>
                            <th class="px-5 py-3 text-right">Hits</th>
                            <th class="px-5 py-3 text-right">Visitors</th>
                            <th class="px-5 py-3">Last seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($errors as $row)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-5 py-3">
                                    <span class="inline-flex text-[11px] font-bold font-mono px-2 py-0.5 rounded-full {{ $statusBadge((int) $row->status_code) }}">{{ $row->status_code }}</span>
                                </td>
                                <td class="px-5 py-3 max-w-xl">
                                    <button type="button" wire:click="filterBy('path', @js($row->path))" class="font-mono text-xs text-gray-800 hover:text-indigo-600 break-all text-left">{{ $row->path }}</button>
                                    @if ($row->exception_class)
                                        <p class="mt-1 text-[11px] text-red-600 break-words">{{ class_basename($row->exception_class) }}: {{ \Illuminate\Support\Str::limit($row->exception_message, 160) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-800">{{ number_format($row->hits) }}</td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ number_format($row->visitors) }}</td>
                                <td class="px-5 py-3 text-xs text-gray-400 whitespace-nowrap">{{ local_time($row->last_seen)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">No errors in this period. 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif ($tab === 'slow')
            <p class="px-5 pt-4 text-xs text-gray-500">Pages and Livewire actions by average time taken in this period — what to speed up first.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                            <th class="px-5 py-3">Page / action</th>
                            <th class="px-5 py-3 text-right">Hits</th>
                            <th class="px-5 py-3 text-right">Avg</th>
                            <th class="px-5 py-3 text-right">Max</th>
                            <th class="px-5 py-3 text-right">Avg queries</th>
                            <th class="px-5 py-3 text-right">Slow hits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($slow as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 max-w-xl">
                                    <span class="text-[10px] font-medium text-gray-500 bg-gray-100 rounded px-1.5 py-0.5 mr-1">{{ $row->type }}</span>
                                    <button type="button" wire:click="filterBy('path', @js($row->target))" class="font-mono text-xs text-gray-800 hover:text-indigo-600 break-all text-left">{{ $row->target }}</button>
                                </td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ number_format($row->hits) }}</td>
                                <td class="px-5 py-3 text-right font-mono text-xs {{ $durationClass((int) $row->avg_ms) }}">{{ number_format($row->avg_ms) }}ms</td>
                                <td class="px-5 py-3 text-right font-mono text-xs {{ $durationClass((int) $row->max_ms) }}">{{ number_format($row->max_ms) }}ms</td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ number_format($row->avg_queries) }}</td>
                                <td class="px-5 py-3 text-right {{ $row->slow_hits ? 'text-red-600 font-semibold' : 'text-gray-400' }}">{{ number_format($row->slow_hits) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">No requests in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @else
            <p class="px-5 pt-4 text-xs text-gray-500">Most visited storefront pages in this period.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                            <th class="px-5 py-3">Page</th>
                            <th class="px-5 py-3 text-right">Views</th>
                            <th class="px-5 py-3 text-right">Visitors</th>
                            <th class="px-5 py-3 text-right">Avg load</th>
                            <th class="px-5 py-3 text-right">Errors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($pages as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 max-w-xl">
                                    <button type="button" wire:click="filterBy('path', @js($row->path))" class="font-mono text-xs text-gray-800 hover:text-indigo-600 break-all text-left">{{ $row->path }}</button>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-800">{{ number_format($row->hits) }}</td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ number_format($row->visitors) }}</td>
                                <td class="px-5 py-3 text-right font-mono text-xs {{ $durationClass((int) $row->avg_ms) }}">{{ number_format($row->avg_ms) }}ms</td>
                                <td class="px-5 py-3 text-right {{ $row->errors ? 'text-red-600 font-semibold' : 'text-gray-400' }}">{{ number_format($row->errors) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">No page views in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Detail drawer --}}
    @if ($viewing)
        <div class="fixed inset-0 bg-gray-900/40 z-40" wire:click="closeDrawer"></div>
        <div class="fixed top-0 right-0 h-screen w-full max-w-xl bg-white shadow-2xl z-50 overflow-y-auto">
            <div class="p-6 space-y-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex text-[11px] font-bold font-mono px-2 py-0.5 rounded-full {{ $statusBadge($viewing->status_code) }}">{{ $viewing->status_code }}</span>
                            <span class="text-[11px] font-bold font-mono text-gray-500 bg-gray-100 rounded px-1.5 py-0.5">{{ $viewing->method }}</span>
                            <span class="text-[11px] font-mono {{ $durationClass($viewing->duration_ms) }}">{{ number_format($viewing->duration_ms) }}ms</span>
                        </div>
                        <h2 class="mt-2 font-mono text-sm text-gray-900 break-all">{{ $viewing->path }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ local_time($viewing->created_at)->format('d M Y, H:i:s') }} · {{ $viewing->created_at->diffForHumans() }}</p>
                    </div>
                    <button wire:click="closeDrawer" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition shrink-0" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                @if ($viewing->exception_class)
                    <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3">
                        <p class="text-xs font-semibold text-red-700 break-all">{{ $viewing->exception_class }}</p>
                        <p class="mt-1 text-xs text-red-600 whitespace-pre-wrap break-words">{{ $viewing->exception_message }}</p>
                        @if ($viewing->exception_location)
                            <p class="mt-2 font-mono text-[11px] text-red-500 break-all">{{ $viewing->exception_location }}</p>
                        @endif
                    </div>
                @endif

                @php
                    $rows = [
                        'Full URL'        => $viewing->url,
                        'Route'           => $viewing->route_name,
                        'Livewire action' => $viewing->livewire_action,
                        'Type / area'     => $viewing->type . ' · ' . $viewing->area,
                        'DB queries'      => $viewing->query_count,
                        'Peak memory'     => $viewing->memory_mb . ' MB',
                        'IP address'      => $viewing->ip_address,
                        'Referer'         => $viewing->referer,
                        'User'            => $viewing->user ? $viewing->user->name . ' (' . $viewing->user->email . ')' : null,
                        'User agent'      => $viewing->user_agent,
                    ];
                @endphp
                <dl class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                    @foreach ($rows as $label => $value)
                        @if ($value !== null && $value !== '')
                            <div class="px-4 py-2.5 grid grid-cols-3 gap-3">
                                <dt class="text-xs font-medium text-gray-500">{{ $label }}</dt>
                                <dd class="col-span-2 text-xs text-gray-800 font-mono break-all">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>

                @if ($viewing->device)
                    <div class="rounded-lg border border-gray-200 px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Device #{{ $viewing->device->id }}</p>
                            <a href="{{ route('admin.users.devices.show', $viewing->device->id) }}" wire:navigate class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Open device →</a>
                        </div>
                        <p class="mt-1 text-sm text-gray-800">
                            {{ $viewing->device->browser ?? 'Unknown browser' }} {{ $viewing->device->browser_version }}
                            · {{ $viewing->device->operating_system ?? 'Unknown OS' }} {{ $viewing->device->os_version }}
                            · {{ $viewing->device->device_type }}
                        </p>
                        <p class="mt-1 text-xs {{ $activeDeviceIds->has($viewing->device->id) ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $activeDeviceIds->has($viewing->device->id) ? '● Active now' : '○ Inactive' }}
                        </p>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2">
                    @if ($viewing->device_id)
                        <button type="button" wire:click="filterBy('device', '{{ $viewing->device_id }}')" class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100">All requests from this device</button>
                    @endif
                    @if ($viewing->ip_address)
                        <button type="button" wire:click="filterBy('ip', '{{ $viewing->ip_address }}')" class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100">All requests from this IP</button>
                    @endif
                    <button type="button" wire:click="filterBy('path', @js($viewing->path))" class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100">All requests to this URL</button>
                </div>
            </div>
        </div>
    @endif

</div>
