{{-- Master Profiles — design: Profiles.dc.html --}}
@php
    $s = $this->summary;
    $rows = $this->rows;
    $select = 'text-[13px] pl-[10px] pr-[30px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white text-[#111827]';
    $btn = 'text-[13px] px-[14px] py-[9px] border border-[#e5e7eb] rounded-[8px] bg-white text-[#374151]';
@endphp

<div x-data="{ sel: [] }" class="acc-profiles box-border flex w-full max-w-[1400px] flex-col gap-[18px]">
    <x-accounts.profile-tabs active="profiles" />

    <x-accounts.page-head title="Master Profiles" subtitle="একজন ব্যক্তি বা প্রতিষ্ঠান = একটি profile। এক profile-এ একাধিক role।">
        <a href="{{ route('accounts.profiles.import') }}" wire:navigate class="{{ $btn }} !text-[#374151]">Import (Excel)</a>
        <button type="button" class="{{ $btn }}" @click="$wire.export(sel)" wire:loading.attr="disabled" wire:target="export">
            Export<span x-show="sel.length" x-cloak> (<span x-text="sel.length"></span>)</span>
        </button>
        <a href="{{ route('accounts.profiles.create') }}" wire:navigate class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">+ New profile</a>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]">
        <x-accounts.stat label="মোট Profile" :value="number_format($s['total'])"
                         :sub="'+' . number_format($s['this_month']) . ' this month · ' . number_format($s['employees']) . ' employees'" />
        <x-accounts.stat label="মোট পাবো · Receivable" :value="$money($s['receivable'])" color="#047857"
                         :sub="'From ' . number_format($s['receivable_cnt']) . ' profiles'" />
        <x-accounts.stat label="মোট দিবো · Payable + Loan" :value="$money($s['payable'] + $s['loan'])" color="#b91c1c"
                         :sub="'Suppliers ' . $money($s['payable']) . ' · Lenders ' . $money($s['loan'])" />
        <x-accounts.stat label="অগ্রিম · Advance" :value="$money($s['adv_given'] + $s['adv_received'])" color="#4338ca"
                         :sub="'Given ' . $money($s['adv_given']) . ' · received ' . $money($s['adv_received'])" />
    </div>

    @if ($this->duplicates > 0)
        <div class="flex flex-wrap items-center justify-between gap-[10px] rounded-[12px] border border-[#fde68a] bg-[#fffbeb] px-[16px] py-[12px] text-[#78350f]">
            <div class="flex items-center gap-[10px]">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 20h20z"></path><path d="M12 10v4"></path><path d="M12 17h.01"></path></svg>
                <span><b>{{ $this->duplicates }}টি সম্ভাব্য duplicate</b> — একই phone number দিয়ে আলাদা profile আছে।</span>
            </div>
            <a href="{{ route('accounts.profiles.merge') }}" wire:navigate class="font-semibold !text-[#92400e]">Review &amp; merge</a>
        </div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex flex-col gap-[12px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <div class="flex flex-wrap items-center gap-[10px]">
                <label for="profile-search" class="sr-only">Search profiles</label>
                <div class="flex flex-[1_1_280px] items-center gap-[8px] rounded-[9px] border border-[#d1d5db] bg-white px-[12px]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <input id="profile-search" type="search" wire:model.live.debounce.400ms="q" placeholder="নাম, phone, code বা NID দিয়ে খুঁজুন"
                           class="flex-1 border-0 bg-transparent px-0 py-[10px] text-[13.5px] outline-none focus:ring-0">
                </div>
                <label for="type-filter" class="text-[13px] text-[#4b5563]">Type</label>
                <select id="type-filter" wire:model.live="type" class="{{ $select }}">
                    <option value="">All</option><option value="individual">Individual</option><option value="business">Business</option>
                </select>
                <label for="area-filter" class="text-[13px] text-[#4b5563]">District</label>
                <select id="area-filter" wire:model.live="district" class="{{ $select }}">
                    <option value="">All districts</option>
                    @foreach ($this->districts as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <label for="status-filter" class="text-[13px] text-[#4b5563]">Status</label>
                <select id="status-filter" wire:model.live="status" class="{{ $select }}">
                    <option value="active">Active</option><option value="inactive">Inactive</option><option value="all">All</option>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-[16px]">
                <div role="group" aria-label="Role" class="flex flex-wrap items-center gap-[6px]">
                    <span class="mr-[2px] text-[12px] text-[#6b7280]">Role</span>
                    @foreach ($this->roleCounts as $label => $count)
                        <x-accounts.chip :on="$role === $label" :count="number_format($count)" wire:click="setRole('{{ $label }}')">{{ $label }}</x-accounts.chip>
                    @endforeach
                </div>
                <div role="group" aria-label="Balance" class="flex flex-wrap items-center gap-[6px]">
                    <span class="mr-[2px] text-[12px] text-[#6b7280]">Balance</span>
                    @foreach (array_keys($this::BAL_CHIPS) as $label)
                        <x-accounts.chip :on="$bal === $label" wire:click="setBal('{{ $label }}')">{{ $label }}</x-accounts.chip>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="q,type,district,status,setRole,setBal,nextPage,previousPage">
            <table class="min-w-[940px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="w-[28px] py-[10px] pl-[18px] pr-0 font-semibold">
                            <input type="checkbox" aria-label="Select all" class="rounded"
                                   @change="sel = $event.target.checked ? @js($rows->pluck('id')->map(fn ($id) => (string) $id)->values()) : []">
                        </th>
                        <th class="px-[8px] py-[10px] font-semibold">Profile</th>
                        <th class="px-[8px] py-[10px] font-semibold">Phone</th>
                        <th class="px-[8px] py-[10px] font-semibold">Roles</th>
                        <th class="px-[8px] py-[10px] font-semibold">Area</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Balance</th>
                        <th class="px-[8px] py-[10px] font-semibold">Last activity</th>
                        <th class="px-[18px] py-[10px] text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $p)
                        @php
                            $b = $this->balanceOf($p);
                            $last = $p->bal_last_at ? \Carbon\Carbon::parse($p->bal_last_at) : $p->updated_at;
                        @endphp
                        <tr wire:key="p-{{ $p->id }}" class="border-t border-[#f0f1f4]">
                            <td class="py-[11px] pl-[18px] pr-0"><input type="checkbox" aria-label="Select profile" value="{{ $p->id }}" x-model="sel" class="rounded"></td>
                            <td class="px-[8px] py-[11px]">
                                <div class="flex items-center gap-[10px]">
                                    <div class="flex h-[34px] w-[34px] flex-none items-center justify-center rounded-full bg-[#eef2ff] text-[12.5px] font-bold text-[#3730a3]">{{ $p->initials() }}</div>
                                    <div><div class="font-semibold">{{ $p->display_name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $p->code }} · {{ $p->typeLabel() }}</div></div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-[#374151]">{{ $p->phone ?: '—' }}</td>
                            <td class="px-[8px] py-[11px]">
                                <div class="flex flex-wrap gap-[4px]">
                                    @forelse ($p->roles->sortBy(fn ($r) => array_search($r->role, \App\Models\MasterProfile::ROLES)) as $r)
                                        <x-accounts.role-badge :role="$r->role" />
                                    @empty
                                        <span class="text-[#9ca3af]">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-[8px] py-[11px] text-[#374151]">{{ $p->area() }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right">
                                <div class="{{ \App\Services\Profiles\ProfileBalances::kindClass($b['kind']) }}">{{ $money($b['amount']) }}</div>
                                <div class="text-[11.5px] text-[#6b7280]">{{ $b['label'] }}</div>
                            </td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-[#6b7280]">{{ \App\Support\Bdt::relative($last) }}</td>
                            <td class="px-[18px] py-[11px] text-right"><a href="{{ route('accounts.profiles.show', $p) }}" wire:navigate class="font-semibold">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($rows->isEmpty())
            <div class="px-[18px] py-[36px] text-center text-[#6b7280]">এই filter-এ কোনো profile নেই।</div>
        @endif
        <div class="flex flex-wrap items-center justify-between gap-[10px] border-t border-[#f0f1f4] px-[18px] py-[12px] text-[13px] text-[#6b7280]">
            <span>Showing {{ $rows->total() ? number_format($rows->firstItem()) . '–' . number_format($rows->lastItem()) : '0' }} of {{ number_format($rows->total()) }}</span>
            <div class="flex gap-[6px]">
                <button type="button" wire:click="previousPage" @disabled($rows->onFirstPage())
                        class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] {{ $rows->onFirstPage() ? 'text-[#9ca3af]' : 'text-[#374151]' }}">Previous</button>
                <button type="button" wire:click="nextPage" @disabled(! $rows->hasMorePages())
                        class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] {{ $rows->hasMorePages() ? 'text-[#374151]' : 'text-[#9ca3af]' }}">Next</button>
            </div>
        </div>
    </section>
</div>
