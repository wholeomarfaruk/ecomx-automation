{{-- Customers — design: Customers.dc.html --}}
@php
    $s = $this->summary;
    $rows = $this->rows;
    $counts = $this->chipCounts;
    $select = 'text-[13px] pl-[10px] pr-[30px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white text-[#111827]';
    $btn = 'text-[13px] px-[14px] py-[9px] border border-[#e5e7eb] rounded-[8px] bg-white text-[#374151]';
@endphp

<div x-data="{ sel: [] }" class="box-border flex w-full max-w-[1400px] flex-col gap-[18px] px-[28px] pb-[40px] pt-[24px]">

    <x-accounts.page-head title="Customers" subtitle="বাকি, credit limit আর কিস্তি — কার কাছে কত পাবো">
        <button type="button" class="{{ $btn }}" wire:loading.attr="disabled" wire:target="sendReminders"
                @click="if (confirm(sel.length ? `Send a due reminder SMS to ${sel.length} selected customer(s)?` : 'Send a due reminder SMS to every customer with due?')) $wire.sendReminders(sel)">Due reminder SMS</button>
        <button type="button" class="{{ $btn }}" wire:click="openVoucher('collect')">টাকা গ্রহণ · Collect</button>
        <a href="{{ route('accounts.profiles.create', ['role' => 'customer']) }}" wire:navigate class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">+ New customer</a>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="মোট Customer" :value="number_format($s['count'])"
                         :sub="number_format($s['due_count']) . ' with due · ' . number_format($s['kisti_count']) . ' on kisti'" />
        <x-accounts.stat label="মোট বাকি · পাবো" :value="$money($s['due'])" color="#047857" :sub="'Collected this month ' . $money($s['collected'])" />
        <x-accounts.stat label="মেয়াদোত্তীর্ণ · 60+ দিন" :value="$money($s['over60'])" color="#b45309" :sub="number_format($s['over60_count']) . ' customers'" />
        <x-accounts.stat label="Credit limit পার" :value="number_format($s['over_limit'])" color="#b91c1c" sub="নতুন বাকি বিক্রি বন্ধ" />
        <x-accounts.stat label="অগ্রিম নেওয়া · Advance" :value="$money($s['advance'])" color="#4338ca" sub="Adjusts on next order" />
    </div>

    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex flex-col gap-[12px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <div class="flex flex-wrap items-center gap-[10px]">
                <label for="cust-search" class="sr-only">Search customers</label>
                <div class="flex flex-[1_1_260px] items-center gap-[8px] rounded-[9px] border border-[#d1d5db] bg-white px-[12px]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <input id="cust-search" type="search" wire:model.live.debounce.400ms="q" placeholder="নাম, phone বা code"
                           class="flex-1 border-0 bg-transparent px-0 py-[10px] text-[13.5px] outline-none focus:ring-0">
                </div>
                <label for="cust-district" class="text-[13px] text-[#4b5563]">District</label>
                <select id="cust-district" wire:model.live="district" class="{{ $select }}">
                    <option value="">All districts</option>
                    @foreach ($this->districts as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <label for="cust-sort" class="text-[13px] text-[#4b5563]">Sort</label>
                <select id="cust-sort" wire:model.live="sort" class="{{ $select }}">
                    @foreach ($this::SORTS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-[6px] text-[13px] text-[#374151]"><input type="checkbox" wire:model.live="kisti" class="rounded"> Kisti plan আছে</label>
            </div>
            <div role="group" aria-label="Due status" class="flex flex-wrap gap-[6px]">
                @foreach ($this::CHIPS as $key => $label)
                    <x-accounts.chip :on="$st === $key" :count="number_format($counts[$key])" wire:click="setSt('{{ $key }}')">{{ $label }}</x-accounts.chip>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="q,district,sort,kisti,setSt,nextPage,previousPage">
            <table class="min-w-[1040px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="w-[28px] py-[10px] pl-[18px] pr-0">
                            <input type="checkbox" aria-label="Select all" class="rounded"
                                   @change="sel = $event.target.checked ? @js($rows->pluck('id')->map(fn ($id) => (string) $id)->values()) : []">
                        </th>
                        <th class="px-[8px] py-[10px] font-semibold">Customer</th>
                        <th class="px-[8px] py-[10px] font-semibold">Area</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Purchase</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Due</th>
                        <th class="w-[150px] px-[8px] py-[10px] font-semibold">Credit limit</th>
                        <th class="px-[8px] py-[10px] font-semibold">Kisti</th>
                        <th class="px-[8px] py-[10px] font-semibold">Last payment</th>
                        <th class="px-[18px] py-[10px] text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $p)
                        @php $r = $this->present($p); @endphp
                        <tr wire:key="c-{{ $p->id }}" class="border-t border-[#f0f1f4]">
                            <td class="py-[11px] pl-[18px] pr-0"><input type="checkbox" aria-label="Select customer" value="{{ $p->id }}" x-model="sel" class="rounded"></td>
                            <td class="px-[8px] py-[11px]"><div class="font-semibold">{{ $p->display_name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $p->code }} · {{ $p->phone ?: '—' }}</div></td>
                            <td class="px-[8px] py-[11px] text-[#374151]">{{ $p->area() }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right"><div>{{ $r['total'] }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $r['orders'] }} orders</div></td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right">
                                <div class="font-bold" style="color: {{ $r['dueColor'] }}">{{ $r['due'] }}</div>
                                <div @class(['text-[11.5px]', 'font-semibold text-[#b45309]' => $r['st'] === 'overdue', 'text-[#6b7280]' => $r['st'] !== 'overdue'])>{{ $r['age'] }}</div>
                            </td>
                            <td class="px-[8px] py-[11px]">
                                <div class="mb-[4px] flex justify-between text-[11.5px] text-[#4b5563]"><span>{{ $r['limit'] }}</span><span @class(['font-bold text-[#b91c1c]' => $r['pct'] >= 100, 'text-[#4b5563]' => $r['pct'] < 100])>{{ $r['pctLabel'] }}</span></div>
                                <div class="h-[6px] overflow-hidden rounded-[4px] bg-[#eef0f3]"><div class="h-full rounded-[4px]" style="background: {{ $r['bar'] }}; width: {{ min($r['pct'], 100) }}%"></div></div>
                            </td>
                            <td class="px-[8px] py-[11px]">
                                @if ($r['kisti'] === '—')
                                    <span class="text-[#9ca3af]">—</span>
                                @else
                                    <span class="whitespace-nowrap rounded-[6px] bg-[#eef2ff] px-[8px] py-[3px] text-[12px] font-semibold text-[#3730a3]">{{ $r['kisti'] }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-[8px] py-[11px]"><div>{{ $r['lastAmt'] }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $r['lastDate'] }}</div></td>
                            <td class="whitespace-nowrap px-[18px] py-[11px] text-right">
                                <a href="#" wire:click.prevent="openVoucher('collect', {{ $p->id }})" class="font-semibold">Collect</a><span class="text-[#d1d5db]"> · </span><a href="#" wire:click.prevent="sendReminder({{ $p->id }})">SMS</a><span class="text-[#d1d5db]"> · </span><a href="{{ route('accounts.profiles.show', $p) }}" wire:navigate>View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($rows->isEmpty())
            <div class="px-[18px] py-[36px] text-center text-[#6b7280]">এই filter-এ কোনো customer নেই।</div>
        @endif
        @include('livewire.accounts.profiles.partials.pager', ['rows' => $rows])
    </section>

    @include('livewire.accounts.profiles.partials.voucher-modal')
</div>
