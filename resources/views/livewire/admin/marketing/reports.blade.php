<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Reports</h1>
        <p class="text-sm text-gray-500 mt-1">Build a custom report, save it for later, or export the matching events to CSV.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Builder --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Report Filters</h2>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Event</label>
                        <select wire:model.live="eventName" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All events</option>
                            @foreach ($eventNames as $name)
                                <option value="{{ $name->value }}">{{ $name->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <input wire:model.live="dateFrom" type="text" placeholder="From" readonly
                                class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-36 cursor-pointer">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <input wire:model.live="dateTo" type="text" placeholder="To" readonly
                                class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-36 cursor-pointer">
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ number_format($totalMatching) }} events match these filters.</p>
                    <div class="flex items-center gap-2">
                        <button wire:click="$set('showSaveForm', true)" type="button"
                            class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            Save Report
                        </button>
                        <button wire:click="export" type="button"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                            Export CSV
                        </button>
                    </div>
                </div>

                @if ($showSaveForm)
                    <div class="mt-4 flex items-center gap-2 pt-4 border-t border-gray-100">
                        <input type="text" wire:model="name" placeholder="Report name…"
                            class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button wire:click="saveReport" type="button" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save</button>
                        <button wire:click="$set('showSaveForm', false)" type="button" class="px-3 py-1.5 text-xs text-gray-500">Cancel</button>
                    </div>
                @endif
            </div>

            {{-- Preview --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Preview (latest 10 matching)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/40">
                                <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Event</th>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Time</th>
                                <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($preview as $row)
                                <tr>
                                    <td class="px-5 py-2.5 text-sm text-gray-700">{{ $row->event_name }}</td>
                                    <td class="px-3 py-2.5 text-sm text-gray-500">{{ $row->occurred_at->format('d M, H:i') }}</td>
                                    <td class="px-5 py-2.5 text-right text-sm text-gray-700">{{ $row->value ? number_format($row->value, 2) : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-10 text-center text-sm text-gray-400">No matching events.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Saved reports + export history --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Saved Reports</h2>
                <div class="space-y-2">
                    @forelse ($savedReports as $saved)
                        <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg hover:bg-gray-50">
                            <button wire:click="loadFilters({{ $saved->id }})" type="button" class="text-sm text-gray-700 hover:text-indigo-600 text-left truncate">
                                {{ $saved->name }}
                            </button>
                            <button wire:click="deleteSavedReport({{ $saved->id }})" type="button" class="text-gray-300 hover:text-red-500 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No saved reports yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Export History</h2>
                <div class="space-y-2">
                    @forelse ($exports as $export)
                        <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg hover:bg-gray-50">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-700 truncate">{{ $export->name }}</p>
                                <p class="text-xs text-gray-400">{{ number_format($export->row_count) }} rows · {{ $export->created_at->diffForHumans() }}</p>
                            </div>
                            <button wire:click="downloadExport({{ $export->id }})" type="button" class="text-indigo-600 hover:text-indigo-700 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No exports yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
