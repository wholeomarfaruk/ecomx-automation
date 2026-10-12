{{-- Loans & EMI — design: LoansEmi.dc.html --}}
@php
    $c = $this->cards;
    $rows = $this->rows;
    $pv = $this->preview;
    $t = $this::TYPES[$plan['type']];
    $badge = ['ok' => 'bg-[#d1fae5] text-[#065f46]', 'soon' => 'bg-[#fef3c7] text-[#92400e]', 'late' => 'bg-[#fee2e2] text-[#991b1b]', 'flex' => 'bg-[#f3f4f6] text-[#374151]'];
    $input = 'text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $err = 'text-[12px] text-[#b91c1c]';
    $dirCount = ['all' => $this->all->count(), 'pay' => $this->all->where('dir', 'pay')->count(), 'recv' => $this->all->where('dir', 'recv')->count()];
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="ঋণ ও কিস্তি · Loans &amp; EMI" subtitle="যত কিস্তির হিসাব — নেওয়া ঋণ, কিস্তিতে বিক্রি, supplier-কে কিস্তি, staff loan">
        <a href="{{ route('accounts.profiles.lenders') }}" wire:navigate class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] !text-[#374151]">Lenders</a>
        <a href="#new-plan" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">+ New loan / kisti plan</a>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="চালু plan · Active" :value="$c['active'][0]" :sub="$c['active'][1]" />
        <x-accounts.stat label="এই মাসে দিতে হবে" :value="$money($c['pay'][0])" color="#b91c1c" :sub="$c['pay'][1]" />
        <x-accounts.stat label="এই মাসে পাওয়ার কথা" :value="$money($c['get'][0])" color="#047857" :sub="$c['get'][1]" />
        <x-accounts.stat label="মিস হওয়া কিস্তি · Overdue" :value="$c['late'][0]" color="#b45309" :sub="$c['late'][1]" />
        <x-accounts.stat label="Support / অগ্রিম কিস্তি" :value="$money($c['support'][0])" color="#4338ca" :sub="$c['support'][1]" />
    </div>

    {{-- Plans --}}
    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <div role="group" aria-label="Direction" class="flex flex-wrap gap-[6px]">
                @foreach ($this::DIRS as $key => $label)
                    <x-accounts.chip :on="$dir === $key" :count="$dirCount[$key]" wire:click="setDir('{{ $key }}')">{{ $label }}</x-accounts.chip>
                @endforeach
            </div>
            <div role="group" aria-label="Frequency" class="flex flex-wrap gap-[6px]">
                @foreach ($this::FQ as $key => $label)
                    <x-accounts.chip :on="$fq === $key" wire:click="setFq('{{ $key }}')">{{ $label }}</x-accounts.chip>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[960px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Party</th>
                        <th class="px-[8px] py-[10px] font-semibold">Rule</th>
                        <th class="w-[150px] px-[8px] py-[10px] font-semibold">Progress</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Kisti</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">বাকি</th>
                        <th class="px-[8px] py-[10px] font-semibold">Next</th>
                        <th class="px-[18px] py-[10px] font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $p)
                        <tr wire:key="pl-{{ $p['key'] }}" class="border-t border-[#f0f1f4]">
                            <td class="py-[11px] pl-[18px] pr-[8px]">
                                <div class="flex flex-wrap items-center gap-[6px]">
                                    @if ($p['profile_id'])<a href="{{ route('accounts.profiles.show', $p['profile_id']) }}" wire:navigate class="font-semibold !text-[#111827]">{{ $p['party'] }}</a>@else<span class="font-semibold">{{ $p['party'] }}</span>@endif
                                    <span @class(['rounded-[6px] px-[7px] py-[1px] text-[11px] font-semibold', 'bg-[#fee2e2] text-[#991b1b]' => $p['dir'] === 'pay', 'bg-[#d1fae5] text-[#065f46]' => $p['dir'] === 'recv'])>{{ $p['dir'] === 'pay' ? 'দিবো' : 'পাবো' }}</span>
                                </div>
                                <div class="text-[11.5px] text-[#6b7280]">{{ $p['kind'] }}</div>
                            </td>
                            <td class="px-[8px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] bg-[#eef2ff] px-[8px] py-[3px] text-[12px] font-semibold text-[#3730a3]">{{ $p['rule'] }}</span></td>
                            <td class="px-[8px] py-[11px]">
                                <div class="mb-[4px] flex justify-between text-[11.5px] text-[#4b5563]"><span>{{ $p['done'] }}</span><span>{{ $p['pct'] }}%</span></div>
                                <div class="h-[6px] overflow-hidden rounded-[4px] bg-[#eef0f3]"><div class="h-full rounded-[4px]" style="width: {{ $p['pct'] }}%; background: {{ $p['st'] === 'late' ? '#dc2626' : '#4f46e5' }}"></div></div>
                            </td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right">{{ $p['kisti'] }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold">{{ $p['left'] }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px]"><div class="font-semibold">{{ $p['next'] }}</div><div class="text-[11.5px] text-[#047857]">{{ $p['nextNote'] }}</div></td>
                            <td class="px-[18px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $badge[$p['st']] }}">{{ $p['status'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($rows->isEmpty())
            <div class="px-[18px] py-[32px] text-center text-[#6b7280]">এই filter-এ কোনো plan নেই।</div>
        @endif
    </section>

    {{-- Builder --}}
    <section id="new-plan" class="scroll-mt-[20px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="border-b border-[#f0f1f4] px-[20px] py-[16px]">
            <h2 class="m-0 text-[17px] font-bold">নতুন ঋণ / কিস্তি plan</h2>
            <div class="mt-[2px] text-[12.5px] text-[#6b7280]">নিয়ম দিন — schedule নিজে থেকে তৈরি হবে</div>
        </div>
        <div class="flex flex-wrap">

            <div class="flex min-w-0 flex-[1_1_440px] flex-col gap-[16px] border-r border-[#f0f1f4] px-[20px] py-[18px]">
                <div class="flex flex-col gap-[8px]">
                    <span class="text-[13px] font-semibold">কী ধরনের?</span>
                    <div role="group" aria-label="Plan type" class="flex flex-wrap gap-[8px]">
                        @foreach ($this::TYPES as $key => $ty)
                            <button type="button" wire:click="setType('{{ $key }}')" @class([
                                'rounded-[10px] px-[14px] py-[10px] text-left',
                                'border-2 border-[#4f46e5] bg-[#eef2ff] text-[#312e81]' => $plan['type'] === $key,
                                'border border-[#e5e7eb] bg-white text-[#374151]' => $plan['type'] !== $key,
                            ])><span class="block font-bold">{{ $ty['label'] }}</span><span class="mt-[2px] block text-[11.5px] opacity-80">{{ $ty['sub'] }}</span></button>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                    <div class="flex flex-col gap-[6px]">
                        <div class="flex items-center justify-between gap-[6px]"><label class="text-[13px] font-semibold">{{ $t['party'] }}</label><a href="{{ route('accounts.profiles.create', ['role' => $t['role']]) }}" wire:navigate class="text-[12px]">+ New {{ strtolower($t['party']) }}</a></div>
                        <div wire:key="party-{{ $plan['type'] }}"><x-searchable-select field="plan.profile_id" :value="$plan['profile_id']" :options="$this->partyOptions" placeholder="— Select —" /></div>
                        @error('plan.profile_id')<span class="{{ $err }}">{{ $message }}</span>@enderror
                    </div>
                    <div class="flex flex-col gap-[6px]">
                        <label for="pl-amt" class="text-[13px] font-semibold">{{ $t['amount'] }} (৳)</label>
                        <input id="pl-amt" type="number" min="0" wire:model.live.debounce.400ms="plan.amount" class="{{ $input }}">
                        @error('plan.amount')<span class="{{ $err }}">{{ $message }}</span>@enderror
                    </div>
                    <div class="flex flex-col gap-[6px]">
                        <label for="pl-start" class="text-[13px] font-semibold">প্রথম কিস্তি · First due</label>
                        <input id="pl-start" type="date" wire:model.live="plan.first_due" class="{{ $input }} py-[8px]">
                        @error('plan.first_due')<span class="{{ $err }}">{{ $message }}</span>@enderror
                    </div>
                    @if ($plan['type'] === 'loan')
                        <div class="flex flex-col gap-[6px]">
                            <label for="pl-acc" class="text-[13px] font-semibold">টাকা আসবে · Received in</label>
                            <select id="pl-acc" wire:model="plan.account_id" class="{{ $input }} px-[10px]">@foreach ($this->cashAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select>
                            @error('plan.account_id')<span class="{{ $err }}">{{ $message }}</span>@enderror
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-[8px]">
                    <span class="text-[13px] font-semibold">কত দিন পরপর · Frequency</span>
                    <div role="group" aria-label="Frequency" class="flex flex-wrap gap-[6px]">
                        @foreach (\App\Services\Accounts\KistiBuilder::FREQUENCIES as $key => $label)
                            <x-accounts.chip :on="$plan['frequency'] === $key" wire:click="$set('plan.frequency', '{{ $key }}')">{{ $label }}</x-accounts.chip>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                    @if ($plan['frequency'] !== 'custom')
                        <div class="flex flex-col gap-[6px]">
                            <label for="pl-n" class="text-[13px] font-semibold">কয়টা কিস্তি</label>
                            <input id="pl-n" type="number" min="1" wire:model.live.debounce.400ms="plan.count" class="{{ $input }}">
                            @error('plan.count')<span class="{{ $err }}">{{ $message }}</span>@enderror
                        </div>
                    @else
                        <div class="flex flex-col gap-[6px]">
                            <label for="pl-off" class="text-[13px] font-semibold">কত দিন পরে পরে (comma দিয়ে)</label>
                            <input id="pl-off" type="text" wire:model.live.debounce.400ms="plan.offsets" class="{{ $input }}">
                            @error('plan.offsets')<span class="{{ $err }}">{{ $message }}</span>@enderror
                        </div>
                    @endif
                    <div class="flex flex-col gap-[6px]">
                        <label for="pl-int" class="text-[13px] font-semibold">লাভ / সুদ</label>
                        <select id="pl-int" wire:model.live="plan.rate_type" class="{{ $input }} px-[10px]">
                            <option value="flat">Flat (মোট টাকার উপর)</option>
                            <option value="reducing">Reducing (বাকির উপর)</option>
                            <option value="none">নেই · No interest</option>
                        </select>
                    </div>
                    @if ($plan['rate_type'] !== 'none')
                        <div class="flex flex-col gap-[6px]">
                            <label for="pl-rate" class="text-[13px] font-semibold">Rate (% per year)</label>
                            <input id="pl-rate" type="number" step="0.5" min="0" wire:model.live.debounce.400ms="plan.rate" class="{{ $input }}">
                            @error('plan.rate')<span class="{{ $err }}">{{ $message }}</span>@enderror
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-[8px]">
                    <span class="text-[13px] font-semibold">কিস্তির বেশি টাকা দিলে (Support)</span>
                    @foreach ($this::SUPPORT as $key => [$label, $sub])
                        <label class="flex cursor-pointer items-start gap-[10px] text-[13px]">
                            <input type="radio" name="support" value="{{ $key }}" wire:model="plan.support_rule" class="mt-[2px] accent-[#4f46e5]">
                            <span><span class="block font-semibold">{{ $label }}</span><span class="block text-[12px] text-[#6b7280]">{{ $sub }}</span></span>
                        </label>
                    @endforeach
                </div>

                <div class="flex flex-col gap-[6px]">
                    <label for="pl-late" class="text-[13px] font-semibold">দেরি হলে · Late rule</label>
                    <select id="pl-late" wire:model="plan.late_rule" class="{{ $input }} px-[10px]">
                        @foreach (\App\Services\Accounts\KistiPlans::LATE as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>

            {{-- Preview --}}
            <div class="flex min-w-0 flex-[1_1_420px] flex-col gap-[14px] bg-[#fcfcfd] px-[20px] py-[18px]">
                <div class="grid grid-cols-2 gap-[10px]">
                    <div class="rounded-[12px] border border-[#e5e7eb] bg-white px-[14px] py-[12px]"><div class="text-[12px] text-[#6b7280]">প্রতি কিস্তি</div><div class="mt-[2px] text-[22px] font-bold text-[#4338ca]">{{ $money($pv['kisti']) }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $pv['count'] && $pv['last'] != $pv['kisti'] ? 'শেষ কিস্তি ' . $money($pv['last']) : 'সব কিস্তি সমান' }}</div></div>
                    <div class="rounded-[12px] border border-[#e5e7eb] bg-white px-[14px] py-[12px]"><div class="text-[12px] text-[#6b7280]">মোট {{ $t['word'] }}</div><div class="mt-[2px] text-[22px] font-bold">{{ $money($pv['total']) }}</div><div class="text-[11.5px] text-[#6b7280]">লাভ/সুদ {{ $money($pv['interest']) }}</div></div>
                    <div class="rounded-[12px] border border-[#e5e7eb] bg-white px-[14px] py-[12px]"><div class="text-[12px] text-[#6b7280]">কিস্তি সংখ্যা</div><div class="mt-[2px] text-[18px] font-bold">{{ $pv['count'] }}</div></div>
                    <div class="rounded-[12px] border border-[#e5e7eb] bg-white px-[14px] py-[12px]"><div class="text-[12px] text-[#6b7280]">শেষ কিস্তি</div><div class="mt-[2px] text-[18px] font-bold">{{ $pv['end']?->format('d M Y') ?? '—' }}</div></div>
                </div>
                <div>
                    <div class="mb-[6px] text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]">Schedule preview</div>
                    <div class="overflow-hidden rounded-[12px] border border-[#e5e7eb] bg-white">
                        <table class="text-[13px]">
                            <thead><tr class="bg-[#f9fafb] text-left text-[11.5px] uppercase text-[#6b7280]"><th class="px-[12px] py-[8px] font-semibold">#</th><th class="px-[12px] py-[8px] font-semibold">Due</th><th class="px-[12px] py-[8px] text-right font-semibold">Kisti</th><th class="px-[12px] py-[8px] text-right font-semibold">বাকি থাকবে</th></tr></thead>
                            <tbody>
                                @forelse ($pv['sched'] as $r)
                                    <tr class="border-t border-[#f0f1f4]">
                                        <td class="px-[12px] py-[8px] text-[#6b7280]">{{ $r['no'] }}</td>
                                        @if (isset($r['more']))
                                            <td class="px-[12px] py-[8px]">{{ $r['more'] }} more</td><td></td><td></td>
                                        @else
                                            <td class="px-[12px] py-[8px]">{{ $r['due']->format('d M Y') }}</td>
                                            <td class="px-[12px] py-[8px] text-right font-semibold">{{ $money($r['amount']) }}</td>
                                            <td class="px-[12px] py-[8px] text-right text-[#4b5563]">{{ $money($r['left']) }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-[12px] py-[14px] text-center text-[#6b7280]">Amount আর কিস্তি দিন।</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="rounded-[10px] border border-[#c7d2fe] bg-[#eef2ff] px-[12px] py-[10px] text-[12.5px] leading-[1.5] text-[#312e81]">{{ $pv['note'] }}</div>
                <div class="flex justify-end gap-[8px]">
                    <button type="button" wire:click="resetPlan('{{ $plan['type'] }}')" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[10px] text-[13px] text-[#374151]">Cancel</button>
                    <button type="button" wire:click="createPlan" wire:loading.attr="disabled" class="rounded-[8px] border-0 bg-[#4f46e5] px-[18px] py-[10px] text-[13px] font-semibold text-white">Create plan</button>
                </div>
            </div>
        </div>
    </section>
</div>
