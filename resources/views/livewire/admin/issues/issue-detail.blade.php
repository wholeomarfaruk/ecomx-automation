<div x-data x-init="$store.pageName = { name: 'Issue #{{ $issue->id }}', slug: 'issues' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="min-w-0">
            <a href="{{ route('admin.issues.index') }}" wire:navigate class="text-xs text-indigo-600 hover:underline">← All issues</a>
            <h1 class="text-lg font-semibold text-gray-900 mt-1">{{ $issue->title }}</h1>
            <div class="flex flex-wrap items-center gap-1.5 mt-1.5 text-xs text-gray-400">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $issue->status->badgeClass() }}">{{ $issue->status->label() }}</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $issue->type->badgeClass() }}">{{ $issue->type->label() }}</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $issue->priority->badgeClass() }}">{{ $issue->priority->label() }}</span>
                <span>#{{ $issue->id }} · reported {{ local_time($issue->created_at)?->format('d M Y, h:i A') }} by {{ $issue->reporter?->name ?? 'System' }}</span>
            </div>
        </div>
        @if($canManage)
            <div class="flex items-center gap-2 shrink-0">
                @if($issue->status->isActive())
                    <button type="button" wire:click="openResolveModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        Resolve
                    </button>
                @else
                    <button type="button" wire:click="openResolveModal"
                        class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                        Edit resolution
                    </button>
                    <button type="button" wire:click="reopen" wire:confirm="Reopen this issue?"
                        class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                        Reopen
                    </button>
                @endif
            </div>
        @endif
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{-- Left --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">

            {{-- Summary --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-400">About</p>
                        @php $subjectUrl = \App\Models\Issue::urlFor($issue->subject); @endphp
                        @if($subjectUrl)
                            <a href="{{ $subjectUrl }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:underline">{{ \App\Models\Issue::describe($issue->subject) }}</a>
                        @else
                            <p class="text-sm font-medium text-gray-800">{{ \App\Models\Issue::describe($issue->subject) ?? '—' }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Against</p>
                        <p class="text-sm font-medium text-gray-800">{{ \App\Models\Issue::describe($issue->party) ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Claim</p>
                        <p class="text-sm font-medium text-gray-800">{{ $issue->claim_amount !== null ? number_format((float) $issue->claim_amount, 2) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Recovered</p>
                        <p class="text-sm font-medium {{ (float) $issue->recovered_amount > 0 ? 'text-emerald-600' : 'text-gray-800' }}">{{ number_format((float) $issue->recovered_amount, 2) }}</p>
                        @if($issue->recoveryEntry)
                            <p class="text-[11px] text-gray-400">Journal {{ $issue->recoveryEntry->entry_number }}</p>
                        @endif
                    </div>
                </div>

                @if(! empty($issue->meta['lines']))
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-2">Items</p>
                        <div class="space-y-1.5">
                            @foreach($issue->meta['lines'] as $line)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-700">{{ rtrim(rtrim(number_format((float) $line['quantity'], 3), '0'), '.') }} × {{ $line['product'] }}</span>
                                    <span class="text-gray-500">{{ number_format((float) $line['value'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400 mt-2">Value after the order discount — the suggested claim. Set the claim to what the courier actually agreed to pay.</p>
                    </div>
                @endif

                @if($issue->resolution)
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-1">Resolution</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $issue->resolution }}</p>
                        @if($issue->resolved_at)
                            <p class="text-[11px] text-gray-400 mt-1">{{ local_time($issue->resolved_at)?->format('d M Y, h:i A') }} · {{ $issue->resolver?->name ?? 'System' }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Timeline --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Timeline</h2>

                @if($canManage)
                    <form wire:submit.prevent="addNote" class="flex items-start gap-2 mb-5">
                        <input wire:model="note" type="text" placeholder="Add a note — called the courier, they asked for photos…"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">Add</button>
                    </form>
                    @error('note') <p class="text-xs text-red-500 -mt-4 mb-4">{{ $message }}</p> @enderror
                @endif

                <ol class="relative border-l border-gray-200 ml-2 space-y-4">
                    @foreach($timeline as $entry)
                        <li wire:key="issue-timeline-{{ $entry->id }}" class="ml-4" x-data="{ open: false }">
                            <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full border-2 border-white {{ match ($entry->event) { 'created' => 'bg-emerald-500', 'commented' => 'bg-sky-400', default => 'bg-indigo-400' } }}"></span>
                            <p class="text-sm {{ $entry->event === 'commented' ? 'text-gray-700 whitespace-pre-line' : 'text-gray-800' }}">{{ $entry->description }}</p>
                            <p class="text-xs text-gray-400">
                                {{ local_time($entry->created_at)?->format('d M Y, h:i A') }}
                                · {{ $entry->causer?->name ?? 'System' }}
                                @if($entry->properties->has('changes'))
                                    · <button type="button" @click="open = !open" class="text-indigo-500 hover:text-indigo-600" x-text="open ? 'Hide details' : 'Details'"></button>
                                @endif
                            </p>
                            @if($entry->properties->has('changes'))
                                <pre x-show="open" x-cloak class="mt-2 p-2 rounded-lg bg-gray-50 text-[11px] text-gray-600 whitespace-pre-wrap break-words">{{ json_encode($entry->properties->get('changes'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        {{-- Right --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Details</h2>
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="status" @disabled(! $canManage)
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($activeStatuses as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                            @unless($issue->status->isActive())
                                <option value="{{ $issue->status->value }}">{{ $issue->status->label() }}</option>
                            @endunless
                        </select>
                        @error('status') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Priority</label>
                        <select wire:model="priority" @disabled(! $canManage)
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach($priorities as $p)
                                <option value="{{ $p->value }}">{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Assigned To</label>
                        <select wire:model="assignedTo" @disabled(! $canManage)
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— Unassigned —</option>
                            @foreach($users as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Claim Amount</label>
                        <input wire:model="claimAmount" type="number" step="0.01" min="0" @disabled(! $canManage)
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('claimAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Details</label>
                        <textarea wire:model="description" rows="4" @disabled(! $canManage)
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                    @if($canManage)
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Save</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Resolve Modal --}}
    <div x-cloak x-data="{ open: @entangle('resolveModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-visible" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Resolve Issue</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Record what was recovered{{ $accountsEnabled ? ' — posted to accounts as Other Income' : '' }}.</p>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="resolve" class="px-6 py-5 space-y-4">
                <div class="inline-flex rounded-lg border border-gray-200 p-0.5 text-xs font-medium">
                    <button type="button" wire:click="$set('resolveStatus', 'resolved')"
                        class="px-3 py-1.5 rounded-md transition {{ $resolveStatus === 'resolved' ? 'bg-emerald-50 text-emerald-700' : 'text-gray-500 hover:text-gray-700' }}">Resolved</button>
                    <button type="button" wire:click="$set('resolveStatus', 'closed')"
                        class="px-3 py-1.5 rounded-md transition {{ $resolveStatus === 'closed' ? 'bg-gray-100 text-gray-700' : 'text-gray-500 hover:text-gray-700' }}">Closed (dropped)</button>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Recovered Amount</label>
                    <input wire:model="recoveredAmount" type="number" step="0.01" min="0" placeholder="0.00"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">What the courier / supplier actually paid or adjusted. 0 if nothing.</p>
                    @error('recoveredAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                @if($accountsEnabled)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Received Into</label>
                        <x-searchable-select field="recoveryAccountId" :value="$recoveryAccountId" wire:key="recovery-account-{{ $issue->id }}-{{ $recoveryAccounts->count() }}"
                            :options="$recoveryAccounts->mapWithKeys(fn ($a) => [(string) $a->id => $a->code . ' — ' . $a->name])"
                            placeholder="— Select account —" search-placeholder="Search accounts…" />
                        <p class="text-xs text-gray-400 mt-1">Courier adjusting it in its payout → that courier's account; paid in cash/bank → that account.</p>
                        @error('recoveryAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Resolution <span class="text-red-500">*</span></label>
                    <textarea wire:model="resolution" rows="3" placeholder="e.g. Pathao agreed 1,800 compensation, adjusted in the 2 Nov payout"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('resolution') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
