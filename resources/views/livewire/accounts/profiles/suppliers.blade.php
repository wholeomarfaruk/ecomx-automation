{{-- Suppliers — design: Suppliers.dc.html --}}
@php
    $s = $this->summary;
    $rows = $this->rows;
    $select = 'text-[13px] pl-[10px] pr-[30px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white text-[#111827]';
    $btn = 'text-[13px] px-[14px] py-[9px] border border-[#e5e7eb] rounded-[8px] bg-white text-[#374151]';
    $balColor = ['pay' => '#b91c1c', 'overdue' => '#b91c1c', 'adv' => '#4338ca', 'clear' => '#6b7280'];
@endphp

<div x-data="{ sel: [] }" class="acc-profiles box-border flex w-full max-w-[1400px] flex-col gap-[18px]">
    <x-accounts.profile-tabs active="suppliers" />

    <x-accounts.page-head title="Suppliers" subtitle="কার কাছ থেকে মাল কিনি, কাকে কত দিবো, কবে দিবো">
        <button type="button" class="{{ $btn }}" @click="$wire.openVoucher('pay', sel.length === 1 ? Number(sel[0]) : null)">টাকা দিন · Pay</button>
        <button type="button" class="{{ $btn }}" @click="$wire.openVoucher('supplier_advance', sel.length === 1 ? Number(sel[0]) : null)">Advance দিন</button>
        <a href="{{ route('accounts.profiles.create', ['role' => 'supplier']) }}" wire:navigate class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">+ New supplier</a>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="মোট Supplier" :value="number_format($s['count'])" :sub="number_format($s['alsoCustomer']) . ' also a customer'" />
        <x-accounts.stat label="মোট দিবো · Payable" :value="$money($s['payable'])" color="#b91c1c" :sub="'To ' . number_format($s['payableCount']) . ' suppliers'" />
        <x-accounts.stat label="৭ দিনের মধ্যে দিতে হবে" :value="$money($s['week'])" color="#b45309" :sub="$money($s['overdue']) . ' already overdue'" />
        <x-accounts.stat label="অগ্রিম দেওয়া · Advance" :value="$money($s['advance'])" color="#4338ca" sub="Adjusts on next purchase" />
        <x-accounts.stat label="এই মাসে কেনা" :value="$money($s['month'])" :sub="number_format($s['monthCount']) . ' purchases'" />
    </div>

    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex flex-col gap-[12px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <div class="flex flex-wrap items-center gap-[10px]">
                <label for="sup-search" class="sr-only">Search suppliers</label>
                <div class="flex flex-[1_1_260px] items-center gap-[8px] rounded-[9px] border border-[#d1d5db] bg-white px-[12px]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <input id="sup-search" type="search" wire:model.live.debounce.300ms="q" placeholder="নাম, phone, contact person"
                           class="flex-1 border-0 bg-transparent px-0 py-[10px] text-[13.5px] outline-none focus:ring-0">
                </div>
                <label for="sup-cat" class="text-[13px] text-[#4b5563]">Category</label>
                <select id="sup-cat" wire:model.live="category" class="{{ $select }}">
                    <option value="">All categories</option>
                    @foreach ($this::CATEGORIES as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
                <label for="sup-terms" class="text-[13px] text-[#4b5563]">Terms</label>
                <select id="sup-terms" wire:model.live="terms" class="{{ $select }}">
                    <option value="">All terms</option>
                    @foreach ($this::TERMS as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div role="group" aria-label="Payment status" class="flex flex-wrap gap-[6px]">
                @foreach ($this::CHIPS as $key => $label)
                    <x-accounts.chip :on="$st === $key" :count="number_format($s['chips'][$key])" wire:click="setSt('{{ $key }}')">{{ $label }}</x-accounts.chip>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1000px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="w-[28px] py-[10px] pl-[18px] pr-0">
                            <input type="checkbox" aria-label="Select all" class="rounded"
                                   @change="sel = $event.target.checked ? @js($rows->pluck('profile.id')->map(fn ($id) => (string) $id)->values()) : []">
                        </th>
                        <th class="px-[8px] py-[10px] font-semibold">Supplier</th>
                        <th class="px-[8px] py-[10px] font-semibold">Category</th>
                        <th class="px-[8px] py-[10px] font-semibold">Terms</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Purchase ({{ now()->year }})</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Balance</th>
                        <th class="px-[8px] py-[10px] font-semibold">Next due</th>
                        <th class="px-[8px] py-[10px] font-semibold">Last purchase</th>
                        <th class="px-[18px] py-[10px] text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php $p = $r['profile']; @endphp
                        <tr wire:key="s-{{ $p->id }}" class="border-t border-[#f0f1f4]">
                            <td class="py-[11px] pl-[18px] pr-0"><input type="checkbox" aria-label="Select supplier" value="{{ $p->id }}" x-model="sel" class="rounded"></td>
                            <td class="px-[8px] py-[11px]">
                                <div class="flex flex-wrap items-center gap-[6px]"><span class="font-semibold">{{ $p->display_name }}</span>
                                    @if ($r['alsoCustomer'])
                                        <span class="rounded-[6px] bg-[#d1fae5] px-[7px] py-[1px] text-[11px] font-semibold text-[#065f46]">Also customer</span>
                                    @endif
                                </div>
                                <div class="text-[11.5px] text-[#6b7280]">{{ $p->code }} · {{ $p->contact_person ?: ($p->isBusiness() ? '—' : 'Self') }}</div>
                            </td>
                            <td class="px-[8px] py-[11px] text-[#374151]">{{ $r['category'] }}</td>
                            <td class="px-[8px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] bg-[#f3f4f6] px-[8px] py-[3px] text-[12px] text-[#374151]">{{ $r['terms'] }}</span></td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right">{{ $money($r['purchase']) }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right">
                                <div class="font-bold" style="color: {{ $balColor[$r['st']] }}">{{ $money($r['balance']) }}</div>
                                <div class="text-[11.5px] text-[#6b7280]">{{ $r['balLabel'] }}</div>
                            </td>
                            <td class="whitespace-nowrap px-[8px] py-[11px]">
                                <div @class(['font-bold text-[#b91c1c]' => $r['st'] === 'overdue', 'font-semibold text-[#111827]' => $r['st'] !== 'overdue'])>{{ $r['due'] }}</div>
                                <div class="text-[11.5px] text-[#6b7280]">{{ $r['dueNote'] }}</div>
                            </td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-[#6b7280]">{{ $r['last'] }}</td>
                            <td class="whitespace-nowrap px-[18px] py-[11px] text-right">
                                <a href="#" wire:click.prevent="openVoucher('pay', {{ $p->id }})" class="font-semibold">Pay</a><span class="text-[#d1d5db]"> · </span><a href="{{ route('accounts.profiles.show', $p) }}" wire:navigate>View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($rows->isEmpty())
            <div class="px-[18px] py-[36px] text-center text-[#6b7280]">এই filter-এ কোনো supplier নেই।</div>
        @endif
        @include('livewire.accounts.profiles.partials.pager', ['rows' => $rows])
    </section>

    @include('livewire.accounts.profiles.partials.voucher-modal')
</div>
