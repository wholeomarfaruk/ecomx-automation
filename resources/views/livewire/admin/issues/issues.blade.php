<div x-data x-init="$store.pageName = { name: 'Issues', slug: 'issues' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Open</p>
                <p class="text-xl font-semibold text-red-500 mt-0.5">{{ (int) $stats->open_count }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">In Progress</p>
                <p class="text-xl font-semibold text-amber-600 mt-0.5">{{ (int) $stats->in_progress_count }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Claims Outstanding</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ number_format((float) $stats->outstanding, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Recovered</p>
                <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ number_format((float) $stats->recovered, 2) }}</p>
            </div>
        </div>
        @if($canManage)
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" x-on:click="$dispatch('open-report-issue')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Report Issue
                </button>
            </div>
        @endif
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by title or #id…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="active">Open & In Progress</option>
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterType"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Types</option>
                @foreach($types as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Issue</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">About</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Against</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Claim</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Recovered</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($issues as $issue)
                        <tr wire:key="issue-row-{{ $issue->id }}" class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3 max-w-md">
                                <a href="{{ route('admin.issues.show', $issue->id) }}" wire:navigate class="text-sm font-medium text-gray-800 hover:text-indigo-600 hover:underline">
                                    {{ $issue->title }}
                                </a>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1 text-xs text-gray-400">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $issue->type->badgeClass() }}">{{ $issue->type->label() }}</span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $issue->priority->badgeClass() }}">{{ $issue->priority->label() }}</span>
                                    <span>#{{ $issue->id }} · {{ $issue->created_at->diffForHumans() }}</span>
                                    @if($issue->assignee)
                                        <span>· {{ $issue->assignee->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-sm">
                                @php $subjectUrl = \App\Models\Issue::urlFor($issue->subject); @endphp
                                @if($subjectUrl)
                                    <a href="{{ $subjectUrl }}" wire:navigate class="text-indigo-600 hover:underline">{{ \App\Models\Issue::describe($issue->subject) }}</a>
                                @else
                                    <span class="text-gray-600">{{ \App\Models\Issue::describe($issue->subject) ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ \App\Models\Issue::describe($issue->party) ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm text-right text-gray-700">{{ $issue->claim_amount !== null ? number_format((float) $issue->claim_amount, 2) : '—' }}</td>
                            <td class="px-5 py-3 text-sm text-right {{ (float) $issue->recovered_amount > 0 ? 'text-emerald-600' : 'text-gray-400' }}">{{ number_format((float) $issue->recovered_amount, 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $issue->status->badgeClass() }}">{{ $issue->status->label() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">No issues here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($issues->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $issues->links() }}</div>
        @endif
    </div>

    @if($canManage)
        <livewire:admin.issues.report-issue-modal />
    @endif
</div>
