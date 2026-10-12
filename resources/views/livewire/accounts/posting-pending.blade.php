{{-- Posting Pending — design: PostingPending.dc.html --}}
@php
    $s = $this->summary;
    $rows = $this->rows;
    $cur = $this->current;
    $select = 'text-[13px] pl-[10px] pr-[30px] py-[8px] border border-[#d1d5db] rounded-[9px] bg-white';
    $badge = [
        'ready'     => ['Ready to post', 'bg-[#d1fae5] text-[#065f46]'],
        'approval'  => ['Needs approval', 'bg-[#e0e7ff] text-[#3730a3]'],
        'issue'     => ['Has issues', 'bg-[#fef3c7] text-[#92400e]'],
        'scheduled' => ['Scheduled', 'bg-[#f3f4f6] text-[#374151]'],
        'rejected'  => ['Rejected', 'bg-[#fee2e2] text-[#991b1b]'],
    ];
    $noteBox = ['info' => 'bg-[#eef2ff] text-[#312e81] border-[#c7d2fe]', 'warn' => 'bg-[#fffbeb] text-[#78350f] border-[#fde68a]', 'bad' => 'bg-[#fef2f2] text-[#7f1d1d] border-[#fecaca]'];
    $mark = ['ok' => ['✓', 'bg-[#d1fae5] text-[#065f46]'], 'warn' => ['!', 'bg-[#e0e7ff] text-[#3730a3]'], 'bad' => ['×', 'bg-[#fee2e2] text-[#991b1b]']];
    $secTitle = 'mb-[6px] text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]';
    $amounts = $rows->mapWithKeys(fn ($r) => [$r['entry']->id => (float) $r['entry']->amount])->all();
@endphp

