<div x-data="{ expandedLog: null }" x-init="$store.pageName = { name: 'Activity Log', slug: 'activity-log' }">

    {{-- Page Header --}}
    <div class="flex flex-wrap items-center justify-end gap-4 mb-6">
        <div class="flex items-center gap-2">
            @if($logs->total() > 0)
                <button wire:click="export" type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export CSV
                </button>
            @endif
            @php $hasFilters = $search || $filterEvent || $dateFrom || $dateTo || $filterCauser || $filterSubjectType || $filterLogName; @endphp
            @if($hasFilters)
                <button wire:click="clearFilters" type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear Filters
                </button>
            @endif
        </div>
    </div>

    {{-- Stats (single GROUP BY query — no extra DB hits) --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        @php
            $statCards = [
                ['label' => 'Total',    'value' => $stats->total,          'class' => 'text-gray-800'],
                ['label' => 'Created',  'value' => $stats->created_count,  'class' => 'text-green-600'],
                ['label' => 'Updated',  'value' => $stats->updated_count,  'class' => 'text-blue-600'],
                ['label' => 'Deleted',  'value' => $stats->deleted_count,  'class' => 'text-red-500'],
                ['label' => 'Logins',   'value' => $stats->login_count,    'class' => 'text-amber-500'],
            ];
        @endphp
        @foreach($statCards as $card)
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $card['class'] }}">{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        {{-- Toolbar Row 1: Search + Filters --}}
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-3">

            <div class="relative w-full sm:flex-1 sm:min-w-52">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search description…" autocomplete="off"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-4 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:bg-white focus:ring-0 focus:outline-none transition" />
            </div>

            <div class="relative w-full sm:w-auto sm:min-w-44">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <input wire:model.live.debounce.400ms="filterCauser" type="text" placeholder="Filter by user…" autocomplete="off"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-4 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:bg-white focus:ring-0 focus:outline-none transition" />
            </div>

            <select wire:model.live="filterEvent"
                class="w-full sm:w-auto rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-0 focus:outline-none transition">
                <option value="">All Events</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
                <option value="login.failed">Login Failed</option>
                <option value="password.reset">Password Reset</option>
            </select>

            @if($logNames->count() > 1)
                <select wire:model.live="filterLogName"
                    class="w-full sm:w-auto rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-0 focus:outline-none transition">
                    <option value="">All Channels</option>
                    @foreach($logNames as $lname)
                        <option value="{{ $lname }}">{{ ucfirst($lname) }}</option>
                    @endforeach
                </select>
            @endif

            @if($subjectTypes->count() > 0)
                <select wire:model.live="filterSubjectType"
                    class="w-full sm:w-auto rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-0 focus:outline-none transition">
                    <option value="">All Models</option>
                    @foreach($subjectTypes as $type)
                        <option value="{{ $type }}">{{ class_basename($type) }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- Toolbar Row 2: Date range + count --}}
        <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3 bg-gray-50/50">
            <span class="text-xs font-medium text-gray-400">Date range:</span>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input wire:model.live="dateFrom" type="text" placeholder="From" readonly
                    class="flatpickr-only-date rounded-lg border border-gray-200 bg-white pl-8 pr-3 py-1.5 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:ring-0 focus:outline-none transition w-32 cursor-pointer" />
            </div>
            <span class="text-xs text-gray-400">to</span>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input wire:model.live="dateTo" type="text" placeholder="To" readonly
                    class="flatpickr-only-date rounded-lg border border-gray-200 bg-white pl-8 pr-3 py-1.5 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:ring-0 focus:outline-none transition w-32 cursor-pointer" />
            </div>
            <span class="text-xs text-gray-400 sm:ml-auto">
                {{ number_format($logs->total()) }} {{ Str::plural('entry', $logs->total()) }}
            </span>
        </div>

        {{-- Active filter chips --}}
        @if($hasFilters)
            <div class="px-5 py-2.5 border-b border-gray-100 bg-amber-50/40 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500 font-medium">Filters:</span>
                @if($search)           <span class="inline-flex items-center gap-1 text-xs border rounded-full px-2.5 py-0.5 bg-white border-gray-200 text-gray-700">"{{ $search }}" <button wire:click="$set('search','')" class="opacity-60 hover:opacity-100">&times;</button></span> @endif
                @if($filterCauser)     <span class="inline-flex items-center gap-1 text-xs border rounded-full px-2.5 py-0.5 bg-indigo-50 border-indigo-200 text-indigo-700">User: {{ $filterCauser }} <button wire:click="$set('filterCauser','')" class="opacity-60 hover:opacity-100">&times;</button></span> @endif
                @if($filterEvent)      <span class="inline-flex items-center gap-1 text-xs border rounded-full px-2.5 py-0.5 bg-blue-50 border-blue-200 text-blue-700">Event: {{ ucfirst($filterEvent) }} <button wire:click="$set('filterEvent','')" class="opacity-60 hover:opacity-100">&times;</button></span> @endif
                @if($filterLogName)    <span class="inline-flex items-center gap-1 text-xs border rounded-full px-2.5 py-0.5 bg-amber-50 border-amber-200 text-amber-700">Channel: {{ ucfirst($filterLogName) }} <button wire:click="$set('filterLogName','')" class="opacity-60 hover:opacity-100">&times;</button></span> @endif
                @if($filterSubjectType)<span class="inline-flex items-center gap-1 text-xs border rounded-full px-2.5 py-0.5 bg-purple-50 border-purple-200 text-purple-700">Model: {{ class_basename($filterSubjectType) }} <button wire:click="$set('filterSubjectType','')" class="opacity-60 hover:opacity-100">&times;</button></span> @endif
                @if($dateFrom)         <span class="inline-flex items-center gap-1 text-xs border rounded-full px-2.5 py-0.5 bg-white border-gray-200 text-gray-700">From: {{ $dateFrom }} <button wire:click="$set('dateFrom','')" class="opacity-60 hover:opacity-100">&times;</button></span> @endif
                @if($dateTo)           <span class="inline-flex items-center gap-1 text-xs border rounded-full px-2.5 py-0.5 bg-white border-gray-200 text-gray-700">To: {{ $dateTo }} <button wire:click="$set('dateTo','')" class="opacity-60 hover:opacity-100">&times;</button></span> @endif
            </div>
        @endif

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">By</th>
                        <th class="hidden lg:table-cell px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Context</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">When</th>
                        <th class="sticky right-0 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Changes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($logs as $log)
                        @php
                            $eventConfig = [
                                'created'        => ['bg-green-50 text-green-700 ring-green-200',   'bg-green-500'],
                                'updated'        => ['bg-blue-50 text-blue-700 ring-blue-200',      'bg-blue-500'],
                                'deleted'        => ['bg-red-50 text-red-700 ring-red-200',         'bg-red-500'],
                                'login'          => ['bg-amber-50 text-amber-700 ring-amber-200',   'bg-amber-500'],
                                'logout'         => ['bg-gray-100 text-gray-600 ring-gray-200',     'bg-gray-400'],
                                'login.failed'   => ['bg-rose-50 text-rose-700 ring-rose-200',      'bg-rose-500'],
                                'password.reset' => ['bg-purple-50 text-purple-700 ring-purple-200','bg-purple-500'],
                            ];
                            [$badgeClass, $dotClass] = $eventConfig[$log->event] ?? ['bg-gray-100 text-gray-600 ring-gray-200', 'bg-gray-400'];

                            $channelColors = ['auth' => 'bg-amber-100 text-amber-700', 'default' => 'bg-gray-100 text-gray-600'];
                            $channelClass  = $channelColors[$log->log_name] ?? 'bg-blue-100 text-blue-700';

                            $properties = $log->properties ?? collect();
                            $changed    = $properties->get('attributes', []);
                            $old        = $properties->get('old', []);
                            $hasChanges = ! empty($changed) || ! empty($old);
                            $hasProps   = $properties->count() > 0;

                            $ua = \App\Models\ActivityLog::parseUserAgent($log->user_agent);
                        @endphp
                        <tr class="group hover:bg-gray-50/60 transition-colors">

                            {{-- Channel --}}
                            <td class="hidden sm:table-cell px-4 py-3.5">
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $channelClass }}">
                                    {{ $log->log_name ?? '—' }}
                                </span>
                            </td>

                            {{-- Event --}}
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap {{ $badgeClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                    {{ ucfirst($log->event ?? 'log') }}
                                </span>
                            </td>

                            {{-- Description --}}
                            <td class="px-4 py-3.5 max-w-[180px]">
                                <p class="text-sm text-gray-700 truncate" title="{{ $log->description }}">{{ $log->description }}</p>
                                @if($log->event === 'login.failed' && $properties->get('attempted_email'))
                                    <p class="text-[10px] text-gray-400 mt-0.5 truncate">{{ $properties->get('attempted_email') }}</p>
                                @endif
                            </td>

                            {{-- Subject --}}
                            <td class="hidden md:table-cell px-4 py-3.5">
                                @if($log->subject_type)
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs font-medium text-gray-700">{{ class_basename($log->subject_type) }}</span>
                                        <span class="text-xs text-gray-400">#{{ $log->subject_id }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Causer --}}
                            <td class="px-4 py-3.5">
                                @if($log->causer)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ 'https://ui-avatars.com/api/?name=' . urlencode($log->causer->name) . '&background=111827&color=ffffff&bold=true&size=64' }}"
                                            alt="{{ $log->causer->name }}"
                                            class="h-7 w-7 rounded-full object-cover border border-gray-200 shrink-0" />
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-gray-800 truncate max-w-[120px]">{{ $log->causer->name }}</p>
                                            <p class="text-[10px] text-gray-400 truncate max-w-[120px]">{{ $log->causer->email }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">System</span>
                                @endif
                            </td>

                            {{-- Context: IP + Browser/OS --}}
                            <td class="hidden lg:table-cell px-4 py-3.5">
                                @if($log->ip_address)
                                    <p class="text-xs font-mono text-gray-700">{{ $log->ip_address }}</p>
                                @endif
                                @if($ua['browser'])
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $ua['browser'] }} · {{ $ua['os'] }}</p>
                                @endif
                                @if(!$log->ip_address && !$ua['browser'])
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- When --}}
                            <td class="px-4 py-3.5">
                                @php $logLocalTime = local_time($log->created_at); @endphp
                                <p class="text-xs font-medium text-gray-700 whitespace-nowrap">{{ $logLocalTime->format('d M Y') }}</p>
                                <p class="text-[10px] text-gray-400">{{ $logLocalTime->format('H:i:s') }}</p>
                            </td>

                            {{-- Changes / Properties --}}
                            <td class="sticky right-0 bg-white group-hover:bg-gray-50/60 px-4 py-3.5">
                                @if($hasChanges || $hasProps)
                                    <button @click="expandedLog === {{ $log->id }} ? expandedLog = null : expandedLog = {{ $log->id }}"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors whitespace-nowrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        <span x-text="expandedLog === {{ $log->id }} ? 'Hide' : '{{ $hasChanges ? count($changed) . ' field' . (count($changed) !== 1 ? 's' : '') : 'Info' }}'"></span>
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Expanded diff / properties row --}}
                        @if($hasChanges || $hasProps)
                            <tr x-show="expandedLog === {{ $log->id }}" x-cloak>
                                <td colspan="8" class="px-4 py-3 bg-gray-50">
                                    <div class="rounded-lg border border-gray-200 bg-white overflow-hidden max-w-3xl">
                                        <table class="min-w-full text-xs">
                                            <thead>
                                                <tr class="bg-gray-50 border-b border-gray-100">
                                                    <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase tracking-wider">Field</th>
                                                    @if(!empty($old))     <th class="px-4 py-2 text-left font-semibold text-red-500 uppercase tracking-wider">Before</th> @endif
                                                    @if(!empty($changed)) <th class="px-4 py-2 text-left font-semibold text-green-600 uppercase tracking-wider">After</th> @endif
                                                    @if(!$hasChanges)     <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase tracking-wider">Value</th> @endif
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @if($hasChanges)
                                                    @foreach(array_keys(!empty($changed) ? $changed : $old) as $field)
                                                        <tr>
                                                            <td class="px-4 py-2 font-mono font-medium text-gray-600">{{ $field }}</td>
                                                            @if(!empty($old))     <td class="px-4 py-2 text-red-600 font-mono break-all">{{ is_array($old[$field] ?? null) ? json_encode($old[$field]) : ($old[$field] ?? '—') }}</td> @endif
                                                            @if(!empty($changed)) <td class="px-4 py-2 text-green-700 font-mono break-all">{{ is_array($changed[$field] ?? null) ? json_encode($changed[$field]) : ($changed[$field] ?? '—') }}</td> @endif
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    @foreach($properties as $key => $value)
                                                        <tr>
                                                            <td class="px-4 py-2 font-mono font-medium text-gray-600">{{ $key }}</td>
                                                            <td class="px-4 py-2 text-gray-700 font-mono break-all">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif

                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500">No activity logs found</p>
                                <p class="text-xs text-gray-400 mt-1">Try adjusting your filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($logs->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}
                </p>
                <div>{{ $logs->links() }}</div>
            </div>
        @endif
    </div>

    {{-- Log Retention (superadmin only) --}}
    @role('superadmin')
    <div class="mt-6 bg-white rounded-xl border border-red-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-red-100 bg-red-50/30 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
            <h2 class="text-sm font-semibold text-gray-700">Log Retention</h2>
            <span class="ml-1 text-xs text-gray-400">— superadmin only</span>
        </div>
        <div class="px-5 py-4">
            <p class="text-xs text-gray-500 mb-4">Permanently delete old log entries. This action <strong>cannot be undone</strong>.</p>
            <div class="flex flex-wrap gap-2">
                @foreach([30 => '30 days', 90 => '90 days', 180 => '6 months', 365 => '1 year'] as $days => $label)
                    @php $count = \App\Models\ActivityLog::where('created_at', '<', now()->subDays($days))->count(); @endphp
                    <button
                        wire:click="purgeOldLogs({{ $days }})"
                        wire:confirm="Delete {{ number_format($count) }} log entries older than {{ $label }}? This cannot be undone."
                        @disabled($count === 0)
                        class="inline-flex flex-col items-center gap-0.5 rounded-lg border px-4 py-2.5 text-xs font-medium transition
                            {{ $count > 0 ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100 cursor-pointer' : 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed' }}">
                        <span>Purge &gt; {{ $label }}</span>
                        <span class="font-normal opacity-70">{{ number_format($count) }} entries</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    @endrole

</div>
