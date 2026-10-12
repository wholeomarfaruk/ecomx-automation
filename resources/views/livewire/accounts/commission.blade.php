{{-- Customer Commission & Rebate — design: Commission.dc.html --}}
@use('App\Services\Accounts\Commission', 'Engine')
@use('App\Models\CommissionScheme', 'Scheme')
@php
    $s = $this->working;
    $calc = $this->calc;
    $rows = $calc['rows'] ?? collect();
    $sum = $this->summary;
    $cur = $this->current;
    $tiers = $s ? Engine::tiersOf($s) : [];
    $qty = ($s?->basis === 'quantity');
    $val = fn ($v) => $qty ? number_format($v) . ' pcs' : $money($v);
    $rate = fn ($r) => $qty ? '৳' . rtrim(rtrim(number_format($r, 2), '0'), '.') . '/pc' : rtrim(rtrim(number_format($r, 2), '0'), '.') . '%';
    $tierCls = ['bg-[#f3f4f6] text-[#4b5563]', 'bg-[#e0f2fe] text-[#075985]', 'bg-[#e0e7ff] text-[#3730a3]', 'bg-[#ede9fe] text-[#5b21b6]', 'bg-[#fce7f3] text-[#9d174d]'];
    $tc = fn ($i) => $tierCls[min($i, count($tierCls) - 1)];
    $field = 'text-[14px] px-[10px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $input = 'w-full text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $err = 'text-[12px] text-[#b91c1c]';
    $exOut = $this->exList->where('out', true);
    $periodLabel = $calc ? $calc['start']->format('j M') . ' – ' . (today()->lt($calc['end']) ? today()->format('j M Y') : $calc['end']->format('j M Y')) : '';
    $tabCount = ['progress' => $rows->count(), 'schemes' => $this->schemes->count(), 'exclusions' => $exOut->count(), 'settlement' => null];
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="কমিশন · Customer Commission &amp; Rebate" subtitle="বছরের বিক্রির উপর customer-দের commission — কম দামে দেওয়া invoice/item বাদ দিয়ে">
        <label for="cm-fy" class="text-[13px] text-[#4b5563]">Year</label>
        <select id="cm-fy" wire:model.live="fy" class="rounded-[9px] border border-[#d1d5db] bg-white py-[8px] pl-[10px] pr-[30px] text-[13px]">
            @foreach ($this->years as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
        </select>
        @if ($s)
            <button type="button" wire:click="sendStatement" wire:confirm="সব customer-কে তাদের commission progress SMS পাঠাবেন?" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Statement to customers</button>
        @endif
        <button type="button" wire:click="newScheme" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">+ New scheme</button>
    </x-accounts.page-head>

    @if (! $s && $tab !== 'schemes')
        <section class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[40px] text-center">
            <div class="text-[15px] font-bold">এখনো কোনো commission scheme নেই</div>
            <div class="mt-[4px] text-[13px] text-[#6b7280]">Yearly rebate, festival commission বা agent referral — নিয়ম দিন, হিসাব নিজে থেকে হবে।</div>
            <button type="button" wire:click="newScheme" class="mt-[14px] rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">+ New scheme</button>
        </section>
    @else

    @if ($s)
        <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(190px,1fr))]">
            <x-accounts.stat label="যোগ্য বিক্রি · Eligible sales" :value="$val($sum['eligible'])" :sub="$sum['customers'] . ' customers' . ($s->paid_only ? ' · paid invoices only' : '')" />
            <x-accounts.stat label="বাদ দেওয়া বিক্রি · Excluded" :value="$val($sum['excluded'])" color="#b45309" sub="Clearance, বড় discount, cost-এর নিচে" />
            <x-accounts.stat label="এখন পর্যন্ত জমা · Accrued" :value="$money($sum['accrued'])" color="#4338ca" :sub="$sum['modeNote']" />
            <x-accounts.stat label="বছর শেষে আনুমানিক" :value="$money($sum['projected'])" :sub="'Current pace · settle ' . ($calc ? $calc['end']->format('d M Y') : '')" />
            <x-accounts.stat label="গত বছর দেওয়া" :value="$money($sum['last'])" :sub="trim($sum['lastLabel'] . ' · ' . $sum['lastCount'] . ' customers', ' ·')" />
        </div>
    @endif

    <div role="tablist" aria-label="Commission sections" class="flex gap-[2px] overflow-x-auto border-b border-[#e5e7eb]">
        @foreach ($this::TABS as $key => $label)
            <button type="button" role="tab" wire:click="setTab('{{ $key }}')" @class([
                'whitespace-nowrap border-0 border-b-[3px] bg-transparent px-[14px] pb-[11px] pt-[12px] text-[14px]',
                'border-[#4f46e5] font-bold text-[#312e81]' => $tab === $key, 'border-transparent font-medium text-[#4b5563]' => $tab !== $key,
            ])>{{ $label }}@if ($tabCount[$key] !== null) <span @class(['ml-[4px] rounded-[10px] px-[7px] py-[1px] text-[11.5px] font-bold', 'bg-[#e0e7ff] text-[#3730a3]' => $tab === $key, 'bg-[#f3f4f6] text-[#4b5563]' => $tab !== $key])>{{ $tabCount[$key] }}</span>@endif</button>
        @endforeach
    </div>

    {{-- ============ PROGRESS ============ --}}
    @if ($tab === 'progress' && $s)
        <div class="flex flex-wrap items-start gap-[16px]">
            <section class="min-w-0 flex-[999_1_640px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
                <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                    <div><h2 class="m-0 text-[15px] font-bold">{{ $s->name }} · customer progress</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">{{ $periodLabel }} · নাম চাপলে বিস্তারিত</div></div>
                    <div class="flex items-center gap-[6px]">
                        @if ($this->dirty())<span class="rounded-[6px] bg-[#fef3c7] px-[9px] py-[3px] text-[12px] font-semibold text-[#92400e]">Unsaved rule changes</span>@endif
                        @if (count($this->schemes) > 1)
                            <select wire:change="pickScheme($event.target.value)" class="rounded-[8px] border border-[#e5e7eb] bg-white py-[5px] pl-[8px] pr-[28px] text-[12.5px]">@foreach ($this->schemes as $o)<option value="{{ $o->id }}" @selected($o->id === $s->id)>{{ $o->name }}</option>@endforeach</select>
                        @endif
                        <span class="rounded-[6px] bg-[#eef2ff] px-[9px] py-[3px] text-[12px] font-semibold text-[#3730a3]">{{ $s->tier_mode === 'whole' ? 'Rate on whole amount' : 'Slab-wise rate' }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[860px] text-[13px]">
                        <thead>
                            <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                                <th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Customer</th>
                                <th class="px-[8px] py-[10px] text-right font-semibold">Eligible</th>
                                <th class="px-[8px] py-[10px] text-right font-semibold">Excluded</th>
                                <th class="px-[8px] py-[10px] font-semibold">Tier</th>
                                <th class="w-[170px] px-[8px] py-[10px] font-semibold">Next tier</th>
                                <th class="px-[8px] py-[10px] text-right font-semibold">Accrued</th>
                                <th class="px-[18px] py-[10px] font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                @php $on = $cur && $cur['profile_id'] === $r['profile_id']; @endphp
                                <tr wire:key="cm-{{ $r['profile_id'] }}" @class(['border-t border-[#f0f1f4]', 'bg-[#eef2ff] shadow-[inset_3px_0_0_#4f46e5]' => $on])>
                                    <td class="py-[11px] pl-[18px] pr-[8px]"><button type="button" wire:click="select({{ $r['profile_id'] }})" class="border-0 bg-transparent p-0 text-left text-[13px] font-bold text-[#4338ca]">{{ $r['name'] }}</button><div class="text-[11.5px] text-[#6b7280]">{{ $r['phone'] ?: '—' }}</div></td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-semibold">{{ $val($r['eligible']) }}</td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-right text-[#b45309]">{{ $r['excluded'] > 0 ? $val($r['excluded']) : '—' }}</td>
                                    <td class="px-[8px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $tc($r['tier']) }}">{{ $rate($r['rate']) }}</span></td>
                                    <td class="px-[8px] py-[11px]">
                                        <div class="mb-[4px] text-[11.5px] text-[#4b5563]">{{ $r['next'] ? $val($r['gap']) . ' আরও → ' . $rate($r['next']['rate']) : 'Top tier' }}</div>
                                        <div class="h-[6px] overflow-hidden rounded-[4px] bg-[#eef0f3]"><div class="h-full rounded-[4px] bg-[#6366f1]" style="width: {{ $r['pct'] }}%"></div></div>
                                    </td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold text-[#4338ca]">{{ $money($r['amount']) }}{{ $r['hold'] ? ' (hold)' : '' }}</td>
                                    <td class="px-[18px] py-[11px]">
                                        @if ($r['hold'])
                                            <span class="whitespace-nowrap rounded-[6px] bg-[#fee2e2] px-[8px] py-[3px] text-[12px] font-semibold text-[#991b1b]">On hold</span>
                                        @elseif ($r['tier'] === 0 && ($tiers[0]['rate'] ?? 0) == 0)
                                            <span class="whitespace-nowrap rounded-[6px] bg-[#f3f4f6] px-[8px] py-[3px] text-[12px] font-semibold text-[#4b5563]">Below {{ $qty ? number_format($tiers[1]['from'] ?? 0) : \App\Support\Bdt::short($tiers[1]['from'] ?? 0) }}</span>
                                        @else
                                            <span class="whitespace-nowrap rounded-[6px] bg-[#d1fae5] px-[8px] py-[3px] text-[12px] font-semibold text-[#065f46]">Earning</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#f0f1f4]"><td colspan="7" class="px-[18px] py-[30px] text-center text-[#6b7280]">এই period-এ এই scheme-এর কোনো বিক্রি নেই।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($cur)
                <aside class="min-w-0 flex-[1_1_360px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white" wire:key="cm-side-{{ $cur['profile_id'] }}">
                    <div class="border-b border-[#f0f1f4] px-[18px] py-[16px]">
                        <h2 class="m-0 text-[17px] font-bold">{{ $cur['name'] }}</h2>
                        <div class="mt-[2px] text-[12.5px] text-[#6b7280]">{{ $s->name }} · {{ \App\Support\AccountsPeriod::fyLabel($calc['start']) }}</div>
                    </div>
                    <div class="flex flex-col gap-[14px] px-[18px] py-[14px]">
                        <div class="grid grid-cols-[1fr_auto] gap-x-[10px] gap-y-[7px] text-[13px]">
                            <span class="text-[#4b5563]">মোট বিক্রি</span><b>{{ $val($cur['gross']) }}</b>
                            <span class="text-[#4b5563]">− Return</span><span class="text-[#b91c1c]">− {{ $val($cur['returns']) }}</span>
                            <span class="text-[#4b5563]">− বাদ দেওয়া invoice/item</span><span class="text-[#b45309]">− {{ $val($cur['excluded']) }}</span>
                            <span class="text-[#4b5563]">− এখনো unpaid</span><span class="text-[#6b7280]">− {{ $val($cur['unpaid']) }}</span>
                            <span class="border-t border-[#e5e7eb] pt-[7px] font-bold">= Commission-যোগ্য বিক্রি</span><b class="border-t border-[#e5e7eb] pt-[7px]">{{ $val($cur['eligible']) }}</b>
                        </div>
                        <div>
                            <div class="mb-[8px] text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]">Tier ladder</div>
                            <div class="flex flex-col gap-[6px]">
                                @foreach ($tiers as $i => $t)
                                    <div @class(['flex justify-between rounded-[8px] px-[12px] py-[8px] text-[13px]', 'bg-[#4f46e5] text-white' => $i === $cur['tier'], 'bg-[#f9fafb] text-[#374151]' => $i !== $cur['tier']])>
                                        <span>{{ $val($t['from']) }}{{ $t['to'] === null ? '+' : ' – ' . $val($t['to']) }}</span><b>{{ $rate($t['rate']) }}</b>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-[10px]">
                            <div class="rounded-[10px] bg-[#eef2ff] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#3730a3]">এখন পর্যন্ত</div><div class="mt-[2px] text-[18px] font-bold text-[#312e81]">{{ $money($cur['amount']) }}</div></div>
                            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#6b7280]">বছর শেষে (আনুমানিক)</div><div class="mt-[2px] text-[18px] font-bold">{{ $money($cur['projectedShown']) }}</div></div>
                        </div>
                        @if ($cur['hold'])
                            <div class="rounded-[10px] border border-[#fecaca] bg-[#fef2f2] px-[12px] py-[10px] text-[13px] leading-[1.5] text-[#7f1d1d]">{{ $cur['hold'] }}</div>
                        @endif
                        <div>
                            <div class="mb-[6px] text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]">বাদ দেওয়া invoice</div>
                            @forelse ($exOut->where('profile_id', $cur['profile_id']) as $e)
                                <div class="flex justify-between gap-[8px] border-t border-[#f0f1f4] py-[7px] text-[13px]"><div><b>{{ $e['ref'] }}</b> <span class="text-[#6b7280]">· {{ $e['what'] }}</span></div><span>{{ $val($e['amount']) }}</span></div>
                            @empty
                                <div class="text-[13px] text-[#6b7280]">কিছু বাদ যায়নি।</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-end gap-[8px] border-t border-[#f0f1f4] bg-[#fcfcfd] px-[18px] py-[14px]">
                        <button type="button" wire:click="sendStatement({{ $cur['profile_id'] }})" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Send statement (SMS)</button>
                        <a href="{{ route('accounts.profiles.show', $cur['profile_id']) }}" wire:navigate class="rounded-[8px] bg-[#4f46e5] px-[14px] py-[9px] text-[13px] font-semibold !text-white">Open profile</a>
                    </div>
                </aside>
            @endif
        </div>
    @endif

    {{-- ============ SCHEMES ============ --}}
    @if ($tab === 'schemes')
        @if ($this->schemes->isNotEmpty())
            <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(300px,1fr))]">
                @foreach ($this->schemes as $o)
                    @php $ot = Engine::tiersOf($o); @endphp
                    <button type="button" wire:click="editScheme({{ $o->id }})" wire:key="sc-{{ $o->id }}" @class([
                        'rounded-[14px] bg-white px-[16px] py-[14px] text-left text-[#111827]',
                        'border-2 border-[#4f46e5]' => ($edit['id'] ?? null) === $o->id, 'border border-[#e5e7eb]' => ($edit['id'] ?? null) !== $o->id,
                    ])>
                        <div class="flex items-center justify-between gap-[8px]"><span class="text-[15px] font-bold">{{ $o->name }}</span>
                            <span @class(['whitespace-nowrap rounded-[6px] px-[8px] py-[2px] text-[11.5px] font-semibold', 'bg-[#d1fae5] text-[#065f46]' => $o->status === 'active', 'bg-[#f3f4f6] text-[#374151]' => $o->status !== 'active'])>{{ ucfirst($o->status) }}</span></div>
                        <div class="mt-[4px] text-[12.5px] text-[#6b7280]">{{ Engine::PERIODS[$o->period] ?? $o->period }} · {{ strtolower(Engine::BASES[$o->basis] ?? $o->basis) }} · {{ collect($ot)->pluck('rate')->map(fn ($x) => rtrim(rtrim(number_format($x, 2), '0'), '.'))->implode(' / ') }}{{ $o->basis === 'quantity' ? ' ৳/pc' : '%' }} tiers</div>
                        <div class="mt-[10px] flex gap-[14px] text-[12.5px]"><span><b>{{ Engine::WHO[$o->who] ?? $o->who }}</b></span><span>{{ $o->tier_mode === 'whole' ? 'Whole amount' : 'Slab' }}</span></div>
                    </button>
                @endforeach
            </div>
        @endif

        <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[20px] py-[16px]">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-[8px]"><span class="text-[17px] font-bold">Scheme:</span>
                        <input type="text" wire:model.blur="edit.name" placeholder="যেমন: Wholesale yearly rebate" class="min-w-[240px] flex-1 rounded-[8px] border border-[#e5e7eb] px-[10px] py-[6px] text-[16px] font-bold"></div>
                    @error('edit.name')<span class="{{ $err }}">{{ $message }}</span>@enderror
                    <div class="mt-[2px] text-[12.5px] text-[#6b7280]">নিয়ম বদলালে Customer progress সাথে সাথে নতুন হিসাব দেখাবে</div>
                </div>
                <span @class(['rounded-[6px] px-[9px] py-[3px] text-[12px] font-semibold', 'bg-[#d1fae5] text-[#065f46]' => ($edit['status'] ?? '') === 'active', 'bg-[#f3f4f6] text-[#374151]' => ($edit['status'] ?? '') !== 'active'])>
                    {{ $edit['id'] ? ucfirst($edit['status']) . ($calc ? ' · ' . \App\Support\AccountsPeriod::fyLabel($calc['start']) : '') : 'New scheme' }}</span>
            </div>
            <div class="flex flex-wrap">
                <div class="flex min-w-0 flex-[1_1_460px] flex-col gap-[16px] border-r border-[#f0f1f4] px-[20px] py-[18px]">
                    <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(170px,1fr))]">
                        <div class="flex flex-col gap-[6px]"><label for="sc-type" class="text-[13px] font-semibold">Type</label><select id="sc-type" wire:model.live="edit.type" class="{{ $field }}">@foreach (Engine::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="flex flex-col gap-[6px]"><label for="sc-basis" class="text-[13px] font-semibold">কিসের উপর</label><select id="sc-basis" wire:model.live="edit.basis" class="{{ $field }}">@foreach (Engine::BASES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="flex flex-col gap-[6px]"><label for="sc-period" class="text-[13px] font-semibold">Period</label><select id="sc-period" wire:model.live="edit.period" class="{{ $field }}">@foreach (Engine::PERIODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="flex flex-col gap-[6px]"><label for="sc-who" class="text-[13px] font-semibold">কারা পাবে</label><select id="sc-who" wire:model.live="edit.who" class="{{ $field }}">@foreach (Engine::WHO as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    </div>
                    @if ($edit['period'] === 'custom')
                        <div class="grid grid-cols-2 gap-[12px]">
                            <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">From</label><input type="date" wire:model.live="edit.starts_on" class="{{ $field }}">@error('edit.starts_on')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                            <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">To</label><input type="date" wire:model.live="edit.ends_on" class="{{ $field }}">@error('edit.ends_on')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                        </div>
                    @endif
                    @if ($edit['who'] === 'group')
                        <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Price group</label>
                            <select multiple wire:model.live="edit.who_value" class="{{ $field }} h-[96px]">@foreach ($this->groups as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
                    @elseif ($edit['who'] === 'tag')
                        <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Profile tag</label><input type="text" wire:model.blur="edit.who_value.0" placeholder="যেমন: Agent" class="{{ $field }}"></div>
                    @elseif ($edit['who'] === 'selected')
                        <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Customers <span class="font-normal text-[#6b7280]">(Ctrl / ⌘ দিয়ে একাধিক)</span></label>
                            <select multiple wire:model.live="edit.who_value" class="{{ $field }} h-[140px]">@foreach ($this->customerOptions as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
                    @endif

                    <div class="flex flex-col gap-[8px]">
                        <span class="text-[13px] font-semibold">Rate কিভাবে বসবে</span>
                        @php $t1 = $tiers[1] ?? null; $t2 = $tiers[2] ?? null; @endphp
                        <div role="group" aria-label="Tier mode" class="flex flex-wrap gap-[8px]">
                            @foreach (['whole' => ['পুরো amount-এ', 'যে tier-এ পৌঁছাবে, পুরো বিক্রিতে সেই rate'], 'slab' => ['Slab অনুযায়ী', 'প্রতিটা ধাপে নিজের rate']] as $k => [$l, $sub])
                                <button type="button" wire:click="$set('edit.tier_mode', '{{ $k }}')" @class([
                                    'flex-[1_1_200px] rounded-[10px] px-[14px] py-[10px] text-left',
                                    'border-2 border-[#4f46e5] bg-[#eef2ff] text-[#312e81]' => $edit['tier_mode'] === $k, 'border border-[#e5e7eb] bg-white text-[#374151]' => $edit['tier_mode'] !== $k,
                                ])><span class="block font-bold">{{ $l }}</span><span class="mt-[2px] block text-[11.5px] opacity-85">{{ $sub }}</span></button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="mb-[6px] text-[13px] font-semibold">Tiers</div>
                        <table class="border border-[#e5e7eb] text-[13px]">
                            <thead><tr class="bg-[#f9fafb] text-left text-[11.5px] uppercase text-[#6b7280]"><th class="px-[12px] py-[8px] font-semibold">From {{ $edit['basis'] === 'quantity' ? '(pcs)' : '(৳)' }}</th><th class="px-[12px] py-[8px] font-semibold">To</th><th class="px-[12px] py-[8px] font-semibold">Rate</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($edit['tiers'] as $i => $t)
                                    @php $lastTier = $i === count($edit['tiers']) - 1; @endphp
                                    <tr class="border-t border-[#f0f1f4]" wire:key="tier-{{ $i }}">
                                        <td class="px-[12px] py-[6px]"><input type="number" min="0" wire:model.live.debounce.500ms="edit.tiers.{{ $i }}.from" aria-label="Tier {{ $i + 1 }} from" class="w-[110px] rounded-[7px] border border-[#d1d5db] px-[8px] py-[6px] text-[13px]"></td>
                                        <td class="px-[12px] py-[6px]">@if ($lastTier)<span class="text-[#6b7280]">and above</span>@else<input type="number" min="0" wire:model.live.debounce.500ms="edit.tiers.{{ $i }}.to" aria-label="Tier {{ $i + 1 }} to" class="w-[110px] rounded-[7px] border border-[#d1d5db] px-[8px] py-[6px] text-[13px]">@endif</td>
                                        <td class="whitespace-nowrap px-[12px] py-[6px]"><input type="number" min="0" step="0.1" wire:model.live.debounce.500ms="edit.tiers.{{ $i }}.rate" aria-label="Tier {{ $i + 1 }} rate" class="w-[64px] rounded-[7px] border border-[#d1d5db] px-[8px] py-[6px] text-[13px]"> {{ $edit['basis'] === 'quantity' ? '৳/pc' : '%' }}</td>
                                        <td class="px-[6px]">@if (count($edit['tiers']) > 1)<button type="button" wire:click="removeTier({{ $i }})" class="text-[#9ca3af]" aria-label="Remove tier">×</button>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @error('edit.tiers')<span class="{{ $err }}">{{ $message }}</span>@enderror
                        <button type="button" wire:click="addTier" class="mt-[8px] rounded-[8px] border border-dashed border-[#c7d2fe] bg-white px-[12px] py-[6px] text-[12.5px] font-semibold text-[#4338ca]">+ Add tier</button>
                    </div>
                </div>

                <div class="flex min-w-0 flex-[1_1_400px] flex-col gap-[16px] bg-[#fcfcfd] px-[20px] py-[18px]">
                    <div>
                        <div class="mb-[4px] text-[13px] font-semibold">কোন বিক্রি বাদ যাবে · Exclusions</div>
                        <div class="mb-[8px] text-[12px] text-[#6b7280]">কম দামে দেওয়া বা special deal-এর invoice/item commission পাবে না</div>
                        @php $impact = $this->exList->where('back', false)->groupBy('rule')->map->sum('amount'); @endphp
                        @foreach (Scheme::RULES as $k => [$title, $sub, $default])
                            <div class="flex items-center justify-between gap-[10px] border-t border-[#f0f1f4] py-[9px]">
                                <label class="flex flex-1 cursor-pointer items-start gap-[10px] text-[13px]">
                                    <input type="checkbox" wire:model.live="edit.rules.{{ $k }}.on" class="mt-[2px] accent-[#4f46e5]">
                                    <span><span class="block font-semibold">
                                        @if ($default !== null)
                                            {!! str_replace('%s', '<input type="number" min="0" step="0.5" wire:model.live.debounce.500ms="edit.rules.' . $k . '.value" class="mx-[2px] w-[54px] rounded-[6px] border border-[#d1d5db] px-[4px] py-[1px] text-[13px]">', e(str_replace('%%', '%', $title))) !!}
                                        @else
                                            {{ $title }}
                                        @endif
                                    </span><span class="block text-[12px] text-[#6b7280]">{{ $sub }}</span></span>
                                </label>
                                <span class="whitespace-nowrap text-[12px] text-[#b45309]">{{ $val($impact[$k] ?? 0) }} বাদ</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-col gap-[8px]">
                        <span class="text-[13px] font-semibold">শর্ত · Eligibility</span>
                        <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model.live="edit.paid_only" class="accent-[#4f46e5]"> শুধু পুরো টাকা পরিশোধ হওয়া invoice গণনা হবে</label>
                        <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model.live="edit.hold_on" class="accent-[#4f46e5]"> <input type="number" min="1" wire:model.live.debounce.500ms="edit.hold_days" class="w-[54px] rounded-[6px] border border-[#d1d5db] px-[4px] py-[1px] text-[13px]"> দিনের বেশি বাকি থাকলে commission hold</label>
                        <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model.live="edit.returns_reverse" class="accent-[#4f46e5]"> Return হলে commission-ও কাটা যাবে</label>
                    </div>
                    <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(160px,1fr))]">
                        <div class="flex flex-col gap-[6px]"><label for="sc-settle" class="text-[13px] font-semibold">কিভাবে দেবো</label><select id="sc-settle" wire:model="edit.settlement_method" class="{{ $field }}">@foreach (Engine::METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="flex flex-col gap-[6px]"><label for="sc-acct" class="text-[13px] font-semibold">হিসাবে কোথায় যাবে</label><select id="sc-acct" wire:model="edit.accounting" class="{{ $field }}">@foreach (Engine::ACCOUNTING as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    </div>
                    <div class="rounded-[10px] border border-[#c7d2fe] bg-[#eef2ff] px-[12px] py-[10px] text-[12.5px] leading-[1.5] text-[#312e81]">প্রতি মাস শেষে accrual entry Posting Pending-এ যাবে (Dr {{ $edit['accounting'] === 'expense' ? 'Commission expense' : 'Sales rebate' }} / Cr Rebate payable)। বছর শেষে settlement-এ customer-এর নামে credit note বা payment হবে।</div>
                    <div class="flex flex-wrap justify-end gap-[8px]">
                        @if ($edit['id'] && ($edit['status'] ?? '') === 'active')
                            <button type="button" wire:click="endScheme" wire:confirm="এই scheme বন্ধ করবেন? আর accrual হবে না।" class="mr-auto rounded-[8px] border border-[#fecaca] bg-white px-[14px] py-[9px] text-[13px] text-[#b91c1c]">End scheme</button>
                        @endif
                        <button type="button" wire:click="saveScheme(false)" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Save as draft</button>
                        <button type="button" wire:click="saveScheme(true)" class="rounded-[8px] border-0 bg-[#4f46e5] px-[18px] py-[9px] text-[13px] font-semibold text-white">Save scheme</button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ EXCLUSIONS ============ --}}
    @if ($tab === 'exclusions' && $s)
        <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <div><h2 class="m-0 text-[15px] font-bold">বাদ দেওয়া invoice ও item</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">Rule অনুযায়ী নিজে থেকে বাদ, বা হাতে বাদ দেওয়া · দরকার হলে আবার যোগ করা যায়</div></div>
                <button type="button" wire:click="$set('showExclude', true)" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[8px] text-[13px] text-[#374151]">+ Exclude invoice</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[860px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Invoice</th>
                            <th class="px-[8px] py-[10px] font-semibold">Customer</th>
                            <th class="px-[8px] py-[10px] font-semibold">What</th>
                            <th class="px-[8px] py-[10px] font-semibold">Why excluded</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Amount</th>
                            <th class="px-[18px] py-[10px] text-right"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->exList as $i => $e)
                            <tr wire:key="ex-{{ $i }}-{{ $e['order_id'] }}-{{ $e['item_id'] }}" @class(['border-t border-[#f0f1f4]', 'opacity-55' => ! $e['out']])>
                                <td class="whitespace-nowrap py-[11px] pl-[18px] pr-[8px]"><b class="text-[#4338ca]">{{ $e['ref'] }}</b><div class="text-[11.5px] text-[#6b7280]">{{ $e['date'] ? \Carbon\CarbonImmutable::parse($e['date'])->format('d M') : '' }}</div></td>
                                <td class="px-[8px] py-[11px] font-semibold">{{ $e['name'] }}</td>
                                <td class="px-[8px] py-[11px] text-[#374151]">{{ $e['what'] }}</td>
                                <td class="px-[8px] py-[11px]">
                                    <span @class(['rounded-[6px] px-[8px] py-[2px] text-[11px] font-semibold', 'bg-[#e0f2fe] text-[#075985]' => $e['src'] === 'Auto', 'bg-[#fef3c7] text-[#92400e]' => $e['src'] !== 'Auto'])>{{ $e['src'] }}</span>
                                    <div class="mt-[3px] text-[11.5px] text-[#6b7280]">{{ ! $e['out'] && ! $e['back'] ? 'Rule বন্ধ — গণনায় আছে' : $e['reason'] }}</div>
                                </td>
                                <td @class(['whitespace-nowrap px-[8px] py-[11px] text-right font-bold', 'text-[#b45309]' => $e['out'], 'text-[#6b7280] line-through' => ! $e['out']])>{{ $e['amount'] !== null ? $val($e['amount']) : '—' }}</td>
                                <td class="px-[18px] py-[11px] text-right">
                                    @if ($e['out'])
                                        <button type="button" wire:click="askReason('back', {{ $e['order_id'] }}, {{ $e['item_id'] ?? 'null' }})" class="rounded-[8px] border border-[#c7d2fe] bg-white px-[12px] py-[6px] text-[12.5px] text-[#4338ca]">Include back</button>
                                    @else
                                        <button type="button" wire:click="askReason('out', {{ $e['order_id'] }}, {{ $e['item_id'] ?? 'null' }})" class="rounded-[8px] border border-[#fde68a] bg-white px-[12px] py-[6px] text-[12.5px] text-[#92400e]">Exclude</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#f0f1f4]"><td colspan="6" class="px-[18px] py-[30px] text-center text-[#6b7280]">কিছু বাদ যায়নি।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-[18px] pb-[14px] pt-[10px] text-[12px] text-[#6b7280]">আবার যোগ করলে কারণ লিখতে হবে, history-তে থাকবে।</div>
        </section>
    @endif

    {{-- ============ SETTLEMENT ============ --}}
    @if ($tab === 'settlement' && $s && $calc)
        @php $ended = $this->periodEnded(); $left = $calc ? max(0, (int) today()->diffInDays($calc['end'])) : 0; @endphp
        <div class="flex flex-wrap items-start gap-[16px]">
            <section class="min-w-0 flex-[999_1_600px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
                <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                    <div><h2 class="m-0 text-[15px] font-bold">{{ $ended ? 'Settlement' : 'বছরশেষের settlement · preview' }}</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">{{ $calc['end']->format('d M Y') }}{{ $ended ? ' · period শেষ' : ' · আর ' . $left . ' দিন · এখনকার হিসাবে' }}</div></div>
                    @if ($ended)
                        <button type="button" wire:click="runSettlement" wire:confirm="সব customer-এর settlement draft তৈরি করবেন?" class="rounded-[8px] border-0 bg-[#4f46e5] px-[14px] py-[9px] text-[13px] font-semibold text-white">Run settlement</button>
                    @else
                        <button type="button" disabled class="cursor-not-allowed rounded-[8px] border-0 bg-[#c7d2fe] px-[14px] py-[9px] text-[13px] font-semibold text-white">Run settlement</button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[640px] text-[13px]">
                        <thead><tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]"><th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Customer</th><th class="px-[8px] py-[10px] font-semibold">Tier</th><th class="px-[8px] py-[10px] text-right font-semibold">Commission</th><th class="px-[8px] py-[10px] font-semibold">দেওয়ার উপায়</th><th class="px-[18px] py-[10px] font-semibold">Note</th></tr></thead>
                        <tbody>
                            @forelse ($this->settleRows as $r)
                                @php $ti = $ended ? $r['tier'] : $r['projTier']; @endphp
                                <tr class="border-t border-[#f0f1f4]" wire:key="st-{{ $r['profile_id'] }}">
                                    <td class="py-[11px] pl-[18px] pr-[8px] font-semibold">{{ $r['name'] }}</td>
                                    <td class="px-[8px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $tc($ti) }}">{{ $rate($tiers[$ti]['rate'] ?? 0) }}</span></td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold">{{ $money($ended ? $r['amount'] : $r['projectedShown']) }}</td>
                                    <td class="px-[8px] py-[11px] text-[#374151]">{{ \Illuminate\Support\Str::before(Engine::METHODS[$s->settlement_method] ?? '', ' (') }}</td>
                                    <td @class(['px-[18px] py-[11px] text-[12px]', 'font-semibold text-[#b91c1c]' => $r['hold'], 'text-[#6b7280]' => ! $r['hold']])>
                                        {{ $r['hold'] ? 'Hold — বাকি পরিশোধ হলে' : ($r['settled'] > 0 ? $money($r['settled']) . ' paid already' : ($ended ? 'Final' : 'Projected')) }}
                                        @unless ($r['hold'])<a href="#" wire:click.prevent="openAdvance({{ $r['profile_id'] }})" class="ml-[6px]">Advance</a>@endunless
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#f0f1f4]"><td colspan="5" class="px-[18px] py-[24px] text-center text-[#6b7280]">এখনো কেউ commission tier-এ পৌঁছায়নি।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-[8px] border-t border-[#f0f1f4] px-[18px] pb-[16px] pt-[12px]">
                    <span class="text-[12.5px] text-[#6b7280]">মাঝ বছরে আগাম দিতে চাইলে · advance settlement</span>
                    <button type="button" wire:click="openAdvance" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[8px] text-[13px] text-[#374151]">Pay advance commission</button>
                </div>
            </section>

            <aside class="min-w-0 flex-[1_1_340px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
                <div class="border-b border-[#f0f1f4] px-[18px] py-[14px]"><h2 class="m-0 text-[15px] font-bold">গত বছর · {{ $sum['lastLabel'] }}</h2></div>
                <table class="text-[13px]"><tbody>
                    @forelse ($this->history as $h)
                        <tr class="border-t border-[#f0f1f4] first:border-t-0">
                            <td class="py-[10px] pl-[18px] pr-[8px]"><div class="font-semibold">{{ $h->profile?->display_name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ \Illuminate\Support\Str::before(Engine::METHODS[$h->method] ?? $h->method, ' (') }} · {{ $h->journalEntry?->ref() }}{{ (float) $h->rate ? ' · ' . $rate((float) $h->rate) : '' }}{{ $h->advance ? ' · advance' : '' }}</div></td>
                            <td class="px-[18px] py-[10px] text-right font-bold">{{ $money($h->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-[18px] py-[18px] text-center text-[#6b7280]">আগের কোনো settlement নেই।</td></tr>
                    @endforelse
                    @if ($this->history->isNotEmpty())
                        <tr class="border-t-2 border-[#e5e7eb] bg-[#f9fafb] font-bold"><td class="py-[10px] pl-[18px] pr-[8px]">Total</td><td class="px-[18px] py-[10px] text-right">{{ $money($this->history->sum('amount')) }}</td></tr>
                    @endif
                </tbody></table>
            </aside>
        </div>
    @endif
    @endif

    {{-- Reason modal --}}
    @if ($reasonFor)
        <x-accounts.modal :title="$reasonFor['action'] === 'back' ? 'Include back' : 'Exclude'" close="$set('reasonFor', null)">
            <div class="text-[13px] text-[#374151]">INV-{{ str_pad((string) $reasonFor['order_id'], 6, '0', STR_PAD_LEFT) }} · কারণ লিখুন — history-তে থাকবে।</div>
            <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Reason</label><input type="text" wire:model="reason" class="{{ $input }}" placeholder="{{ $reasonFor['action'] === 'back' ? 'যেমন: normal price-এ বিক্রি হয়েছে' : 'যেমন: already special price' }}">@error('reason')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('reasonFor', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveReason" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @if ($showExclude)
        <x-accounts.modal title="Exclude invoice" close="$set('showExclude', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Invoice</label><input type="text" wire:model="exclude.ref" placeholder="INV-001176" class="{{ $input }}">@error('exclude.ref')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Reason</label><input type="text" wire:model="exclude.reason" placeholder="যেমন: Eid special deal price" class="{{ $input }}">@error('exclude.reason')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showExclude', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveExclude" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Exclude</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @if ($showAdvance)
        <x-accounts.modal title="Pay advance commission" close="$set('showAdvance', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Customer</label>
                    <select wire:model="advance.profile_id" class="{{ $input }}"><option value="">— Select —</option>@foreach ($rows as $r)<option value="{{ $r['profile_id'] }}">{{ $r['name'] }} · accrued {{ $money($r['accrue']) }}</option>@endforeach</select>@error('advance.profile_id')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Amount (৳)</label><input type="number" min="0" wire:model="advance.amount" class="{{ $input }}">@error('advance.amount')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">কিভাবে</label><select wire:model.live="advance.method" class="{{ $input }}"><option value="credit_note">Credit note</option><option value="deduct_due">বাকি থেকে কাটবো</option><option value="cash">Cash / bKash</option></select></div>
                @if (($advance['method'] ?? '') === 'cash')
                    <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Pay from</label><select wire:model="advance.account_id" class="{{ $input }}">@foreach (app(\App\Services\Profiles\LedgerAccounts::class)->cashAccounts() as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select>@error('advance.account_id')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                @endif
            </div>
            <div class="text-[12.5px] text-[#6b7280]">Draft হবে: Dr Rebate payable / Cr {{ ($advance['method'] ?? '') === 'cash' ? 'cash' : (($advance['method'] ?? '') === 'deduct_due' ? 'customer receivable' : 'customer credit') }}।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showAdvance', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveAdvance" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Create draft</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @livewire('accounts.voucher-form')
</div>
