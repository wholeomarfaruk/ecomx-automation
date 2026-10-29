<div>
    @if($canView)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-800">Issues</h2>
                @if($canManage)
                    <button type="button" x-on:click="$dispatch('open-report-issue', { subjectType: @js($subjectType), subjectId: {{ $subjectId }} })"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Report issue
                    </button>
                @endif
            </div>

            <div class="space-y-2">
                @forelse($issues as $issue)
                    <a href="{{ route('admin.issues.show', $issue->id) }}" wire:navigate wire:key="issue-panel-{{ $issue->id }}"
                        class="block rounded-lg border border-gray-200 px-3 py-2.5 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-sm text-gray-800 leading-snug">{{ $issue->title }}</span>
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $issue->status->badgeClass() }}">{{ $issue->status->label() }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5 text-xs text-gray-400">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $issue->type->badgeClass() }}">{{ $issue->type->label() }}</span>
                            <span>#{{ $issue->id }}</span>
                            @if($issue->claim_amount !== null)
                                <span>Claim {{ number_format((float) $issue->claim_amount, 2) }}</span>
                            @endif
                            @if((float) $issue->recovered_amount > 0)
                                <span class="text-emerald-600">Recovered {{ number_format((float) $issue->recovered_amount, 2) }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-xs text-gray-400">No issues. Something went wrong with this? Report it so it's followed up.</p>
                @endforelse
            </div>
        </div>

        @if($canManage)
            <livewire:admin.issues.report-issue-modal :key="'report-issue-' . $subjectType . '-' . $subjectId" />
        @endif
    @endif
</div>
