{{-- Employees — design: Employees.dc.html --}}
@php
    $s = $this->summary;
    $rows = $this->rows;
    $b = $s['banner'];
    $select = 'text-[13px] pl-[10px] pr-[30px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white text-[#111827]';
    $btn = 'text-[13px] px-[14px] py-[9px] border border-[#e5e7eb] rounded-[8px] bg-white text-[#374151]';
    $input = 'w-full text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white focus:border-[#4f46e5] focus:ring-2 focus:ring-[#e0e7ff]';
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1400px] flex-col gap-[18px]">
    <x-accounts.profile-tabs active="employees" />

    <x-accounts.page-head title="Employees" subtitle="বেতন, অগ্রিম আর login access — কর্মীদের সব হিসাব এক জায়গায়">
        <button type="button" class="{{ $btn }}" wire:click="openVoucher('salary_advance')">অগ্রিম দিন · Advance</button>
        <button type="button" class="{{ $btn }}" wire:click="openVoucher('salary')">বেতন দিন · Pay salary</button>
        <a href="{{ route('accounts.profiles.create', ['role' => 'employee']) }}" wire:navigate class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">+ New employee</a>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="মোট কর্মী" :value="number_format($s['headcount'])" :sub="$s['logins'] . ' with login access'" />
        <x-accounts.stat label="মাসিক বেতন · Payroll" :value="$money($s['payroll'])"
                         :sub="$s['month']->format('F') . ' salary due ' . \Carbon\Carbon::parse($b['due'])->format('d M')" />
        <x-accounts.stat label="বেতন বাকি · Salary payable" :value="$money($s['payable'])" color="#b91c1c" :sub="$s['payableSub']" />
        <x-accounts.stat label="অগ্রিম দেওয়া · Advance" :value="$money($s['advance'])" color="#4338ca" sub="Monthly salary থেকে কাটবে" />
    </div>

    <div class="flex flex-wrap items-center justify-between gap-[12px] rounded-[12px] border border-[#c7d2fe] bg-[#eef2ff] px-[18px] py-[14px]">
        <div>
            <div class="font-bold text-[#312e81]">{{ $s['month']->format('F Y') }} payroll{{ $b['prepared'] ? ' · prepared' : '' }}</div>
            <div class="mt-[2px] text-[13px] text-[#4338ca]">Gross {{ $money($b['gross']) }} − advance কাটা {{ $money($b['deduction']) }} = net {{ $money($b['net']) }} · due {{ \Carbon\Carbon::parse($b['due'])->format('d M') }}</div>
        </div>
        <button type="button" wire:click="openPrepare" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">{{ $b['prepared'] ? 'Open payroll' : 'Prepare payroll' }}</button>
    </div>

    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex flex-col gap-[12px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <div class="flex flex-wrap items-center gap-[10px]">
                <label for="emp-search" class="sr-only">Search employees</label>
                <div class="flex flex-[1_1_260px] items-center gap-[8px] rounded-[9px] border border-[#d1d5db] bg-white px-[12px]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <input id="emp-search" type="search" wire:model.live.debounce.300ms="q" placeholder="নাম, phone বা designation"
                           class="flex-1 border-0 bg-transparent px-0 py-[10px] text-[13.5px] outline-none focus:ring-0">
                </div>
                <label for="emp-dept" class="text-[13px] text-[#4b5563]">Department</label>
                <select id="emp-dept" wire:model.live="department" class="{{ $select }}">
                    <option value="">All departments</option>
                    @foreach ($this::DEPARTMENTS as $d)
                        <option value="{{ $d }}">{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div role="group" aria-label="Employee status" class="flex flex-wrap gap-[6px]">
                @foreach ($this::CHIPS as $key => $label)
                    <x-accounts.chip :on="$st === $key" :count="$s['chips'][$key]" wire:click="setSt('{{ $key }}')">{{ $label }}</x-accounts.chip>
                @endforeach
            </div>
        </div>

        <div x-data="{ sel: [] }" class="overflow-x-auto">
            <table class="min-w-[1060px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="w-[28px] py-[10px] pl-[18px] pr-0">
                            <input type="checkbox" aria-label="Select all" class="rounded" @change="sel = $event.target.checked ? @js($rows->pluck('profile.id')->map(fn ($id) => (string) $id)->values()) : []">
                        </th>
                        <th class="px-[8px] py-[10px] font-semibold">Employee</th>
                        <th class="px-[8px] py-[10px] font-semibold">Designation</th>
                        <th class="px-[8px] py-[10px] font-semibold">Joined</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Salary</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Advance</th>
                        <th class="px-[8px] py-[10px] font-semibold">{{ now()->subMonthNoOverflow()->format('F') }}</th>
                        <th class="px-[8px] py-[10px] font-semibold">Paid via</th>
                        <th class="px-[8px] py-[10px] font-semibold">Login</th>
                        <th class="px-[18px] py-[10px] text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php $p = $r['profile']; @endphp
                        <tr wire:key="e-{{ $p->id }}" class="border-t border-[#f0f1f4]">
                            <td class="py-[11px] pl-[18px] pr-0"><input type="checkbox" aria-label="Select employee" value="{{ $p->id }}" x-model="sel" class="rounded"></td>
                            <td class="px-[8px] py-[11px]">
                                <div class="flex items-center gap-[10px]">
                                    <div class="flex h-[34px] w-[34px] flex-none items-center justify-center rounded-full bg-[#ccfbf1] text-[12.5px] font-bold text-[#115e59]">{{ $p->initials() }}</div>
                                    <div><div class="font-semibold">{{ $p->display_name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $p->code }} · {{ $p->phone ?: '—' }}</div></div>
                                </div>
                            </td>
                            <td class="px-[8px] py-[11px]"><div>{{ $r['designation'] }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $r['department'] }}</div></td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-[#374151]">{{ $r['joined'] }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-semibold">{{ $money($r['salary']) }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right">
                                @if ($r['advance'] > 0.5)
                                    <div class="font-bold text-[#4338ca]">{{ $money($r['advance']) }}</div>
                                @else
                                    <div class="text-[#9ca3af]">—</div>
                                @endif
                                <div class="text-[11.5px] text-[#6b7280]">{{ $r['advNote'] }}</div>
                            </td>
                            <td class="px-[8px] py-[11px]">
                                @if ($r['statusPaid'] === null)
                                    <span class="text-[#9ca3af]">—</span>
                                @else
                                    <span @class(['whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold', 'bg-[#d1fae5] text-[#065f46]' => $r['statusPaid'], 'bg-[#fef3c7] text-[#92400e]' => ! $r['statusPaid']])>{{ $r['status'] }}</span>
                                @endif
                            </td>
                            <td class="px-[8px] py-[11px] text-[#374151]">{{ $r['via'] }}</td>
                            <td class="px-[8px] py-[11px]">
                                @if ($r['login'])
                                    <span class="whitespace-nowrap rounded-[6px] bg-[#e0e7ff] px-[8px] py-[3px] text-[12px] font-semibold text-[#3730a3]">{{ $r['login'] }}</span>
                                @else
                                    <span class="text-[12px] text-[#9ca3af]">No access</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-[18px] py-[11px] text-right">
                                <a href="#" wire:click.prevent="openVoucher('salary', {{ $p->id }})" class="font-semibold">Pay</a><span class="text-[#d1d5db]"> · </span><a href="{{ route('accounts.profiles.show', $p) }}" wire:navigate>View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($rows->isEmpty())
                <div class="px-[18px] py-[36px] text-center text-[#6b7280]">এই filter-এ কোনো employee নেই।</div>
            @endif
        </div>
    </section>

    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex items-center justify-between border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <h2 class="m-0 text-[15px] font-bold">বেতনের হিসাব · Payroll history</h2>
            <a href="#" wire:click.prevent="$toggle('allHistory')" class="text-[13px]">{{ $allHistory ? 'Show recent' : 'View all' }}</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[640px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="px-[18px] py-[10px] font-semibold">Month</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Gross</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Bonus</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Advance কাটা</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Net paid</th>
                        <th class="px-[18px] py-[10px] font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->history as $run)
                        <tr class="border-t border-[#f0f1f4]">
                            <td class="px-[18px] py-[11px] font-semibold"><a href="{{ route('accounts.profiles.payroll', ['month' => $run->month->format('Y-m')]) }}" wire:navigate class="!text-[#111827] hover:!text-[#312e81]">{{ $run->month->format($run->month->year === now()->year ? 'F' : 'F Y') }}</a></td>
                            <td class="px-[8px] py-[11px] text-right">{{ $money($run->gross) }}</td>
                            <td class="px-[8px] py-[11px] text-right">{{ (float) $run->bonus > 0 ? $money($run->bonus) . ($run->bonus_note ? ' · ' . $run->bonus_note : '') : '—' }}</td>
                            <td class="px-[8px] py-[11px] text-right">{{ (float) $run->advance_deducted > 0 ? $money($run->advance_deducted) : '—' }}</td>
                            <td class="px-[8px] py-[11px] text-right font-semibold">{{ $money($run->paid) }}</td>
                            <td class="px-[18px] py-[11px]">
                                @if ($run->unpaid() > 0.5)
                                    <span class="rounded-[6px] bg-[#fef3c7] px-[8px] py-[3px] text-[12px] font-semibold text-[#92400e]">{{ $money($run->unpaid()) }} বাকি</span>
                                @else
                                    <span class="rounded-[6px] bg-[#d1fae5] px-[8px] py-[3px] text-[12px] font-semibold text-[#065f46]">Paid</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-[#f0f1f4]"><td colspan="6" class="px-[18px] py-[24px] text-center text-[#6b7280]">এখনো কোনো payroll তৈরি হয়নি।</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($showPrepare)
        <x-accounts.modal title="Prepare payroll" close="$set('showPrepare', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]">
                    <label for="pr-month" class="text-[13px] font-semibold">Month</label>
                    <input id="pr-month" type="month" wire:model="prepare.month" class="{{ $input }}">
                    @error('prepare.month') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-[6px]">
                    <label for="pr-due" class="text-[13px] font-semibold">Due date</label>
                    <input id="pr-due" type="text" readonly placeholder="YYYY-MM-DD" wire:model="prepare.due_on" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}">
                </div>
                <div class="flex flex-col gap-[6px]">
                    <label for="pr-bonus" class="text-[13px] font-semibold">Bonus (% of salary)</label>
                    <input id="pr-bonus" type="number" min="0" step="0.01" wire:model="prepare.bonus" placeholder="0" class="{{ $input }}">
                    @error('prepare.bonus') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-[6px]">
                    <label for="pr-note" class="text-[13px] font-semibold">Bonus note</label>
                    <input id="pr-note" type="text" wire:model="prepare.bonus_note" placeholder="Eid" class="{{ $input }}">
                </div>
            </div>
            <div class="text-[12.5px] text-[#4b5563]">প্রত্যেক কর্মীর মাসিক বেতন + bonus থেকে চলতি অগ্রিমের মাসিক কিস্তি কেটে net বেতন Salary Payable-এ উঠবে। পরে "Pay" দিয়ে পরিশোধ করবেন।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showPrepare', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[18px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="preparePayroll" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#4f46e5] px-[22px] py-[10px] text-[14px] font-bold text-white">Prepare &amp; post</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @include('livewire.accounts.profiles.partials.voucher-modal')
</div>