<div x-data="{ checked: [], amounts: @js($amounts), get total() { return this.checked.reduce((a, id) => a + (this.amounts[id] || 0), 0) } }"
     x-on:clear-selection.window="checked = []"
     class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="পোস্টিং বাকি · Posting Pending" subtitle="Draft entry balance, লাভ বা report-এ ধরা হয় না। Review করে post করুন।">
        @php $oc = app(\App\Services\Accounts\OrderPosting::class)->counts(); @endphp
        <a href="{{ route('accounts.posting.orders') }}" wire:navigate class="rounded-[8px] border px-[14px] py-[9px] text-[13px] {{ $oc['sales'] + $oc['returns'] ? 'border-[#fde68a] bg-[#fffbeb] font-semibold !text-[#92400e]' : 'border-[#e5e7eb] bg-white !text-[#374151]' }}">Orders to post · {{ $oc['sales'] + $oc['returns'] }}</a>
        <a href="#rules" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] !text-[#374151]">Posting rules</a>
        <button type="button" wire:click="$dispatch('voucher-form', { type: 'receipt' })" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">+ New voucher</button>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(190px,1fr))]">
        <x-accounts.stat label="Post করার অপেক্ষায়" :value="$s['open']" color="#b45309" :sub="$money($s['openAmount']) . ' · ' . $s['ready'] . ' ready now'" />
        <x-accounts.stat label="Approval লাগবে" :value="$s['approval']" color="#4338ca"
                         :sub="$money($s['approvalAmt']) . (\App\Support\AccountsSettings::approvalThreshold() ? ' · above ' . $money(\App\Support\AccountsSettings::approvalThreshold()) : '')" />
        <x-accounts.stat label="৪৮ ঘণ্টার বেশি পড়ে আছে" :value="$s['old']" :color="$s['old'] ? '#b91c1c' : null" :sub="$s['oldFirst']" />
        <x-accounts.stat label="ফেরত পাঠানো · Rejected" :value="$s['rejected']" sub="Waiting for maker to fix" />
        <x-accounts.stat label="আজ post হয়েছে" :value="$s['postedToday']" color="#047857" :sub="$money($s['postedAmt']) . ' · by ' . $s['posters'] . ' users'" />
    </div>

    @if ($lock)
        <div class="flex flex-wrap items-center gap-[10px] rounded-[12px] border border-[#e2e8f0] bg-[#f8fafc] px-[16px] py-[10px] text-[13px] text-[#334155]">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
            <span>Books locked up to <b>{{ $lock->format('d M Y') }}</b>. এর আগের তারিখের entry post করা যাবে না।</span>
        </div>
    @endif

    <div class="flex flex-wrap items-start gap-[16px]">

        {{-- Queue --}}
        <section class="min-w-0 flex-[999_1_600px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex flex-col gap-[12px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <div role="group" aria-label="Status" class="flex flex-wrap gap-[6px]">
                    @foreach ($this::TABS as $key => $label)
                        <x-accounts.chip :on="$tab === $key" :count="$s['counts'][$key]" wire:click="setTab('{{ $key }}')">{{ $label }}</x-accounts.chip>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center gap-[10px]">
                    <label for="pp-search" class="sr-only">Search vouchers</label>
                    <div class="flex flex-[1_1_220px] items-center gap-[8px] rounded-[9px] border border-[#d1d5db] bg-white px-[12px]">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                        <input id="pp-search" type="search" wire:model.live.debounce.300ms="q" placeholder="Ref, party বা amount" class="flex-1 border-0 bg-transparent px-0 py-[9px] text-[13.5px] outline-none focus:ring-0">
                    </div>
                    <label for="pp-type" class="text-[13px] text-[#4b5563]">Type</label>
                    <select id="pp-type" wire:model.live="type" class="{{ $select }}"><option value="">All types</option>@foreach ($this::TYPES as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select>
                    <label for="pp-source" class="text-[13px] text-[#4b5563]">Source</label>
                    <select id="pp-source" wire:model.live="source" class="{{ $select }}"><option value="">All</option><option value="manual">Manual</option><option value="system">System</option></select>
                    <label for="pp-by" class="text-[13px] text-[#4b5563]">Created by</label>
                    <select id="pp-by" wire:model.live="createdBy" class="{{ $select }}"><option value="">Anyone</option>@foreach ($this->makers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach<option value="system">System</option></select>
                </div>
            </div>

            <div x-show="checked.length" x-cloak class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#c7d2fe] bg-[#eef2ff] px-[18px] py-[10px] text-[13px] text-[#312e81]">
                <span><b x-text="checked.length + ' selected'"></b> · <span x-text="'৳' + Math.round(total).toLocaleString('en-IN')"></span></span>
                <div class="flex gap-[8px]">
                    <button type="button" @click="checked = []" class="rounded-[8px] border border-[#c7d2fe] bg-white px-[12px] py-[7px] text-[13px] text-[#3730a3]">Clear</button>
                    <button type="button" @click="$wire.startBulkReject(checked)" class="rounded-[8px] border border-[#fecaca] bg-white px-[12px] py-[7px] text-[13px] text-[#b91c1c]">Reject selected</button>
                    <button type="button" @click="$wire.postSelected(checked)" class="rounded-[8px] border-0 bg-[#4f46e5] px-[14px] py-[7px] text-[13px] font-semibold text-white">Post selected</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[820px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="w-[28px] py-[10px] pl-[18px] pr-0"><span class="sr-only">Select</span></th>
                            <th class="px-[8px] py-[10px] font-semibold">Voucher</th>
                            <th class="px-[8px] py-[10px] font-semibold">Party · details</th>
                            <th class="px-[8px] py-[10px] font-semibold">Source</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Amount</th>
                            <th class="px-[8px] py-[10px] font-semibold">Waiting</th>
                            <th class="px-[18px] py-[10px] font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            @php $e = $r['entry']; [$wait, $old] = $vouchers->waiting($e); $isSel = $cur && $cur['entry']->id === $e->id; @endphp
                            <tr wire:key="pp-{{ $e->id }}" wire:click="open({{ $e->id }})" @class(['cursor-pointer border-t border-[#f0f1f4]', 'bg-[#eef2ff] shadow-[inset_3px_0_0_#4f46e5]' => $isSel, 'hover:bg-[#f9fafb]' => ! $isSel])>
                                <td class="py-[11px] pl-[18px] pr-0" x-on:click.stop><input type="checkbox" value="{{ $e->id }}" x-model.number="checked" aria-label="Select voucher" class="rounded text-[#4f46e5]"></td>
                                <td class="whitespace-nowrap px-[8px] py-[11px]">
                                    <span class="text-[13px] font-bold text-[#4338ca]">{{ $e->ref() }}</span>
                                    <div class="text-[11.5px] text-[#6b7280]">{{ $vouchers->typeLabel($e) }} · {{ ($e->scheduled_for ?? $e->entry_date)->format('d M') }}</div>
                                </td>
                                <td class="px-[8px] py-[11px]"><div class="font-semibold">{{ $vouchers->partyOf($e) }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $e->description }}</div></td>
                                <td class="px-[8px] py-[11px]">
                                    <span @class(['rounded-[6px] px-[8px] py-[2px] text-[11.5px] font-semibold', 'bg-[#f3f4f6] text-[#374151]' => $e->source === 'manual', 'bg-[#e0f2fe] text-[#075985]' => $e->source !== 'manual'])>{{ ucfirst($e->source) }}</span>
                                    <div class="mt-[3px] text-[11.5px] text-[#6b7280]">by {{ $e->createdBy?->name ?? 'System' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold">{{ $money($e->amount) }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px]"><span @class(['font-bold text-[#b91c1c]' => $old, 'text-[#6b7280]' => ! $old])>{{ $wait }}</span></td>
                                <td class="px-[18px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $badge[$r['state']][1] }}">{{ $badge[$r['state']][0] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($rows->isEmpty())
                <div class="px-[18px] py-[36px] text-center text-[#6b7280]">এই তালিকায় কোনো voucher নেই।</div>
            @endif
        </section>

        {{-- Review panel --}}
        <aside class="flex min-w-0 flex-[1_1_400px] flex-col overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            @if ($cur)
                @php $e = $cur['entry']; $st = $cur['state']; @endphp
                <div class="flex items-start justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[16px]">
                    <div>
                        <div class="flex flex-wrap items-center gap-[8px]">
                            <h2 class="m-0 text-[18px] font-bold">{{ $e->ref() }}</h2>
                            <span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $badge[$st][1] }}">{{ $badge[$st][0] }}</span>
                        </div>
                        <div class="mt-[3px] text-[12.5px] text-[#6b7280]">{{ $cur['type'] }} · {{ ($e->scheduled_for ?? $e->entry_date)->format('d M') }} · {{ ucfirst($e->source) }}</div>
                    </div>
                    <div class="text-right"><div class="text-[11.5px] text-[#6b7280]">Amount</div><div class="text-[20px] font-bold">{{ $money($e->amount) }}</div></div>
                </div>

                <div class="flex flex-col gap-[16px] px-[18px] py-[14px]">
                    <div class="grid grid-cols-[96px_1fr] gap-x-[10px] gap-y-[6px] text-[13px]">
                        <span class="text-[#6b7280]">Party</span><span class="font-semibold">{{ $cur['party'] }}</span>
                        <span class="text-[#6b7280]">Details</span><span>{{ $e->description ?: '—' }}</span>
                        <span class="text-[#6b7280]">Created</span><span>{{ $e->createdBy?->name ?? 'System' }} · {{ $e->created_at->format('d M, g:i A') }}</span>
                    </div>

                    @if ($cur['note'])
                        <div class="rounded-[10px] border px-[12px] py-[10px] text-[13px] leading-[1.5] {{ $noteBox[$cur['kind']] }}">{{ $cur['note'] }}</div>
                    @endif

                    <div>
                        <div class="{{ $secTitle }}">Journal lines</div>
                        <table class="overflow-hidden rounded-[10px] border border-[#eef0f3] text-[12.5px]">
                            <thead><tr class="bg-[#f9fafb] text-left text-[#6b7280]"><th class="px-[10px] py-[7px] font-semibold">Account</th><th class="px-[10px] py-[7px] text-right font-semibold">Debit</th><th class="px-[10px] py-[7px] text-right font-semibold">Credit</th></tr></thead>
                            <tbody>
                                @foreach ($cur['lines'] as $l)
                                    @php $party = $l->subledger_type ? $vouchers->partyName($l->subledger_type, $l->subledger_id) : null; @endphp
                                    <tr class="border-t border-[#f0f1f4]"><td class="px-[10px] py-[7px]">{{ $l->account?->name }}{{ $party ? ' — ' . $party : '' }}</td>
                                        <td class="px-[10px] py-[7px] text-right">{{ (float) $l->debit > 0 ? $money($l->debit) : '' }}</td><td class="px-[10px] py-[7px] text-right">{{ (float) $l->credit > 0 ? $money($l->credit) : '' }}</td></tr>
                                @endforeach
                                @php $dr = (float) $cur['lines']->sum('debit'); $cr = (float) $cur['lines']->sum('credit'); $bal = abs($dr - $cr) < 0.01; @endphp
                                <tr class="border-t border-[#e5e7eb] bg-[#f9fafb] font-bold"><td class="px-[10px] py-[7px]">Total <span class="font-semibold {{ $bal ? 'text-[#047857]' : 'text-[#b91c1c]' }}">· {{ $bal ? 'balanced' : 'not balanced' }}</span></td>
                                    <td class="px-[10px] py-[7px] text-right">{{ $money($dr) }}</td><td class="px-[10px] py-[7px] text-right">{{ $money($cr) }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($cur['impact'])
                        <div>
                            <div class="{{ $secTitle }}">Post করলে যা বদলাবে</div>
                            <div class="flex flex-col gap-[6px]">
                                @foreach ($cur['impact'] as $i)
                                    <div class="flex justify-between gap-[10px] rounded-[8px] bg-[#f9fafb] px-[10px] py-[8px] text-[13px]">
                                        <span class="text-[#374151]">{{ $i['label'] }}</span>
                                        <span class="whitespace-nowrap"><span class="text-[#6b7280]">{{ $i['from'] }}</span> → <b>{{ $i['to'] }}</b></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <div class="{{ $secTitle }}">Checks</div>
                        <div class="flex flex-col gap-[6px]">
                            @foreach ($cur['checks'] as $c)
                                <div class="flex items-start gap-[8px] text-[13px]">
                                    <span class="flex h-[18px] w-[18px] flex-none items-center justify-center rounded-full text-[11px] font-bold {{ $mark[$c['state']][1] }}">{{ $mark[$c['state']][0] }}</span>
                                    <span @class(['font-semibold text-[#991b1b]' => $c['state'] === 'bad', 'text-[#374151]' => $c['state'] !== 'bad'])>{{ $c['text'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if ($e->attachments->isNotEmpty())
                            <div class="mt-[8px] flex flex-wrap gap-[6px]">
                                @foreach ($e->attachments as $a)
                                    <a href="{{ route('accounts.vouchers.attachment', $a) }}" target="_blank" class="rounded-[6px] bg-[#f3f4f6] px-[8px] py-[2px] text-[12px] !text-[#374151]">{{ $a->name }}</a>
                                @endforeach
                            </div>
                        @endif
                        @if ($st !== 'rejected')
                            <div x-data class="mt-[8px]">
                                <input type="file" x-ref="att" wire:model="attachment" class="hidden" accept=".pdf,image/*,.xlsx,.csv">
                                <button type="button" @click="$refs.att.click()" class="text-[12.5px] font-semibold text-[#4338ca]">+ Attach bill / slip</button>
                                <span wire:loading wire:target="attachment" class="text-[12px] text-[#6b7280]">Uploading…</span>
                                @error('attachment') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="{{ $secTitle }}">History</div>
                        <div class="flex flex-col gap-[8px]">
                            @foreach ($cur['trail'] as $h)
                                <div class="flex items-start gap-[10px]">
                                    <span class="mt-[6px] h-[7px] w-[7px] flex-none rounded-full bg-[#a5b4fc]"></span>
                                    <div><div class="text-[13px]">{{ ucfirst($h->event) }} by {{ $h->user?->name ?? 'System' }}{{ $h->note ? ' — ' . $h->note : '' }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $h->created_at->format('d M, g:i A') }}</div></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($rejecting && ! in_array($st, ['scheduled', 'rejected'], true))
                        <div class="flex flex-col gap-[8px] rounded-[10px] border border-[#fecaca] bg-[#fef2f2] p-[12px]">
                            <label for="rej-reason" class="text-[13px] font-semibold text-[#991b1b]">কেন ফেরত পাঠাচ্ছেন? (required)</label>
                            <textarea id="rej-reason" rows="2" wire:model="rejectReason" placeholder="যেমন: bank slip-এর সাথে amount মিলছে না" class="resize-y rounded-[8px] border border-[#fca5a5] bg-white px-[10px] py-[9px] text-[13px]"></textarea>
                            @error('rejectReason') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                            <div class="flex justify-end gap-[8px]">
                                <button type="button" wire:click="$set('rejecting', false)" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[8px] text-[13px] text-[#374151]">Cancel</button>
                                <button type="button" wire:click="reject({{ $e->id }})" class="rounded-[8px] border-0 bg-[#b91c1c] px-[14px] py-[8px] text-[13px] font-semibold text-white">Send back to maker</button>
                            </div>
                        </div>
                    @endif

                    @if ($st === 'scheduled' && $newDate !== null)
                        <div class="flex flex-wrap items-center gap-[8px] rounded-[10px] border border-[#e5e7eb] p-[10px]">
                            <input type="text" readonly placeholder="YYYY-MM-DD" wire:model="newDate" class="flatpickr-only-date cursor-pointer bg-white rounded-[8px] border border-[#d1d5db] px-[10px] py-[7px] text-[13px]">
                            <button type="button" wire:click="reschedule({{ $e->id }})" class="rounded-[8px] bg-[#4f46e5] px-[12px] py-[7px] text-[13px] font-semibold text-white">Save date</button>
                            @error('newDate') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>

                @php $primaryOn = 'rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white'; $primaryOff = 'cursor-not-allowed rounded-[8px] border-0 bg-[#c7d2fe] px-[16px] py-[9px] text-[13px] font-semibold text-white'; $secondary = 'rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]'; @endphp
                <div class="mt-auto flex flex-wrap justify-end gap-[8px] border-t border-[#f0f1f4] bg-[#fcfcfd] px-[18px] py-[14px]">
                    @if (in_array($st, ['ready', 'approval', 'issue'], true))
                        <button type="button" wire:click="$set('rejecting', true)" class="rounded-[8px] border border-[#fecaca] bg-white px-[14px] py-[9px] text-[13px] text-[#b91c1c]">Reject</button>
                        <button type="button" wire:click="$dispatch('voucher-form', { id: {{ $e->id }} })" class="{{ $secondary }}">Edit</button>
                    @endif
                    @if ($st === 'ready')
                        <button type="button" wire:click="post({{ $e->id }})" wire:confirm="Post {{ $e->ref() }}? Posted entries can only be reversed." @disabled($cur['makerBlocked']) class="{{ $cur['makerBlocked'] ? $primaryOff : $primaryOn }}" title="{{ $cur['makerBlocked'] ? 'নিজের entry নিজে post করা যাবে না' : '' }}">Post</button>
                    @elseif ($st === 'approval')
                        <button type="button" wire:click="post({{ $e->id }})" wire:confirm="Approve & post {{ $e->ref() }}?" @disabled(! $cur['canApprove']) class="{{ $cur['canApprove'] ? $primaryOn : $primaryOff }}">{{ $cur['canApprove'] ? 'Approve & post' : 'Waiting for Owner' }}</button>
                    @elseif ($st === 'issue')
                        <button type="button" disabled class="{{ $primaryOff }}">Fix issues to post</button>
                    @elseif ($st === 'scheduled')
                        <button type="button" wire:click="$set('newDate', '{{ ($e->scheduled_for ?? $e->entry_date)->toDateString() }}')" class="{{ $secondary }}">Change date</button>
                        <button type="button" wire:click="postNow({{ $e->id }})" wire:confirm="Post {{ $e->ref() }} now?" class="{{ $primaryOn }}">Post now</button>
                    @elseif ($st === 'rejected')
                        <button type="button" wire:click="deleteVoucher({{ $e->id }})" wire:confirm="Delete {{ $e->ref() }}?" class="{{ $secondary }}">Delete</button>
                        <button type="button" wire:click="$dispatch('voucher-form', { id: {{ $e->id }} })" class="{{ $primaryOn }}">Edit &amp; resubmit</button>
                    @endif
                </div>
            @else
                <div class="px-[18px] py-[36px] text-center text-[#6b7280]">কোনো voucher বাছাই করা নেই।</div>
            @endif
        </aside>
    </div>

    {{-- Posted today + rules --}}
    <div class="flex flex-wrap items-start gap-[16px]">
        <section class="min-w-0 flex-[999_1_600px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex items-center justify-between border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <h2 class="m-0 text-[15px] font-bold">আজ post হয়েছে · Posted today</h2>
                <a href="{{ route('accounts.transactions', ['period' => 'today', 'status' => 'posted']) }}" wire:navigate class="text-[13px]">সব দেখুন</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[600px] text-[13px]">
                    <tbody>
                        @forelse ($this->postedToday as $p)
                            <tr class="border-t border-[#f0f1f4] first:border-t-0">
                                <td class="whitespace-nowrap px-[18px] py-[10px] text-[#6b7280]">{{ $p['time'] }}</td>
                                <td @class(['whitespace-nowrap px-[8px] py-[10px] font-semibold', 'text-[#6b7280] line-through' => $p['reversed']])>{{ $p['ref'] }}</td>
                                <td class="px-[8px] py-[10px]">{{ $p['text'] }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[10px] text-[#6b7280]">by {{ $p['by'] }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[10px] text-right font-bold">{{ $p['amount'] }}</td>
                                <td class="px-[18px] py-[10px] text-right">
                                    @if ($p['reversed'])<span class="text-[12.5px] text-[#6b7280]">Reversed</span>
                                    @else<a href="#" wire:click.prevent="$set('reversing', {{ $p['id'] }})" class="text-[12.5px] !text-[#b91c1c]">Reverse</a>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-[18px] py-[18px] text-center text-[#6b7280]">আজ এখনো কিছু post হয়নি।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-[18px] pb-[14px] pt-[10px] text-[12px] text-[#6b7280]">Post হওয়া entry edit করা যায় না। ভুল হলে Reverse করুন — একটা উল্টো journal তৈরি হবে।</div>
        </section>

        <section id="rules" class="flex min-w-0 flex-[1_1_400px] flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
            <h2 class="m-0 text-[15px] font-bold">Posting rules</h2>
            @foreach ($this::RULE_TEXT as $key => [$title, $desc])
                @php
                    $rule = \App\Support\AccountsSettings::rule($key);
                    $title = sprintf($title, $money($rule['amount']));
                    $desc = sprintf($desc, $lock?->format('d M Y') ?? '—');
                @endphp
                <label class="flex cursor-pointer items-start gap-[10px] text-[13px]">
                    <input type="checkbox" @checked($rule['on']) wire:click="toggleRule('{{ $key }}')" @disabled(! \App\Support\AccountsAccess::can('settings')) class="mt-[2px] h-[16px] w-[16px] rounded text-[#4f46e5]">
                    <span><span class="block font-semibold">{{ $title }}</span><span class="mt-[1px] block text-[12px] text-[#6b7280]">{{ $desc }}</span></span>
                </label>
            @endforeach
        </section>
    </div>

    {{-- Bulk reject --}}
    @if ($bulkRejecting)
        <x-accounts.modal title="Reject selected" close="$set('bulkRejecting', false)">
            <label for="bulk-reason" class="text-[13px] font-semibold text-[#991b1b]">কেন ফেরত পাঠাচ্ছেন? (required) · {{ count($bulkIds) }} vouchers</label>
            <textarea id="bulk-reason" rows="3" wire:model="rejectReason" class="resize-y rounded-[9px] border border-[#fca5a5] px-[12px] py-[10px] text-[13px]"></textarea>
            @error('rejectReason') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
            <x-slot:footer>
                <button type="button" wire:click="$set('bulkRejecting', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="reject" class="rounded-[9px] border-0 bg-[#b91c1c] px-[18px] py-[10px] text-[14px] font-bold text-white">Send back to maker</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    {{-- Reverse --}}
    @if ($reversing)
        <x-accounts.modal title="Reverse voucher" close="$set('reversing', null)">
            <div class="text-[13.5px] text-[#374151]">একটা উল্টো journal post হবে; মূল voucher যেমন আছে তেমন থাকবে।</div>
            <input type="text" wire:model="reverseReason" placeholder="কারণ (optional) — যেমন: wrong customer" class="rounded-[9px] border border-[#d1d5db] px-[12px] py-[9px] text-[14px]">
            <x-slot:footer>
                <button type="button" wire:click="$set('reversing', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="reverse" class="rounded-[9px] border-0 bg-[#b91c1c] px-[18px] py-[10px] text-[14px] font-bold text-white">Reverse</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @livewire('accounts.voucher-form')
</div>
