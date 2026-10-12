{{-- Receivables (Due) — design: Receivables.dc.html --}}
@php
    $s = $this->summary;
    $rows = $this->rows;
    $shown = $showAll ? $rows : $rows->take(50);
    $colors = ['#10b981', '#f59e0b', '#f97316', '#dc2626'];
    $labels = ['0–30 দিন', '31–60 দিন', '61–90 দিন', '90+ দিন'];
    $box = ['red' => 'border-[#fecaca] bg-[#fef2f2] text-[#7f1d1d]', 'amber' => 'border-[#fde68a] bg-[#fffbeb] text-[#78350f]', 'blue' => 'border-[#c7d2fe] bg-[#eef2ff] text-[#312e81]'];
    $input = 'w-full text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $small = 'flex-1 rounded-[8px] border border-[#e5e7eb] bg-white px-[10px] py-[7px] text-center text-[12.5px] !text-[#374151]';
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="আমি পাবো · Receivables" subtitle="কার কাছে কত, কত দিনের পুরনো, কবে দেবে বলেছে">
        <button type="button" onclick="window.print()" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Aging report (PDF)</button>
        <button type="button" wire:click="remindOld" wire:confirm="সব 60+ দিনের বাকি customer-কে reminder SMS পাঠাবেন?" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Reminder SMS · 60+ দিন</button>
        <button type="button" wire:click="$dispatch('voucher-form', { type: 'receipt' })" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">টাকা নিন · Collect</button>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(210px,1fr))]">
        <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
            <div class="text-[12.5px] text-[#4b5563]">Customer বাকি</div>
            <div class="mt-[6px] text-[24px] font-bold text-[#047857]">{{ $money($s['due']) }}</div>
            <div class="mt-[4px] text-[12px] font-semibold text-[#b45309]">{{ $money($s['over60']) }} · 60+ দিন পুরনো</div>
        </div>
        <x-accounts.stat label="Courier-এর কাছে COD" :value="$money($s['cod'])" color="#047857" :sub="$s['codParcels'] . ' delivered parcels · ' . $s['couriers'] . ' couriers'" />
        <x-accounts.stat label="অগ্রিম দেওয়া · Advances" :value="$money($s['supplierAdv'] + $s['staffAdv'])" color="#4338ca" :sub="'Supplier ' . $money($s['supplierAdv']) . ' · staff ' . $money($s['staffAdv'])" />
        <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
            <div class="text-[12.5px] text-[#4b5563]">এই মাসে আদায় · Collected</div>
            <div class="mt-[6px] text-[24px] font-bold">{{ $money($s['collected']) }}</div>
            <div class="mt-[6px] flex items-center gap-[8px]">
                @if ($s['target'] > 0)
                    <div class="h-[6px] flex-1 overflow-hidden rounded-[4px] bg-[#eef0f3]"><div class="h-full bg-[#10b981]" style="width: {{ $s['targetPct'] }}%"></div></div>
                    <span class="whitespace-nowrap text-[12px] text-[#6b7280]">{{ $s['targetPct'] }}% of {{ $money($s['target']) }} target</span>
                @else
                    <a href="{{ route('accounts.settings') }}" wire:navigate class="text-[12px]">Set a monthly target</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Aging bar --}}
    <section class="flex flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
        <div class="flex flex-wrap items-center justify-between gap-[8px]">
            <h2 class="m-0 text-[15px] font-bold">বাকি কত দিনের · Customer aging</h2>
            <span class="text-[12.5px] text-[#6b7280]">Oldest unpaid invoice থেকে গণনা</span>
        </div>
        <div class="flex h-[14px] gap-[2px] overflow-hidden rounded-[7px] bg-[#eef0f3]">
            @foreach ($s['pct'] as $i => $pct)
                @if ($pct > 0)<div style="width: {{ $pct }}%; background: {{ $colors[$i] }}"></div>@endif
            @endforeach
        </div>
        <div class="grid gap-[10px] text-[13px] [grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]">
            @foreach ($s['buckets'] as $i => $b)
                <div><div class="flex items-center gap-[6px] text-[#4b5563]"><span class="h-[10px] w-[10px] rounded-[3px]" style="background: {{ $colors[$i] }}"></span>{{ $labels[$i] }}</div>
                    <div class="mt-[2px] font-bold">{{ $money($b['amount']) }} <span class="font-medium text-[#6b7280]">· {{ $b['count'] }} জন</span></div></div>
            @endforeach
        </div>
    </section>

    <div class="flex flex-wrap items-start gap-[16px]">

        {{-- Aging table --}}
        <section class="min-w-0 flex-[999_1_640px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="acc-no-print flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <div role="group" aria-label="Aging bucket" class="flex flex-wrap gap-[6px]">
                    @foreach ($this::CHIPS as $key => $label)
                        <x-accounts.chip :on="$bucket === $key" wire:click="setBucket('{{ $key }}')">{{ $label }}</x-accounts.chip>
                    @endforeach
                </div>
                <div class="flex items-center gap-[8px]">
                    <label for="rv-sort" class="text-[13px] text-[#4b5563]">Sort</label>
                    <select id="rv-sort" wire:model.live="sort" class="rounded-[9px] border border-[#d1d5db] bg-white py-[8px] pl-[10px] pr-[30px] text-[13px]">
                        @foreach ($this::SORTS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[900px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Customer</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Total</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">0–30</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">31–60</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">61–90</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">90+</th>
                            <th class="px-[8px] py-[10px] font-semibold">Promise / follow-up</th>
                            <th class="acc-no-print px-[18px] py-[10px] text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shown as $r)
                            @php $p = $r['profile']; $b = $r['buckets']; @endphp
                            <tr wire:key="rv-{{ $p->id }}" class="border-t border-[#f0f1f4]">
                                <td class="py-[11px] pl-[18px] pr-[8px]"><a href="{{ route('accounts.profiles.show', $p) }}" wire:navigate class="font-semibold !text-[#111827]">{{ $p->display_name }}</a><div class="text-[11.5px] text-[#6b7280]">{{ $p->area() }} · {{ $r['items'] }} invoices</div></td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold">{{ $money($r['total']) }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right text-[#047857]">{{ $dash($b[0]) }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right text-[#b45309]">{{ $dash($b[1]) }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-semibold text-[#c2410c]">{{ $dash($b[2]) }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold text-[#b91c1c]">{{ $dash($b[3]) }}</td>
                                <td class="px-[8px] py-[11px]"><div class="text-[13px] {{ $r['promiseStyle'] }}">{{ $r['promise'] }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $r['last'] }}</div></td>
                                <td class="acc-no-print whitespace-nowrap px-[18px] py-[11px] text-right">
                                    <a href="#" wire:click.prevent="collect({{ $p->id }})" class="font-semibold">Collect</a><span class="text-[#d1d5db]"> · </span><a href="#" wire:click.prevent="remind({{ $p->id }})">Remind</a><span class="text-[#d1d5db]"> · </span><a href="#" wire:click.prevent="openPromise({{ $p->id }})">Promise</a>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#f0f1f4]"><td colspan="8" class="px-[18px] py-[30px] text-center text-[#6b7280]">এই filter-এ কোনো বাকি নেই।</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-[#e5e7eb] bg-[#f9fafb] font-bold">
                            <td class="py-[11px] pl-[18px] pr-[8px]">All {{ number_format($s['count']) }} customers</td>
                            <td class="px-[8px] py-[11px] text-right">{{ $money($s['due']) }}</td>
                            @foreach ($s['buckets'] as $bk)<td class="px-[8px] py-[11px] text-right">{{ $money($bk['amount']) }}</td>@endforeach
                            <td colspan="2" class="px-[18px] py-[11px]">
                                @if ($rows->count() > 50)<button type="button" wire:click="$toggle('showAll')" class="acc-no-print text-[12.5px] font-semibold text-[#4338ca]">{{ $showAll ? 'Show top 50' : 'Show all ' . $rows->count() }}</button>@endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        {{-- Follow-up --}}
        <aside class="acc-no-print flex min-w-0 flex-[1_1_340px] flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
            <div><h2 class="m-0 text-[15px] font-bold">আজকের follow-up</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">যাদের সাথে আজ কথা বলা দরকার</div></div>
            @forelse ($this->followups as $f)
                @php $p = $f['p']; @endphp
                <div wire:key="fu-{{ $p->id }}" class="rounded-[12px] border p-[12px] {{ $box[$f['tone']] }}">
                    <div class="flex items-start justify-between gap-[8px]">
                        <div><div class="font-bold">{{ $p->display_name }}</div><div class="mt-[2px] text-[12.5px]">{{ $f['why'] }}</div></div>
                        <div class="whitespace-nowrap font-bold">{{ $money($f['amount']) }}</div>
                    </div>
                    <div class="mt-[10px] flex gap-[8px]">
                        @if ($tel($p->phone))<a href="{{ $tel($p->phone) }}" wire:click="logCall({{ $p->id }})" class="{{ $small }}">Call</a>@else<span class="{{ $small }} opacity-50">Call</span>@endif
                        <a href="#" wire:click.prevent="remind({{ $p->id }})" class="{{ $small }}">SMS</a>
                        @if ($f['third'] === 'Write off')
                            <a href="#" wire:click.prevent="openWriteOff({{ $p->id }})" class="{{ $small }}">Write off</a>
                        @elseif ($f['third'] === 'Stop credit')
                            <a href="#" wire:click.prevent="stopCredit({{ $p->id }})" wire:confirm="{{ $p->display_name }}-কে নতুন বাকিতে বিক্রি বন্ধ করবেন?" class="{{ $small }}">Stop credit</a>
                        @else
                            <a href="#" wire:click.prevent="openPromise({{ $p->id }})" class="{{ $small }}">Set promise</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-[12px] border border-[#a7f3d0] bg-[#ecfdf5] p-[12px] text-[13px] text-[#064e3b]">আজ কারো সাথে জরুরি follow-up নেই।</div>
            @endforelse
        </aside>
    </div>

    <div class="flex flex-wrap items-start gap-[16px]">
        <section class="min-w-0 flex-[1_1_520px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex items-center justify-between border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <div><h2 class="m-0 text-[15px] font-bold">Courier COD settlement</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">Delivered হয়েছে, কিন্তু টাকা এখনো আসেনি</div></div>
                <a href="#" wire:click.prevent="openSettle" class="acc-no-print text-[13px]">Match settlement</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[520px] text-[13px]">
                    <thead><tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]"><th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Courier</th><th class="px-[8px] py-[10px] text-right font-semibold">Parcels</th><th class="px-[8px] py-[10px] text-right font-semibold">Amount</th><th class="px-[8px] py-[10px] font-semibold">Oldest</th><th class="px-[18px] py-[10px] font-semibold">Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->cod as $c)
                            <tr class="border-t border-[#f0f1f4]">
                                <td class="py-[11px] pl-[18px] pr-[8px] font-semibold"><a href="#" wire:click.prevent="openSettle({{ $c['courier_id'] }})" class="!text-[#111827]">{{ $c['name'] }}</a></td>
                                <td class="px-[8px] py-[11px] text-right">{{ $c['parcels'] }}</td>
                                <td class="px-[8px] py-[11px] text-right font-bold">{{ $money($c['amount']) }}</td>
                                <td @class(['px-[8px] py-[11px]', 'font-semibold text-[#b91c1c]' => $c['late'], 'text-[#374151]' => ! $c['late']])>{{ $c['oldest'] ? $c['oldest']->format('d M') . ($c['late'] ? ' · ' . (int) $c['oldest']->diffInDays(today()) . ' দিন' : '') : '—' }}</td>
                                <td class="px-[18px] py-[11px]"><span class="rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $c['cls'] }}">{{ $c['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#f0f1f4]"><td colspan="5" class="px-[18px] py-[20px] text-center text-[#6b7280]">কোনো courier-এর কাছে COD বাকি নেই।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="min-w-0 flex-[1_1_380px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="border-b border-[#f0f1f4] px-[18px] py-[14px]"><h2 class="m-0 text-[15px] font-bold">অগ্রিম দেওয়া · Advances given</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">মাল বা বেতনে adjust হবে</div></div>
            <table class="text-[13px]">
                <tbody>
                    @forelse ($this->advances as $ad)
                        <tr class="border-t border-[#f0f1f4] first:border-t-0">
                            <td class="py-[11px] pl-[18px] pr-[8px]"><a href="{{ route('accounts.profiles.show', $ad['id']) }}" wire:navigate class="font-semibold !text-[#111827]">{{ $ad['name'] }}</a><div class="text-[11.5px] text-[#6b7280]">{{ $ad['note'] }}</div></td>
                            <td class="px-[18px] py-[11px] text-right font-bold text-[#4338ca]">{{ $money($ad['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-[18px] py-[18px] text-center text-[#6b7280]">কোনো অগ্রিম নেই।</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    @if ($promiseFor)
        <x-accounts.modal title="Promise / follow-up" close="$set('promiseFor', null)">
            <div class="flex flex-wrap gap-[6px]">
                <x-accounts.chip :on="$promise['kind'] === 'promise'" wire:click="$set('promise.kind', 'promise')">দেবে বলেছে · Promise</x-accounts.chip>
                <x-accounts.chip :on="$promise['kind'] === 'callback'" wire:click="$set('promise.kind', 'callback')">আবার call · Call back</x-accounts.chip>
            </div>
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Date</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="promise.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}">@error('promise.date')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                @if ($promise['kind'] === 'promise')
                    <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Amount (৳)</label><input type="number" min="0" wire:model="promise.amount" class="{{ $input }}">@error('promise.amount')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                @endif
            </div>
            <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Note</label><input type="text" wire:model="promise.note" placeholder="যেমন: Puja-র পরে দেবে" class="{{ $input }}"></div>
            <x-slot:footer>
                <button type="button" wire:click="$set('promiseFor', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="savePromise" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @if ($writeOffFor)
        <x-accounts.modal title="Write off · bad debt" close="$set('writeOffFor', null)">
            <div class="text-[13px] text-[#374151]">একটা draft journal হবে: Dr Bad debt expense / Cr Receivable। Post না করা পর্যন্ত বাকি কমবে না।</div>
            <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Amount (৳)</label><input type="number" min="0" wire:model="writeOffAmount" class="{{ $input }}">@error('writeOffAmount')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('writeOffFor', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="writeOff" class="rounded-[9px] border-0 bg-[#b91c1c] px-[20px] py-[10px] text-[14px] font-bold text-white">Create draft</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @if ($showSettle)
        <x-accounts.modal title="Match settlement · Courier COD" close="$set('showSettle', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Courier</label>
                    <select wire:model.live="settle.courier_id" class="{{ $input }}">@foreach ($this->cod as $c)<option value="{{ $c['courier_id'] }}">{{ $c['name'] }} · {{ $money($c['amount']) }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">COD amount (gross ৳)</label><input type="number" min="0" wire:model="settle.gross" class="{{ $input }}">@error('settle.gross')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Courier charge (৳)</label><input type="number" min="0" wire:model="settle.fee" placeholder="0" class="{{ $input }}"></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Received in</label>
                    <select wire:model="settle.account_id" class="{{ $input }}">@foreach (app(\App\Services\Profiles\LedgerAccounts::class)->cashAccounts() as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Date</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="settle.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}"></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Parcels</label><input type="number" min="0" wire:model="settle.parcels" class="{{ $input }}"></div>
            </div>
            <div class="text-[12.5px] text-[#6b7280]">Draft receipt হবে: Dr bank (net) + Dr Courier expense (charge) / Cr courier-এর কাছে থাকা COD।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showSettle', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveSettle" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Create receipt</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @livewire('accounts.voucher-form')
</div>
