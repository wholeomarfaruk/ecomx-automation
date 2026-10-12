{{-- Transactions (day book) — design: Transactions.dc.html --}}
@php
    $s = $this->summary;
    $page = $this->entries;
    $days = $page->getCollection()->groupBy(fn ($e) => $e->entry_date->toDateString());
    $totals = $this->dayTotals;
    $cur = $this->current;
    $select = 'text-[13px] pl-[10px] pr-[30px] py-[8px] border border-[#d1d5db] rounded-[9px] bg-white';
    $badge = ['Posted' => 'bg-[#d1fae5] text-[#065f46]', 'Draft' => 'bg-[#fef3c7] text-[#92400e]', 'Reversed' => 'bg-[#f3f4f6] text-[#4b5563]', 'Rejected' => 'bg-[#fee2e2] text-[#991b1b]'];
    $secTitle = 'mb-[6px] text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]';
    $pill = fn ($on) => $on ? 'rounded-[7px] border-0 bg-[#4f46e5] px-[12px] py-[7px] text-[13px] font-semibold text-white' : 'rounded-[7px] border-0 bg-transparent px-[12px] py-[7px] text-[13px] text-[#374151]';
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="লেনদেন · Transactions" subtitle="সব টাকা আসা-যাওয়া, দিন অনুযায়ী (Day book)">
        <button type="button" wire:click="$dispatch('voucher-form', { type: 'receipt' })" class="rounded-[8px] border border-[#a7f3d0] bg-[#ecfdf5] px-[12px] py-[9px] text-[13px] font-semibold text-[#065f46]">+ টাকা আসলো</button>
        <button type="button" wire:click="$dispatch('voucher-form', { type: 'payment' })" class="rounded-[8px] border border-[#fecaca] bg-[#fef2f2] px-[12px] py-[9px] text-[13px] font-semibold text-[#991b1b]">+ টাকা গেলো</button>
        <button type="button" wire:click="$dispatch('voucher-form', { type: 'expense' })" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[9px] text-[13px] text-[#374151]">+ Expense</button>
        <button type="button" wire:click="$dispatch('voucher-form', { type: 'contra' })" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[9px] text-[13px] text-[#374151]">+ Transfer</button>
        <button type="button" wire:click="$dispatch('voucher-form', { type: 'journal' })" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[9px] text-[13px] text-[#374151]">+ Journal</button>
    </x-accounts.page-head>

    {{-- Period --}}
    <div class="acc-no-print flex flex-wrap items-center justify-between gap-[12px]">
        <div role="group" aria-label="Period" class="flex flex-wrap gap-[4px] rounded-[10px] border border-[#e5e7eb] bg-white p-[4px]">
            @foreach ($this::PERIODS as $key => $label)
                <button type="button" wire:click="setPeriod('{{ $key }}')" class="{{ $pill($period === $key) }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="flex flex-wrap items-center gap-[6px] text-[12.5px] text-[#4b5563]">
            @if ($period === 'custom')
                <input type="text" readonly placeholder="YYYY-MM-DD" wire:model.live="from" class="flatpickr-only-date cursor-pointer bg-white rounded-[8px] border border-[#d1d5db] px-[8px] py-[5px] text-[12.5px]">
                <span>–</span>
                <input type="text" readonly placeholder="YYYY-MM-DD" wire:model.live="to" class="flatpickr-only-date cursor-pointer bg-white rounded-[8px] border border-[#d1d5db] px-[8px] py-[5px] text-[12.5px]">
            @else
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path><path d="M8 3v4"></path><path d="M16 3v4"></path></svg>
                <span>{{ $rangeText }}</span>
            @endif
            <span class="text-[#d1d5db]">·</span>
            <button type="button" wire:click="export" class="border-0 bg-transparent p-0 text-[12.5px] text-[#4338ca]">Export</button>
            <span class="text-[#d1d5db]">·</span>
            <button type="button" onclick="window.print()" class="border-0 bg-transparent p-0 text-[12.5px] text-[#4338ca]">Print day book</button>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="টাকা আসলো · Money in" :value="$money($s['in'])" color="#047857" :sub="'Posted · ' . $money($s['inDraft']) . ' in draft'" />
        <x-accounts.stat label="টাকা গেলো · Money out" :value="$money($s['out'])" color="#b91c1c" :sub="'Posted · ' . $money($s['outDraft']) . ' in draft'" />
        <x-accounts.stat label="নিট · Net" :value="($s['net'] < 0 ? '− ' : '+ ') . $money(abs($s['net']))" :color="$s['net'] < 0 ? '#b91c1c' : '#047857'" sub="In − out, posted only" />
        <x-accounts.stat label="Transfer · নিজের account-এ" :value="$money($s['transfer'])" sub="Cash ↔ bank ↔ bKash" />
        <x-accounts.stat label="মোট entry" :value="$s['count']" :sub="$s['posted'] . ' posted · ' . $s['draft'] . ' draft · ' . $s['other'] . ' other'" />
    </div>

    <div class="flex flex-wrap items-start gap-[16px]">

        {{-- List --}}
        <section class="min-w-0 flex-[999_1_640px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="acc-no-print flex flex-col gap-[12px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <div role="group" aria-label="Type" class="flex flex-wrap gap-[6px]">
                    @foreach ($this::TYPES as $key => $label)
                        <x-accounts.chip :on="$type === $key" wire:click="setType('{{ $key }}')">{{ $label }}</x-accounts.chip>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center gap-[10px]">
                    <label for="tx-search" class="sr-only">Search transactions</label>
                    <div class="flex flex-[1_1_220px] items-center gap-[8px] rounded-[9px] border border-[#d1d5db] bg-white px-[12px]">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                        <input id="tx-search" type="search" wire:model.live.debounce.400ms="q" placeholder="Ref, party, note বা amount" class="flex-1 border-0 bg-transparent px-0 py-[9px] text-[13.5px] outline-none focus:ring-0">
                    </div>
                    <label for="tx-acc" class="text-[13px] text-[#4b5563]">Account</label>
                    <select id="tx-acc" wire:model.live="account" class="{{ $select }}"><option value="">All accounts</option>@foreach ($accounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select>
                    <label for="tx-status" class="text-[13px] text-[#4b5563]">Status</label>
                    <select id="tx-status" wire:model.live="status" class="{{ $select }}"><option value="">All</option><option value="posted">Posted</option><option value="draft">Draft</option><option value="reversed">Reversed</option><option value="rejected">Rejected</option></select>
                    <label class="flex items-center gap-[6px] text-[13px] text-[#374151]"><input type="checkbox" wire:model.live="drafts" class="rounded text-[#4f46e5]"> Draft দেখাও</label>
                </div>
            </div>

            <div class="overflow-x-auto" wire:loading.class="opacity-60">
                <table class="min-w-[860px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Time</th>
                            <th class="px-[8px] py-[10px] font-semibold">Voucher</th>
                            <th class="px-[8px] py-[10px] font-semibold">Party · note</th>
                            <th class="px-[8px] py-[10px] font-semibold">Account</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">In</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Out</th>
                            <th class="px-[18px] py-[10px] font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($days as $date => $list)
                            @php $d = \Carbon\CarbonImmutable::parse($date); $t = $totals[$date] ?? ['in' => 0, 'out' => 0]; @endphp
                            <tr class="border-t border-[#e5e7eb] bg-[#f8fafc]">
                                <td colspan="4" class="px-[18px] py-[9px] text-[12.5px] font-bold text-[#334155]">{{ \App\Support\AccountsPeriod::bnWeekday($d) }} · {{ $d->format('d M Y') }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[9px] text-right text-[12.5px] font-bold text-[#047857]">{{ $money($t['in']) }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[9px] text-right text-[12.5px] font-bold text-[#b91c1c]">{{ $money($t['out']) }}</td>
                                <td class="px-[18px] py-[9px] text-[11.5px] text-[#6b7280]">posted total</td>
                            </tr>
                            @foreach ($list as $e)
                                @php
                                    $p = $this->present($e);
                                    $dead = in_array($p['status'], ['Reversed', 'Rejected'], true);
                                    $draft = $p['status'] === 'Draft';
                                    $amt = $money($p['amount']);
                                    $inCls = $dead ? 'text-[#9ca3af] line-through' : ($p['dir'] === 'transfer' ? 'text-[#6b7280]' : ($draft ? 'font-semibold text-[#047857] opacity-60' : 'font-bold text-[#047857]'));
                                    $outCls = $dead ? 'text-[#9ca3af] line-through' : ($p['dir'] === 'transfer' ? 'text-[#6b7280]' : ($draft ? 'font-semibold text-[#b91c1c] opacity-60' : 'font-bold text-[#b91c1c]'));
                                    $isSel = $cur && $cur->id === $e->id;
                                @endphp
                                <tr wire:key="tx-{{ $e->id }}" @class(['border-t border-[#f0f1f4]', 'bg-[#eef2ff] shadow-[inset_3px_0_0_#4f46e5]' => $isSel])>
                                    <td class="whitespace-nowrap py-[11px] pl-[18px] pr-[8px] text-[#6b7280]">{{ $e->created_at->format('g:i A') }}</td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px]">
                                        <button type="button" wire:click="open({{ $e->id }})" @class(['border-0 bg-transparent p-0 text-left text-[13px] font-bold', 'text-[#6b7280] line-through' => $dead, 'text-[#4338ca]' => ! $dead])>{{ $e->ref() }}</button>
                                        <div class="text-[11.5px] text-[#6b7280]">{{ $p['type'] }}</div>
                                    </td>
                                    <td class="px-[8px] py-[11px]"><div class="font-semibold">{{ $p['party'] }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $e->description }}</div></td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-[#374151]">{{ $p['account'] }}</td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-right"><span class="{{ $inCls }}">{{ in_array($p['dir'], ['in', 'transfer'], true) ? $amt : '' }}</span></td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-right"><span class="{{ $outCls }}">{{ in_array($p['dir'], ['out', 'transfer'], true) ? $amt : '' }}</span></td>
                                    <td class="px-[18px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] px-[8px] py-[2px] text-[11.5px] font-semibold {{ $badge[$p['status']] }}">{{ $p['status'] }}</span></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($page->isEmpty())
                <div class="px-[18px] py-[36px] text-center text-[#6b7280]">এই filter-এ কোনো লেনদেন নেই।</div>
            @endif
            <div class="acc-no-print flex flex-wrap items-center justify-between gap-[10px] border-t border-[#f0f1f4] px-[18px] py-[12px] text-[13px] text-[#6b7280]">
                <span>Showing {{ $page->total() ? $page->firstItem() . '–' . $page->lastItem() . ' of ' . number_format($page->total()) : '0' }} entries</span>
                <div class="flex gap-[6px]">
                    <button type="button" wire:click="previousPage" @disabled($page->onFirstPage()) class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] {{ $page->onFirstPage() ? 'text-[#9ca3af]' : 'text-[#374151]' }}">Previous</button>
                    <button type="button" wire:click="nextPage" @disabled(! $page->hasMorePages()) class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] {{ $page->hasMorePages() ? 'text-[#374151]' : 'text-[#9ca3af]' }}">Next</button>
                </div>
            </div>
        </section>

        {{-- Detail --}}
        <aside class="acc-no-print flex min-w-0 flex-[1_1_380px] flex-col overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            @if ($cur)
                @php
                    $p = $this->present($cur);
                    $dirLabel = ['in' => 'টাকা আসলো', 'out' => 'টাকা গেলো', 'transfer' => 'Transfer', 'none' => 'Journal'][$p['dir']];
                    $amtColor = ['in' => 'text-[#047857]', 'out' => 'text-[#b91c1c]'][$p['dir']] ?? 'text-[#111827]';
                    $flag = $cur->status === \App\Enums\Accounts\JournalEntryStatus::REJECTED
                        ? ['Rejected by ' . ($cur->rejectedBy?->name ?? 'reviewer') . ': ' . $cur->reject_reason, 'bg-[#fef2f2] text-[#7f1d1d] border-[#fecaca]']
                        : ($cur->reversingEntry->isNotEmpty()
                            ? ['Reversed by ' . $cur->reversingEntry->first()->ref() . '.', 'bg-[#f3f4f6] text-[#374151] border-[#e5e7eb]']
                            : ($cur->reversedEntry ? ['Reversal of ' . $cur->reversedEntry->ref() . '.', 'bg-[#f3f4f6] text-[#374151] border-[#e5e7eb]'] : null));
                @endphp
                <div class="flex items-start justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[16px]">
                    <div>
                        <div class="flex flex-wrap items-center gap-[8px]">
                            <h2 class="m-0 text-[18px] font-bold">{{ $cur->ref() }}</h2>
                            <span class="whitespace-nowrap rounded-[6px] px-[8px] py-[2px] text-[11.5px] font-semibold {{ $badge[$p['status']] }}">{{ $p['status'] }}</span>
                        </div>
                        <div class="mt-[3px] text-[12.5px] text-[#6b7280]">{{ $p['type'] }} · {{ $cur->entry_date->format('d M Y') }}, {{ $cur->created_at->format('g:i A') }}</div>
                    </div>
                    <div class="text-right"><div class="text-[11.5px] text-[#6b7280]">{{ $dirLabel }}</div><div class="text-[20px] font-bold {{ $amtColor }}">{{ $money($p['amount']) }}</div></div>
                </div>
                <div class="flex flex-col gap-[16px] px-[18px] py-[14px]">
                    <div class="grid grid-cols-[96px_1fr] gap-x-[10px] gap-y-[6px] text-[13px]">
                        <span class="text-[#6b7280]">Party</span><span class="font-semibold">{{ $p['party'] }}</span>
                        <span class="text-[#6b7280]">Account</span><span>{{ $p['account'] }}</span>
                        <span class="text-[#6b7280]">Note</span><span>{{ $cur->description ?: '—' }}</span>
                        <span class="text-[#6b7280]">Created</span><span>{{ $cur->createdBy?->name ?? 'System' }} · {{ $cur->created_at->format('g:i A') }}</span>
                        <span class="text-[#6b7280]">Posted</span><span>{{ $cur->posted_at ? ($cur->postedBy?->name ?? 'System') . ' · ' . $cur->posted_at->format('g:i A') : '—' }}</span>
                    </div>
                    @if ($flag)
                        <div class="rounded-[10px] border px-[12px] py-[10px] text-[13px] {{ $flag[1] }}">{{ $flag[0] }}</div>
                    @endif
                    <div>
                        <div class="{{ $secTitle }}">Journal lines</div>
                        <table class="border border-[#eef0f3] text-[12.5px]">
                            <thead><tr class="bg-[#f9fafb] text-left text-[#6b7280]"><th class="px-[10px] py-[7px] font-semibold">Account</th><th class="px-[10px] py-[7px] text-right font-semibold">Debit</th><th class="px-[10px] py-[7px] text-right font-semibold">Credit</th></tr></thead>
                            <tbody>
                                @foreach ($cur->lines as $l)
                                    @php $party = $l->subledger_type ? app(\App\Services\Accounts\Vouchers::class)->partyName($l->subledger_type, $l->subledger_id) : null; @endphp
                                    <tr class="border-t border-[#f0f1f4]"><td class="px-[10px] py-[7px]">{{ $l->account?->name }}{{ $party ? ' — ' . $party : '' }}</td><td class="px-[10px] py-[7px] text-right">{{ (float) $l->debit > 0 ? $money($l->debit) : '' }}</td><td class="px-[10px] py-[7px] text-right">{{ (float) $l->credit > 0 ? $money($l->credit) : '' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <div class="{{ $secTitle }}">Attachment</div>
                        @forelse ($cur->attachments as $a)
                            <div class="mb-[6px] flex items-center justify-between rounded-[10px] border border-[#eef0f3] px-[12px] py-[9px] text-[13px]">
                                <span class="flex items-center gap-[8px]"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"></path><path d="M14 3v5h5"></path></svg>{{ $a->name }}</span>
                                <a href="{{ route('accounts.vouchers.attachment', $a) }}" target="_blank" class="text-[12.5px]">View</a>
                            </div>
                        @empty
                            <div class="rounded-[10px] border border-[#eef0f3] px-[12px] py-[9px] text-[13px] text-[#6b7280]">—</div>
                        @endforelse
                    </div>
                </div>
                <div class="mt-auto flex flex-wrap justify-end gap-[8px] border-t border-[#f0f1f4] bg-[#fcfcfd] px-[18px] py-[14px]">
                    <a href="{{ route('accounts.vouchers.print', $cur) }}" target="_blank" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] !text-[#374151]">Print</a>
                    <button type="button" wire:click="$dispatch('voucher-form', { id: {{ $cur->id }}, duplicate: true })" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Duplicate</button>
                    @if ($p['status'] === 'Posted' && ! $cur->reversedEntry)
                        <button type="button" wire:click="$set('reversing', {{ $cur->id }})" class="rounded-[8px] border border-[#fecaca] bg-white px-[14px] py-[9px] text-[13px] text-[#b91c1c]">Reverse</button>
                    @endif
                    @if (in_array($p['status'], ['Draft', 'Rejected'], true))
                        <a href="{{ route('accounts.posting', ['v' => $cur->id, 'tab' => $p['status'] === 'Rejected' ? 'rejected' : ($cur->status === \App\Enums\Accounts\JournalEntryStatus::SCHEDULED ? 'scheduled' : 'open')]) }}" wire:navigate class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">Open in Posting Pending</a>
                    @endif
                </div>
            @else
                <div class="px-[18px] py-[36px] text-center text-[#6b7280]">কোনো লেনদেন বাছাই করা নেই।</div>
            @endif
        </aside>
    </div>

    @if ($reversing)
        <x-accounts.modal title="Reverse voucher" close="$set('reversing', null)">
            <div class="text-[13.5px] text-[#374151]">একটা উল্টো journal post হবে; মূল voucher যেমন আছে তেমন থাকবে।</div>
            <input type="text" wire:model="reverseReason" placeholder="কারণ (optional)" class="rounded-[9px] border border-[#d1d5db] px-[12px] py-[9px] text-[14px]">
            <x-slot:footer>
                <button type="button" wire:click="$set('reversing', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="reverse" class="rounded-[9px] border-0 bg-[#b91c1c] px-[18px] py-[10px] text-[14px] font-bold text-white">Reverse</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @livewire('accounts.voucher-form')
</div>
