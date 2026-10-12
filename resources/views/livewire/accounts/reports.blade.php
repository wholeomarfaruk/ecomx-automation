{{-- Reports — design: Reports.dc.html --}}
@use('App\Services\Accounts\Reports', 'Engine')
@use('App\Models\SavedReport', 'Saved')
@php
    $r = $this->report;
    $dr = $this->drafts;
    $field = 'text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $err = 'text-[12px] text-[#b91c1c]';
    $info = match ($rep) {
        'daily' => ['Daily report · দিনের হিসাব', $this->day()->format('l, j F Y')],
        'saved' => ['Saved reports', 'Save করা report, schedule আর archive'],
        default => [$r['title'] ?? '', $r['period'] ?? ''],
    };
    $dot = ['ok' => ['✓', 'bg-[#d1fae5] text-[#065f46]'], 'bad' => ['×', 'bg-[#fee2e2] text-[#991b1b]'], 'warn' => ['!', 'bg-[#fef3c7] text-[#92400e]']];
    $isBook = in_array($rep, ['cash', 'bank'], true);
    $asOf = in_array($rep, Engine::AS_OF, true);
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="রিপোর্ট · Reports" subtitle="দৈনিক হিসাব, লাভ-ক্ষতি, ব্যালেন্স শিট, খাতা — শুধু post হওয়া entry থেকে">
        <button type="button" wire:click="pick('saved')" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Saved reports · {{ $this->saved->count() }}</button>
        @if ($rep !== 'saved')
            <button type="button" wire:click="toggleSave" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Save this report</button>
        @endif
    </x-accounts.page-head>

    @if ($saving)
        <section class="flex flex-col gap-[14px] rounded-[14px] border-2 border-[#c7d2fe] bg-white px-[20px] py-[16px]">
            <div class="flex items-center justify-between gap-[10px]"><h2 class="m-0 text-[15px] font-bold">{{ $editId ? 'Edit' : 'Save' }}: {{ Engine::title($save['key'] ?? $rep) }}</h2><span class="text-[12px] text-[#6b7280]">{{ $editId ? 'Filter আগের মতোই থাকবে' : 'এখনকার filter সহ save হবে' }}</span></div>
            <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
                <div class="flex flex-col gap-[6px]"><label for="sv-name" class="text-[13px] font-semibold">Name</label><input id="sv-name" type="text" wire:model="save.name" class="{{ $field }}">@error('save.name')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label for="sv-sch" class="text-[13px] font-semibold">নিজে থেকে চালাও · Schedule</label><select id="sv-sch" wire:model="save.schedule" class="{{ $field }} px-[10px]">@foreach (Saved::SCHEDULES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label for="sv-to" class="text-[13px] font-semibold">কাকে পাঠাবো</label><select id="sv-to" wire:model="save.recipients" class="{{ $field }} px-[10px]">@foreach (Saved::RECIPIENTS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            </div>
            <div class="flex flex-wrap gap-[16px] text-[13px]">
                @foreach (Saved::CHANNELS as $k => $l)
                    <label class="flex items-center gap-[6px]"><input type="checkbox" value="{{ $k }}" wire:model="save.channels" class="accent-[#4f46e5]"> {{ $l }}</label>
                @endforeach
            </div>
            <div class="flex justify-end gap-[8px]">
                @if ($editId)
                    <button type="button" wire:click="deleteSaved({{ $editId }})" wire:confirm="এই saved report মুছে ফেলবেন?" class="mr-auto rounded-[8px] border border-[#fecaca] bg-white px-[14px] py-[9px] text-[13px] text-[#b91c1c]">Delete</button>
                @endif
                <button type="button" wire:click="$set('saving', false)" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveReport" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Save</button>
            </div>
        </section>
    @endif

    <div class="flex flex-wrap items-start gap-[16px]">

        {{-- Report list --}}
        <nav aria-label="Reports" class="acc-no-print flex max-w-full flex-[1_1_250px] flex-col gap-[2px] rounded-[14px] border border-[#e5e7eb] bg-white p-[12px]">
            @foreach (Engine::GROUPS as $title => $items)
                <div class="px-[10px] pb-[6px] pt-[12px] text-[11px] uppercase tracking-[0.08em] text-[#6b7280]">{{ $title }}</div>
                @foreach ($items as $k => [$label, $sub])
                    <button type="button" wire:click="pick('{{ $k }}')" @class([
                        'flex items-center justify-between gap-[8px] rounded-[8px] border-0 px-[10px] py-[9px] text-left text-[13.5px]',
                        'bg-[#eef2ff] font-bold text-[#312e81]' => $rep === $k, 'bg-transparent text-[#374151]' => $rep !== $k,
                    ])><span>{{ $label }}</span><span class="text-[11.5px] font-normal text-[#9ca3af]">{{ $k === 'saved' ? $this->saved->count() . ' saved' : $sub }}</span></button>
                @endforeach
            @endforeach
        </nav>

        {{-- Report body --}}
        <section class="min-w-0 flex-[999_1_640px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-[12px] border-b border-[#f0f1f4] px-[20px] py-[16px]">
                <div>
                    <h2 class="m-0 text-[18px] font-bold">{{ $info[0] }}</h2>
                    <div class="mt-[2px] text-[12.5px] text-[#6b7280]">{{ \App\Support\AccountsSettings::businessName() }} · {{ $info[1] }}</div>
                </div>
                @if (! in_array($rep, ['daily', 'saved'], true))
                    <div class="acc-no-print flex flex-wrap items-center gap-[8px]">
                        @if ($isBook && ! empty($r['accountOptions']))
                            <select wire:model.live="accountId" aria-label="Account" class="rounded-[9px] border border-[#d1d5db] bg-white py-[8px] pl-[10px] pr-[30px] text-[13px]">
                                @foreach ($r['accountOptions'] as $a)<option value="{{ $a['id'] }}" @selected($a['id'] === ($r['accountId'] ?? 0))>{{ $a['name'] }}</option>@endforeach
                            </select>
                        @endif
                        @if ($rep === 'party')
                            <div class="w-[220px]" wire:key="party-pick"><x-searchable-select field="profileId" :value="$profileId" :options="$this->profileOptions" placeholder="— Profile —" /></div>
                        @endif
                        @if ($asOf)
                            <label for="rp-asof" class="text-[13px] text-[#4b5563]">As of</label>
                            <input id="rp-asof" type="text" readonly placeholder="YYYY-MM-DD" wire:model.live="asOf" class="flatpickr-only-date cursor-pointer bg-white rounded-[9px] border border-[#d1d5db] bg-white px-[10px] py-[7px] text-[13px]">
                        @else
                            <label for="rp-period" class="text-[13px] text-[#4b5563]">Period</label>
                            <select id="rp-period" wire:model.live="period" class="rounded-[9px] border border-[#d1d5db] bg-white py-[8px] pl-[10px] pr-[30px] text-[13px]">
                                @foreach (Engine::PERIODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                            </select>
                            @if ($period === 'custom')
                                <input type="text" readonly placeholder="YYYY-MM-DD" wire:model.live="from" aria-label="From" class="flatpickr-only-date cursor-pointer bg-white rounded-[9px] border border-[#d1d5db] bg-white px-[10px] py-[7px] text-[13px]">
                                <input type="text" readonly placeholder="YYYY-MM-DD" wire:model.live="to" aria-label="To" class="flatpickr-only-date cursor-pointer bg-white rounded-[9px] border border-[#d1d5db] bg-white px-[10px] py-[7px] text-[13px]">
                            @endif
                        @endif
                        <a href="{{ $this->exportUrl('excel') }}" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[8px] text-[13px] !text-[#374151]">Excel</a>
                        <a href="{{ $this->exportUrl('pdf') }}" target="_blank" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[8px] text-[13px] !text-[#374151]">PDF</a>
                        <button type="button" onclick="window.print()" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[8px] text-[13px] text-[#374151]">Print</button>
                    </div>
                @endif
            </div>

            @if ($dr['count'] > 0)
                <div class="acc-no-print flex flex-wrap items-center gap-[10px] border-b border-[#fde68a] bg-[#fffbeb] px-[20px] py-[10px] text-[13px] text-[#78350f]">
                    <span>{{ $dr['count'] }}টা draft ({{ $money($dr['amount']) }}) এই report-এ ধরা হয়নি।</span>
                    <a href="{{ route('accounts.posting') }}" wire:navigate class="font-semibold !text-[#92400e]">Review</a>
                </div>
            @endif

            {{-- ===== Daily ===== --}}
            @if ($rep === 'daily')
                @php $d = $this->daily; $cl = $this->closing; $closed = $cl && $cl->status !== 'open'; @endphp
                <div class="flex flex-wrap items-center gap-[10px] border-b border-[#f0f1f4] px-[20px] py-[16px]">
                    <label for="dr-date" class="text-[13px] font-semibold">তারিখ</label>
                    <input class="flatpickr-only-date cursor-pointer bg-white rounded-[8px] border border-[#d1d5db] px-[10px] py-[8px] text-[13px]" id="dr-date" type="text" readonly placeholder="YYYY-MM-DD" wire:model.live="date" data-max-date="{{ today()->toDateString() }}">
                    @if ($closed)
                        <span class="rounded-[6px] bg-[#d1fae5] px-[9px] py-[3px] text-[12px] font-semibold text-[#065f46]">{{ $cl->status === 'auto' ? 'Auto-closed' : 'Closed' }} · {{ $cl->closed_at?->format('g:i A') }}{{ $cl->closedBy ? ' by ' . $cl->closedBy->name : '' }}{{ $cl->version > 1 ? ' · v' . $cl->version : '' }}</span>
                        @if ($cl->pdf_path)<a href="{{ route('accounts.reports.closing', $cl) }}" target="_blank" class="text-[12.5px]">PDF</a>@endif
                    @else
                        <span class="rounded-[6px] bg-[#fef3c7] px-[9px] py-[3px] text-[12px] font-semibold text-[#92400e]">Open · এখনো close হয়নি{{ $cl && $cl->reopened_at ? ' (reopened)' : '' }}</span>
                    @endif
                </div>

                @include('accounts.reports.daily-body', ['data' => $d])

                <div class="flex flex-wrap gap-[16px] px-[20px] py-[16px]">
                    <div class="flex flex-[1_1_320px] flex-col gap-[8px]">
                        <div class="text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]">দিন বন্ধের আগে · Checklist</div>
                        @foreach ($d['checklist'] as [$state, $text, $link, $route])
                            <div class="flex items-center justify-between gap-[10px] rounded-[8px] bg-[#f9fafb] px-[10px] py-[8px] text-[13px]">
                                <span class="flex items-center gap-[8px]"><span class="flex h-[18px] w-[18px] flex-none items-center justify-center rounded-full text-[11px] font-bold {{ $dot[$state][1] }}">{{ $dot[$state][0] }}</span>{{ $text }}</span>
                                <a href="{{ route($route) }}" wire:navigate class="whitespace-nowrap text-[12px]">{{ $link }}</a>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-[1_1_280px] flex-col gap-[8px]">
                        <label for="dr-note" class="text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]">দিনের note</label>
                        <textarea id="dr-note" rows="4" wire:model="dayNote" @disabled($closed) placeholder="যেমন: Pathao settlement কাল আসবে, RedX-কে call করতে হবে" class="resize-y rounded-[9px] border border-[#d1d5db] px-[12px] py-[10px] text-[13px]"></textarea>
                        <div class="flex flex-wrap justify-end gap-[8px]">
                            <button type="button" wire:click="sendToOwners" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Send to owners</button>
                            @if ($closed)
                                @if (\App\Support\AccountsAccess::can('approve'))
                                    <button type="button" wire:click="reopenDay" wire:confirm="দিনটা আবার খুলবেন? আবার close করলে নতুন version হবে।" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[16px] py-[9px] text-[13px] font-semibold text-[#374151]">Reopen day</button>
                                @endif
                            @else
                                <button type="button" wire:click="closeDay" wire:confirm="{{ $this->day()->format('d M') }}-এর হিসাব close করে save করবেন?" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Close day &amp; save</button>
                            @endif
                        </div>
                        <div class="text-[12px] text-[#6b7280]">Close করলে আজকের report archive-এ থাকবে, পরে বদলালে নতুন version হবে।</div>
                    </div>
                </div>

                <div class="border-t border-[#f0f1f4]">
                    <div class="px-[20px] py-[12px] text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]">আগের দিনগুলো</div>
                    <table class="text-[13px]"><tbody>
                        @forelse ($this->history as $h)
                            @php $snap = (array) $h->snapshot; $tin = $snap['tiles']['in'][0] ?? 0; $tout = $snap['tiles']['out'][0] ?? 0; @endphp
                            <tr class="border-t border-[#f0f1f4]">
                                <td class="whitespace-nowrap px-[20px] py-[9px] font-semibold"><a href="#" wire:click.prevent="$set('date', '{{ $h->date->toDateString() }}')" class="!text-[#111827]">{{ $h->date->format('d M') }} · {{ \App\Support\AccountsPeriod::bnWeekday($h->date) }}</a></td>
                                <td class="px-[12px] py-[9px] text-[#047857]">+ {{ $money($tin) }}</td>
                                <td class="px-[12px] py-[9px] text-[#b91c1c]">− {{ $money($tout) }}</td>
                                <td class="px-[12px] py-[9px] text-[#6b7280]">{{ $h->status === 'auto' ? 'Auto-closed · ' . $h->closed_at?->format('g:i A') : ($h->status === 'open' ? 'Reopened' : 'Closed by ' . ($h->closedBy?->name ?? '—') . ' · ' . $h->closed_at?->format('g:i A')) }}{{ (float) $h->short_amount > 0 ? ' · ' . $money($h->short_amount) . ' short' : '' }}</td>
                                <td class="px-[12px] py-[9px]">
                                    @if ((float) $h->short_amount > 0)<span class="whitespace-nowrap rounded-[6px] bg-[#fef3c7] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#92400e]">Short</span>
                                    @elseif ($h->status === 'auto')<span class="whitespace-nowrap rounded-[6px] bg-[#f3f4f6] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#374151]">Auto</span>
                                    @elseif ($h->status === 'open')<span class="whitespace-nowrap rounded-[6px] bg-[#fef3c7] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#92400e]">Open</span>
                                    @else<span class="whitespace-nowrap rounded-[6px] bg-[#d1fae5] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#065f46]">Closed</span>@endif
                                </td>
                                <td class="px-[20px] py-[9px] text-right">@if ($h->pdf_path)<a href="{{ route('accounts.reports.closing', $h) }}" target="_blank" class="text-[12.5px]">PDF</a>@endif</td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#f0f1f4]"><td class="px-[20px] py-[14px] text-[#6b7280]">এখনো কোনো দিন close হয়নি।</td></tr>
                        @endforelse
                    </tbody></table>
                </div>

            {{-- ===== Saved ===== --}}
            @elseif ($rep === 'saved')
                @php $ac = $this->archiveCounts; @endphp
                <div class="overflow-x-auto">
                    <table class="min-w-[760px] text-[13px]">
                        <thead><tr class="bg-[#f9fafb] text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]"><th class="px-[20px] py-[10px] font-semibold">Saved report</th><th class="px-[12px] py-[10px] font-semibold">Schedule</th><th class="px-[12px] py-[10px] font-semibold">পাঠানো হয়</th><th class="px-[12px] py-[10px] font-semibold">Last run</th><th class="px-[20px] py-[10px] text-right"><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody>
                            @forelse ($this->saved as $v)
                                @php $f = (array) $v->filters; $ch = collect((array) $v->channels)->map(fn ($c) => ['archive' => 'archive', 'email' => 'email', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS summary'][$c] ?? $c)->implode(' + '); @endphp
                                <tr class="border-t border-[#f0f1f4]" wire:key="sv-{{ $v->id }}">
                                    <td class="px-[20px] py-[11px]"><div class="font-bold">{{ $v->name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ Engine::title($v->report_key) }} · {{ Engine::PERIODS[$f['period'] ?? ''] ?? (($f['as_of'] ?? null) ? 'As of ' . $f['as_of'] : 'Default filter') }}</div></td>
                                    <td class="px-[12px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] bg-[#eef2ff] px-[8px] py-[3px] text-[12px] font-semibold text-[#3730a3]">{{ Saved::SCHEDULES[$v->schedule] ?? $v->schedule }}</span></td>
                                    <td class="px-[12px] py-[11px] text-[#374151]">{{ Saved::RECIPIENTS[$v->recipients] ?? $v->recipients }}{{ $ch ? ' · ' . $ch : '' }}</td>
                                    <td class="whitespace-nowrap px-[12px] py-[11px] text-[#6b7280]">{{ $v->last_run_at?->format('d M, g:i A') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-[20px] py-[11px] text-right">
                                        <a href="#" wire:click.prevent="openSaved({{ $v->id }})" class="font-semibold">Open</a><span class="text-[#d1d5db]"> · </span>
                                        <a href="#" wire:click.prevent="runNow({{ $v->id }})">Run now</a><span class="text-[#d1d5db]"> · </span>
                                        <a href="#" wire:click.prevent="editSaved({{ $v->id }})">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#f0f1f4]"><td colspan="5" class="px-[20px] py-[30px] text-center text-[#6b7280]">কোনো report save করা নেই। যেকোনো report খুলে "Save this report" চাপুন।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-[10px] border-t border-[#f0f1f4] px-[20px] py-[14px] text-[13px]">
                    <span class="text-[#4b5563]"><b>PDF archive</b> · {{ $ac['total'] }}টা saved copy (daily {{ $ac['daily'] }}, monthly {{ $ac['monthly'] }}, other {{ $ac['other'] }})</span>
                    <a href="#" wire:click.prevent="$set('showArchive', true)" class="font-semibold">Open archive</a>
                </div>

            {{-- ===== Everything else ===== --}}
            @elseif ($r)
                @if (! empty($r['needsProfile']))
                    <div class="flex flex-col items-center gap-[10px] px-[20px] py-[48px] text-center text-[#6b7280]">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10"></path><path d="M10 20V4"></path><path d="M16 20v-7"></path><path d="M21 20H3"></path></svg>
                        <div class="font-semibold text-[#374151]">Party ledger</div>
                        <div class="max-w-[420px] text-[13px]">{{ $r['note'] }}</div>
                    </div>
                @else
                    @include('accounts.reports.table', ['r' => $r])
                @endif
            @endif
        </section>
    </div>

    @if ($showArchive)
        <x-accounts.modal title="PDF archive" close="$set('showArchive', false)">
            <div class="max-h-[60vh] overflow-y-auto">
                <table class="text-[13px]"><tbody>
                    @forelse ($this->archives as $a)
                        <tr class="border-t border-[#f0f1f4] first:border-t-0">
                            <td class="py-[8px] pr-[8px]"><div class="font-semibold">{{ $a->title }}</div><div class="text-[11.5px] text-[#6b7280]">{{ ucfirst($a->kind) }} · {{ $a->created_at->format('d M Y, g:i A') }}</div></td>
                            <td class="py-[8px] text-right"><a href="{{ route('accounts.reports.archive', $a) }}" target="_blank" class="text-[12.5px] font-semibold">Open</a></td>
                        </tr>
                    @empty
                        <tr><td class="py-[14px] text-center text-[#6b7280]">Archive খালি।</td></tr>
                    @endforelse
                </tbody></table>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showArchive', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Close</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif
</div>
