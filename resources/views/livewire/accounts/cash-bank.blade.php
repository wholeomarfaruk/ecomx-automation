{{-- Cash & Bank — design: CashBank.dc.html --}}
@php
    $t = $this->totals;
    $a = $this->current;
    $led = $this->ledger;
    $kinds = $this::KINDS;
    $input = 'w-full text-[13px] px-[10px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $balances = $this->accounts->pluck('balance', 'id')->all();
    $names = $this->accounts->pluck('name', 'id')->all();
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="নগদ ও ব্যাংক · Cash & Bank" subtitle="কোন account-এ কত টাকা, কোথা থেকে এলো, কোথায় গেলো">
        <a href="#transfer" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] !text-[#374151]">+ Transfer</a>
        <button type="button" wire:click="$set('showImport', true)" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Statement import</button>
        <button type="button" wire:click="$set('showNew', true)" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">+ New account</button>
    </x-accounts.page-head>

    {{-- Total --}}
    <section class="flex flex-col gap-[14px] rounded-[14px] border border-[#e5e7eb] bg-white px-[20px] py-[18px]">
        <div class="flex flex-wrap items-end justify-between gap-[14px]">
            <div>
                <div class="text-[12.5px] text-[#4b5563]">মোট টাকা · All accounts (posted)</div>
                <div class="mt-[4px] text-[30px] font-bold">{{ $money($t['total']) }}</div>
                <div class="mt-[2px] text-[12px] text-[#6b7280]">{{ $t['draftCount'] ? 'Draft post হলে হবে ' . $money($t['after']) . ' · ' . $t['draftCount'] . ' drafts' : 'কোনো draft নেই' }}</div>
            </div>
            <div class="flex flex-wrap gap-[22px] text-[13px]">
                @foreach ($kinds as $k => [$label, $cls, $color])
                    <div><div class="flex items-center gap-[6px] text-[#4b5563]"><span class="h-[10px] w-[10px] rounded-[3px]" style="background: {{ $color }}"></span>{{ $label }}</div><div class="mt-[2px] font-bold">{{ $money($t['byKind'][$k]) }}</div></div>
                @endforeach
            </div>
        </div>
        <div class="flex h-[10px] gap-[2px] overflow-hidden rounded-[6px]">
            @foreach ($kinds as $k => [$label, $cls, $color])
                @if ($t['pct'][$k] > 0)<div style="width: {{ $t['pct'][$k] }}%; background: {{ $color }}"></div>@endif
            @endforeach
        </div>
    </section>

    {{-- Accounts --}}
    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(210px,1fr))]">
        @foreach ($this->accounts as $c)
            @php $on = $a && $a['id'] === $c['id']; @endphp
            <button type="button" wire:key="acc-{{ $c['id'] }}" wire:click="pick({{ $c['id'] }})"
                    @class(['cursor-pointer rounded-[14px] bg-white px-[16px] py-[14px] text-left text-[#111827]', 'border-2 border-[#4f46e5] shadow-[0_0_0_3px_#e0e7ff]' => $on, 'border border-[#e5e7eb]' => ! $on])>
                <div class="flex items-center justify-between gap-[8px]">
                    <span class="rounded-[6px] px-[8px] py-[2px] text-[11.5px] font-semibold {{ $kinds[$c['kind']][1] ?? '' }}">{{ $kinds[$c['kind']][0] ?? $c['kind'] }}</span>
                    <span @class(['text-[11px]', 'text-[#047857]' => $c['recOk'], 'font-semibold text-[#b45309]' => ! $c['recOk']])>{{ $c['rec'] }}</span>
                </div>
                <div class="mt-[10px] text-[15px] font-bold">{{ $c['name'] }}</div>
                <div class="text-[12px] text-[#6b7280]">{{ $c['number'] ?: '—' }}</div>
                <div class="mt-[8px] text-[22px] font-bold">{{ $money($c['balance']) }}</div>
                <div class="mt-[4px] flex gap-[10px] text-[12px]"><span class="text-[#047857]">+ {{ $money($c['in']) }}</span><span class="text-[#b91c1c]">− {{ $money($c['out']) }}</span><span class="text-[#6b7280]">today</span></div>
            </button>
        @endforeach
    </div>

    @if ($a)
    <div class="flex flex-wrap items-start gap-[16px]">

        {{-- Ledger --}}
        <section class="min-w-0 flex-[999_1_600px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <div>
                    <h2 class="m-0 text-[15px] font-bold">{{ $a['name'] }} · Ledger</h2>
                    <div class="mt-[2px] text-[12.5px] text-[#6b7280]">{{ \App\Support\AccountsPeriod::label($led['start'], $led['end']) }} · posted entries</div>
                </div>
                <div class="acc-no-print flex flex-wrap items-center gap-[8px]">
                    <label for="led-range" class="text-[13px] text-[#4b5563]">Period</label>
                    <select id="led-range" wire:model.live="ledgerPeriod" class="rounded-[9px] border border-[#d1d5db] bg-white py-[8px] pl-[10px] pr-[30px] text-[13px]"><option value="week">This week</option><option value="today">Today</option><option value="month">This month</option><option value="custom">Custom</option></select>
                    @if ($ledgerPeriod === 'custom')
                        <input type="text" readonly placeholder="YYYY-MM-DD" wire:model.live="from" class="flatpickr-only-date cursor-pointer bg-white rounded-[8px] border border-[#d1d5db] px-[8px] py-[6px] text-[12.5px]"><input type="text" readonly placeholder="YYYY-MM-DD" wire:model.live="to" class="flatpickr-only-date cursor-pointer bg-white rounded-[8px] border border-[#d1d5db] px-[8px] py-[6px] text-[12.5px]">
                    @endif
                    <button type="button" onclick="window.print()" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[8px] text-[13px] text-[#374151]">Print</button>
                    <a href="{{ route('accounts.transactions', ['account' => $a['id'], 'period' => 'month']) }}" wire:navigate class="text-[13px]">All transactions</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[680px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Date</th><th class="px-[8px] py-[10px] font-semibold">Voucher</th><th class="px-[8px] py-[10px] font-semibold">Particulars</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">In</th><th class="px-[8px] py-[10px] text-right font-semibold">Out</th><th class="px-[18px] py-[10px] text-right font-semibold">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-[#e5e7eb] bg-[#f8fafc]">
                            <td colspan="5" class="px-[18px] py-[9px] font-semibold text-[#334155]">Opening balance · {{ $led['start']->format('d M') }}</td>
                            <td class="px-[18px] py-[9px] text-right font-bold">{{ $money($led['opening']) }}</td>
                        </tr>
                        @forelse ($led['rows'] as $r)
                            <tr class="border-t border-[#f0f1f4]">
                                <td class="whitespace-nowrap py-[11px] pl-[18px] pr-[8px] text-[#374151]">{{ $r['date'] }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] font-semibold"><a href="{{ route('accounts.transactions', ['v' => $r['id'], 'period' => 'month']) }}" wire:navigate>{{ $r['ref'] }}</a></td>
                                <td class="px-[8px] py-[11px]">{{ $r['text'] }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-semibold text-[#047857]">{{ $r['in'] > 0 ? $money($r['in']) : '' }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-semibold text-[#b91c1c]">{{ $r['out'] > 0 ? $money($r['out']) : '' }}</td>
                                <td class="whitespace-nowrap px-[18px] py-[11px] text-right font-semibold">{{ $money($r['bal']) }}</td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#f0f1f4]"><td colspan="6" class="px-[18px] py-[22px] text-center text-[#6b7280]">এই সময়ে কোনো লেনদেন নেই।</td></tr>
                        @endforelse
                        <tr class="border-t-2 border-[#e5e7eb] bg-[#f9fafb] font-bold">
                            <td colspan="3" class="px-[18px] py-[11px]">Closing balance · {{ $led['end']->format('d M') }}</td>
                            <td class="px-[8px] py-[11px] text-right text-[#047857]">{{ $money($led['in']) }}</td>
                            <td class="px-[8px] py-[11px] text-right text-[#b91c1c]">{{ $money($led['out']) }}</td>
                            <td class="px-[18px] py-[11px] text-right">{{ $money($led['closing']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @if ($this->draftStrip)
                <div class="flex flex-wrap items-center justify-between gap-[10px] border-t border-[#fde68a] bg-[#fffbeb] px-[18px] py-[11px] text-[13px] text-[#78350f]">
                    <span>{{ $this->draftStrip }}</span>
                    <a href="{{ route('accounts.posting') }}" wire:navigate class="font-semibold !text-[#92400e]">Review drafts</a>
                </div>
            @endif
        </section>

        {{-- Right column --}}
        <div class="acc-no-print flex min-w-0 flex-[1_1_360px] flex-col gap-[16px]">

            @if ($a['kind'] === 'cash')
                <section wire:key="count-{{ $a['id'] }}"
                         x-data="{ counts: @js(collect(\App\Models\CashCount::DENOMINATIONS)->keys()->mapWithKeys(fn ($k) => [$k => (int) ($this->lastCount?->count_date?->isToday() ? ($this->lastCount->denominations[$k] ?? 0) : 0)])->all()),
                                   values: @js(collect(\App\Models\CashCount::DENOMINATIONS)->map(fn ($d) => $d[1])->all()),
                                   book: {{ (float) $a['balance'] }},
                                   get total() { return Object.keys(this.values).reduce((s, k) => s + (parseInt(this.counts[k]) || 0) * this.values[k], 0) },
                                   get diff() { return Math.round(this.total - this.book) },
                                   fmt(n) { return '৳' + Math.round(Math.abs(n)).toLocaleString('en-IN') } }"
                         class="flex flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                    <div><h2 class="m-0 text-[15px] font-bold">নগদ গণনা · Cash count</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">দিন শেষে হাতের টাকা গুনে মিলিয়ে নিন</div></div>
                    <table class="text-[13px]">
                        <thead><tr class="text-left text-[11.5px] uppercase text-[#6b7280]"><th class="py-[6px] font-semibold">Note</th><th class="py-[6px] font-semibold">Count</th><th class="py-[6px] text-right font-semibold">Total</th></tr></thead>
                        <tbody>
                            @foreach (\App\Models\CashCount::DENOMINATIONS as $k => [$label, $value])
                                <tr class="border-t border-[#f0f1f4]">
                                    <td class="py-[6px] font-semibold">{{ $label }}</td>
                                    <td class="py-[5px]"><label for="cc-{{ $k }}" class="sr-only">{{ $label }} count</label><input id="cc-{{ $k }}" type="number" min="0" x-model="counts.{{ $k }}" class="w-[84px] rounded-[7px] border border-[#d1d5db] px-[8px] py-[6px] text-[13px]"></td>
                                    <td class="py-[6px] text-right" x-text="fmt((parseInt(counts.{{ $k }}) || 0) * {{ $value }})"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="grid grid-cols-[1fr_auto] gap-[6px] rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px] text-[13px]">
                        <span class="text-[#4b5563]">গুনে পাওয়া</span><b x-text="fmt(total)"></b>
                        <span class="text-[#4b5563]">Book balance</span><b>{{ $money($a['balance']) }}</b>
                        <span class="font-bold" x-text="diff === 0 ? 'মিলে গেছে' : (diff < 0 ? 'কম আছে · Shortage' : 'বেশি আছে · Excess')"></span><b :class="diff === 0 ? 'text-[#047857]' : 'text-[#b91c1c]'" x-text="diff === 0 ? '৳0' : fmt(diff)"></b>
                    </div>
                    @if (\App\Support\AccountsAccess::can('cash'))
                        <button type="button" @click="$wire.saveCount(counts)" :class="diff === 0 ? 'bg-[#4f46e5]' : 'bg-[#b91c1c]'" class="rounded-[8px] border-0 px-[14px] py-[10px] text-[13px] font-semibold text-white"
                                x-text="diff === 0 ? 'Save cash count' : (diff < 0 ? 'Record shortage ' : 'Record excess ') + fmt(diff)"></button>
                    @endif
                    @if ($this->lastCount)
                        <div class="text-[12px] text-[#6b7280]">Last count: {{ $this->lastCount->count_date->format('d M Y') }} · {{ abs((float) $this->lastCount->difference) < 0.5 ? 'matched' : ((float) $this->lastCount->difference < 0 ? 'short ' : 'excess ') . $money(abs((float) $this->lastCount->difference)) }}</div>
                    @endif
                </section>
            @else
                @php $r = $this->reconcile; $boxOk = $r['diff'] === null || $r['ok']; @endphp
                <section wire:key="rec-{{ $a['id'] }}" class="flex flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                    <div><h2 class="m-0 text-[15px] font-bold">মিলিয়ে দেখা · Reconcile</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">Bank / app-এর balance-এর সাথে software মিলছে কিনা</div></div>
                    <div class="flex flex-col gap-[6px]">
                        <label for="stmt-bal" class="text-[13px] font-semibold">Statement balance ({{ today()->format('d M') }})</label>
                        <input id="stmt-bal" type="text" inputmode="decimal" wire:model.live.debounce.500ms="statement" placeholder="৳0" class="rounded-[9px] border border-[#d1d5db] px-[12px] py-[9px] text-[14px]">
                    </div>
                    <div class="grid grid-cols-[1fr_auto] gap-[6px] rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px] text-[13px]">
                        <span class="text-[#4b5563]">Book balance</span><b>{{ $money($a['balance']) }}</b>
                        <span class="font-bold">Difference</span><b @class(['text-[#b45309]' => $r['diff'] !== null && ! $r['ok'], 'text-[#047857]' => $r['diff'] === null || $r['ok']])>{{ $r['diff'] === null ? '—' : ($r['ok'] ? '৳0' : $money(abs($r['diff']))) }}</b>
                    </div>
                    <div @class(['rounded-[10px] border px-[12px] py-[10px] text-[13px] leading-[1.5]', 'border-[#a7f3d0] bg-[#ecfdf5] text-[#064e3b]' => $boxOk, 'border-[#fde68a] bg-[#fffbeb] text-[#78350f]' => ! $boxOk])>{{ $r['text'] }}</div>
                    <div class="flex justify-end gap-[8px]">
                        <button type="button" wire:click="reconcileSecondary" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">{{ $r['secondary'] }}</button>
                        <button type="button" wire:click="reconcileAction" @disabled($r['action'] === 'none' || ! \App\Support\AccountsAccess::can('cash')) class="rounded-[8px] border-0 bg-[#4f46e5] px-[14px] py-[9px] text-[13px] font-semibold text-white disabled:opacity-50">{{ $r['primary'] }}</button>
                    </div>
                    <div class="text-[12px] text-[#6b7280]">Last reconciled: {{ $r['last'] }}</div>
                </section>
            @endif

            <section id="transfer"
                     x-data="{ bal: @js($balances), names: @js($names), t: $wire.entangle('transfer'),
                               fmt(n) { return (n < 0 ? '− ' : '') + '৳' + Math.round(Math.abs(n)).toLocaleString('en-IN') },
                               get amt() { return parseFloat(this.t.amount) || 0 }, get fee() { return parseFloat(this.t.charge) || 0 } }"
                     class="flex flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <div><h2 class="m-0 text-[15px] font-bold">Transfer · এক account থেকে আরেক account</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">আয় বা খরচ না — শুধু টাকা জায়গা বদলায়</div></div>
                <div class="grid grid-cols-2 gap-[10px]">
                    <div class="flex flex-col gap-[6px]"><label for="tr-from" class="text-[13px] font-semibold">From</label><select id="tr-from" x-model.number="t.from" class="{{ $input }}">@foreach ($names as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
                    <div class="flex flex-col gap-[6px]"><label for="tr-to" class="text-[13px] font-semibold">To</label><select id="tr-to" x-model.number="t.to" class="{{ $input }}">@foreach ($names as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
                    <div class="flex flex-col gap-[6px]"><label for="tr-amt" class="text-[13px] font-semibold">Amount (৳)</label><input id="tr-amt" type="number" min="0" x-model="t.amount" class="{{ $input }}"></div>
                    <div class="flex flex-col gap-[6px]"><label for="tr-fee" class="text-[13px] font-semibold">Charge (৳)</label><input id="tr-fee" type="number" min="0" x-model="t.charge" class="{{ $input }}"></div>
                    <div class="flex flex-col gap-[6px]"><label for="tr-date" class="text-[13px] font-semibold">Date</label><input id="tr-date" type="text" readonly placeholder="YYYY-MM-DD" x-model="t.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }} py-[8px]"></div>
                    <div class="flex flex-col gap-[6px]"><label for="tr-note" class="text-[13px] font-semibold">Note</label><input id="tr-note" type="text" x-model="t.note" placeholder="যেমন: cash out" class="{{ $input }}"></div>
                </div>
                @error('transfer.*') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px] text-[12.5px] leading-[1.6] text-[#4b5563]">
                    <span x-text="names[t.from] + ' ' + fmt(bal[t.from] || 0) + ' → '"></span><b x-text="fmt((bal[t.from] || 0) - amt - fee)"></b>
                    <span x-text="'(' + fmt(amt) + (fee ? ' + ' + fmt(fee) + ' charge' : '') + ')'"></span><br>
                    <span x-text="names[t.to] + ' ' + fmt(bal[t.to] || 0) + ' → '"></span><b x-text="fmt((bal[t.to] || 0) + amt)"></b><br>
                    <span class="text-[#6b7280]">Charge যাবে Bank &amp; MFS charges খরচে।</span>
                </div>
                <div class="flex justify-end gap-[8px]">
                    <button type="button" wire:click="saveTransfer(false)" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Save as draft</button>
                    @if (\App\Support\AccountsAccess::can('post'))
                        <button type="button" wire:click="saveTransfer(true)" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Transfer</button>
                    @endif
                </div>
            </section>
        </div>
    </div>
    @else
        <div class="rounded-[14px] border border-dashed border-[#c7d2fe] bg-white px-[18px] py-[36px] text-center text-[#6b7280]">কোনো cash / bank account নেই। <button type="button" wire:click="$set('showNew', true)" class="font-semibold text-[#4338ca]">+ New account</button></div>
    @endif

    {{-- New account --}}
    @if ($showNew)
        <x-accounts.modal title="+ New account" close="$set('showNew', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Account name</label><input type="text" wire:model="newAccount.name" placeholder="City Bank" class="{{ $input }} text-[14px]">
                    @error('newAccount.name') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Kind</label><select wire:model="newAccount.kind" class="{{ $input }} text-[14px]">@foreach ($kinds as $k => [$label])<option value="{{ $k }}">{{ $label }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">A/C / wallet no.</label><input type="text" wire:model="newAccount.number" placeholder="Current · A/C ••7781" class="{{ $input }} text-[14px]"></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Opening balance (৳)</label><input type="number" wire:model="newAccount.opening" placeholder="0" class="{{ $input }} text-[14px]"></div>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showNew', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="createAccount" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Add account</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    {{-- Statement import --}}
    @if ($showImport)
        <x-accounts.modal title="Statement import · {{ $a['name'] ?? '' }}" close="$set('showImport', false)" width="max-w-[760px]">
            <div class="text-[13px] text-[#4b5563]">Bank / bKash / Nagad statement-এর CSV বা Excel দিন (Date, Description, Amount — অথবা Debit / Credit column)। Amount আর তারিখ (±৩ দিন) দিয়ে voucher-এর সাথে মেলানো হবে।</div>
            <div x-data>
                <input type="file" x-ref="sf" wire:model="importFile" accept=".csv,.xlsx" class="hidden">
                <button type="button" @click="$refs.sf.click()" class="rounded-[8px] border border-[#c7d2fe] bg-white px-[14px] py-[8px] text-[13px] font-semibold text-[#4338ca]">Choose file</button>
                <span wire:loading wire:target="importFile" class="text-[12px] text-[#6b7280]">Reading…</span>
                @error('importFile') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
            </div>
            @if ($importResult)
                <div class="text-[13px] font-semibold">{{ $importResult['matched'] }} / {{ $importResult['total'] }} line matched</div>
                <div class="max-h-[360px] overflow-auto rounded-[10px] border border-[#eef0f3]">
                    <table class="text-[12.5px]">
                        <thead class="sticky top-0 bg-[#f9fafb]"><tr class="text-left text-[11px] uppercase text-[#6b7280]"><th class="px-[10px] py-[7px]">Date</th><th class="px-[8px] py-[7px]">Description</th><th class="px-[8px] py-[7px] text-right">Amount</th><th class="px-[10px] py-[7px]">Match</th></tr></thead>
                        <tbody>
                            @foreach ($importResult['rows'] as $i => $row)
                                <tr class="border-t border-[#f0f1f4]">
                                    <td class="whitespace-nowrap px-[10px] py-[6px]">{{ $row['date'] ?? '—' }}</td>
                                    <td class="px-[8px] py-[6px]">{{ $row['desc'] }}</td>
                                    <td class="whitespace-nowrap px-[8px] py-[6px] text-right font-semibold {{ $row['amount'] >= 0 ? 'text-[#047857]' : 'text-[#b91c1c]' }}">{{ $money($row['amount'], true) }}</td>
                                    <td class="whitespace-nowrap px-[10px] py-[6px]">
                                        @if ($row['match'])<span class="rounded-[6px] bg-[#d1fae5] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#065f46]">{{ $row['match'] }}</span>
                                        @else<button type="button" wire:click="addFromStatement({{ $i }})" class="text-[12.5px] font-semibold text-[#4338ca]">+ Add entry</button>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-accounts.modal>
    @endif

    @livewire('accounts.voucher-form')
</div>
