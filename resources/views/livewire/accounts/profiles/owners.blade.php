{{-- Owners — design: Owners.dc.html --}}
@php
    $s = $this->summary;
    $btn = 'text-[13px] px-[14px] py-[9px] border border-[#e5e7eb] rounded-[8px] bg-white text-[#374151]';
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1400px] flex-col gap-[18px]">
    <x-accounts.profile-tabs active="owners" />

    <x-accounts.page-head title="Owners" subtitle="মূলধন, উত্তোলন আর লাভের ভাগ। উত্তোলন খরচ না, তাই লাভ কমায় না।">
        <button type="button" class="{{ $btn }}" wire:click="openVoucher('capital')">মূলধন যোগ · Add capital</button>
        <button type="button" class="{{ $btn }}" wire:click="openVoucher('drawing')">উত্তোলন · Drawing</button>
        <a href="{{ route('accounts.profiles.create', ['role' => 'owner']) }}" wire:navigate class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">+ New owner</a>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="মোট মূলধন · Capital" :value="$money($s['capital'])" :sub="$s['count'] . ' owners' . ($s['shares'] ? ' · ' . $s['shares'] . ' share' : '')" />
        <x-accounts.stat :label="'লাভ · Profit ' . now()->year" :value="$money($s['profit'])" :color="$s['profit'] < 0 ? '#b91c1c' : '#047857'"
                         :sub="'Posted entries, Jan – ' . now()->format('M')" />
        <x-accounts.stat :label="'উত্তোলন · Drawings ' . now()->year" :value="$money($s['drawings'])" color="#b91c1c" :sub="'This month ' . $money($s['month'])" />
        <x-accounts.stat label="Net equity" :value="$money($s['equity'])" color="#4338ca" sub="Capital + profit − drawings" />
    </div>

    <div class="grid items-start gap-[16px] [grid-template-columns:repeat(auto-fit,minmax(380px,1fr))]">
        @forelse ($this->cards as $o)
            @php $p = $o['profile']; @endphp
            <section wire:key="owner-{{ $p->id }}" class="flex flex-col gap-[16px] rounded-[14px] border border-[#e5e7eb] bg-white p-[18px]">
                <div class="flex items-center gap-[12px]">
                    <div class="flex h-[48px] w-[48px] flex-none items-center justify-center rounded-full bg-[#fef3c7] text-[16px] font-bold text-[#92400e]">{{ $p->initials() }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[16px] font-bold">{{ $p->display_name }}</div>
                        <div class="mt-[4px] flex flex-wrap gap-[6px]">
                            <span class="rounded-[6px] bg-[#fef3c7] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#92400e]">Owner · {{ $o['share'] }}</span>
                            @if ($o['lender'])
                                <span class="rounded-[6px] bg-[#e0e7ff] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#3730a3]">Lender · {{ $o['lender'] }} বাকি</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right"><div class="text-[11.5px] text-[#6b7280]">Net equity</div><div class="text-[18px] font-bold text-[#4338ca]">{{ $o['equity'] }}</div></div>
                </div>
                <div class="grid grid-cols-3 gap-[10px]">
                    <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#6b7280]">মূলধন</div><div class="mt-[2px] font-bold">{{ $o['capital'] }}</div></div>
                    <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#6b7280]">লাভের ভাগ</div><div class="mt-[2px] font-bold text-[#047857]">+ {{ $o['profit'] }}</div></div>
                    <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#6b7280]">উত্তোলন</div><div class="mt-[2px] font-bold text-[#b91c1c]">− {{ $o['drawings'] }}</div></div>
                </div>
                <div>
                    <div class="mb-[6px] text-[12px] text-[#4b5563]">Drawings by month ({{ now()->year }})</div>
                    <div class="flex h-[56px] items-end gap-[6px]">
                        @foreach ($o['months'] as $m)
                            <div class="flex flex-1 flex-col items-center gap-[4px]" title="{{ $m['title'] }}">
                                <div class="w-full max-w-[22px] rounded-[3px] {{ $m['on'] ? 'bg-[#f59e0b]' : 'bg-[#e5e7eb]' }}" style="height: {{ $m['height'] }}px"></div>
                                <div class="text-[10px] text-[#6b7280]">{{ $m['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-[8px]">
                    <a href="#" wire:click.prevent="openVoucher('capital', {{ $p->id }})" class="flex-1 rounded-[8px] border border-[#e5e7eb] px-[12px] py-[9px] text-center text-[13px] !text-[#374151]">Add capital</a>
                    <a href="#" wire:click.prevent="openVoucher('drawing', {{ $p->id }})" class="flex-1 rounded-[8px] border border-[#e5e7eb] px-[12px] py-[9px] text-center text-[13px] !text-[#374151]">Record drawing</a>
                    <a href="{{ route('accounts.profiles.show', ['profile' => $p, 'role' => 'owner']) }}" wire:navigate class="flex-1 rounded-[8px] bg-[#4f46e5] px-[12px] py-[9px] text-center text-[13px] font-semibold !text-white">Statement</a>
                </div>
            </section>
        @empty
            <div class="rounded-[14px] border border-dashed border-[#fde68a] bg-white px-[18px] py-[36px] text-center text-[#6b7280]">
                এখনো কোনো owner profile নেই। <a href="{{ route('accounts.profiles.create', ['role' => 'owner']) }}" wire:navigate class="font-semibold">+ New owner</a>
            </div>
        @endforelse
    </div>

    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex items-center justify-between border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <h2 class="m-0 text-[15px] font-bold">মূলধন ও উত্তোলন · Recent</h2>
            <a href="#" wire:click.prevent="$toggle('allTx')" class="text-[13px]">{{ $allTx ? 'Show recent' : 'View all' }}</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[720px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="px-[18px] py-[10px] font-semibold">Date</th>
                        <th class="px-[8px] py-[10px] font-semibold">Owner</th>
                        <th class="px-[8px] py-[10px] font-semibold">Type</th>
                        <th class="px-[8px] py-[10px] font-semibold">Account</th>
                        <th class="px-[8px] py-[10px] font-semibold">Note</th>
                        <th class="px-[18px] py-[10px] text-right font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->transactions as $t)
                        <tr class="border-t border-[#f0f1f4]">
                            <td class="whitespace-nowrap px-[18px] py-[11px] text-[#374151]">{{ $t['date'] }}</td>
                            <td class="px-[8px] py-[11px] font-semibold">{{ $t['owner'] }}</td>
                            <td class="px-[8px] py-[11px]"><span class="rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $t['typeClass'] }}">{{ $t['type'] }}</span></td>
                            <td class="px-[8px] py-[11px] text-[#374151]">{{ $t['account'] }}</td>
                            <td class="px-[8px] py-[11px] text-[#6b7280]">{{ $t['note'] }}</td>
                            <td class="whitespace-nowrap px-[18px] py-[11px] text-right"><span class="font-bold {{ $t['out'] ? 'text-[#b91c1c]' : 'text-[#047857]' }}">{{ $t['amount'] }}</span></td>
                        </tr>
                    @empty
                        <tr class="border-t border-[#f0f1f4]"><td colspan="6" class="px-[18px] py-[24px] text-center text-[#6b7280]">এখনো কোনো মূলধন বা উত্তোলন নেই।</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('livewire.accounts.profiles.partials.voucher-modal')
</div>
