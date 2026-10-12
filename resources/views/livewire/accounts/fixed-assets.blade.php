{{-- Fixed Assets — design: FixedAssets.dc.html --}}
@use('App\Services\Accounts\FixedAssets', 'AssetBook')
@php
    $s = $this->summary;
    $rows = $this->rows;
    $a = $this->current;
    $input = 'w-full text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $err = 'text-[12px] text-[#b91c1c]';
    $badge = function ($x) use ($money) {
        if ($x->status === 'disposed') {
            $p = (float) ($x->disposal?->proceeds ?? 0);

            return ['bg-[#f3f4f6] text-[#4b5563]', $p > 0 ? 'Sold ' . $money($p) : 'Disposed'];
        }

        return $x->in_repair_since ? ['bg-[#fef3c7] text-[#92400e]', 'In repair'] : ['bg-[#d1fae5] text-[#065f46]', 'In use'];
    };
    $tone = ['amber' => 'border-[#fde68a] bg-[#fffbeb] text-[#78350f]', 'red' => 'border-[#fecaca] bg-[#fef2f2] text-[#7f1d1d]', 'gray' => 'border-[#e5e7eb] bg-[#f3f4f6] text-[#374151]'];
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="স্থায়ী সম্পদ · Fixed Assets" subtitle="মেশিন, furniture, গাড়ি, electronics — কত দিয়ে কিনেছি, এখন দাম কত">
        <button type="button" onclick="window.print()" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Asset register (PDF)</button>
        @if ($s['run'])
            <a href="{{ route('accounts.posting') }}" wire:navigate class="rounded-[8px] border border-[#c7d2fe] bg-[#eef2ff] px-[14px] py-[9px] text-[13px] font-semibold !text-[#3730a3]">{{ now()->format('F') }} depreciation · {{ $s['run']->ref() }}</a>
        @else
            <button type="button" wire:click="runDepreciation" wire:confirm="{{ now()->format('F') }} মাসের অবচয়ের draft journal তৈরি করবেন?" class="rounded-[8px] border border-[#c7d2fe] bg-[#eef2ff] px-[14px] py-[9px] text-[13px] font-semibold text-[#3730a3]">Run {{ now()->format('F') }} depreciation</button>
        @endif
        <button type="button" wire:click="openAdd" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">+ Add asset</button>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="কেনা দাম · Cost" :value="$money($s['cost'])" :sub="$s['count'] . ' assets in use'" />
        <x-accounts.stat label="মোট অবচয় · Depreciation" :value="$money($s['accum'])" color="#b45309" :sub="$s['upto']" />
        <x-accounts.stat label="এখনকার দাম · Book value" :value="$money($s['book'])" color="#4338ca" sub="Shows in Balance Sheet" />
        <x-accounts.stat label="এই মাসের অবচয়" :value="$money($s['month'])" :sub="$s['monthNote']" />
    </div>

    <div class="flex flex-wrap items-start gap-[16px]">

        <section class="min-w-0 flex-[999_1_640px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="acc-no-print flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <div role="group" aria-label="Category" class="flex flex-wrap gap-[6px]">
                    @foreach ($this->categories as $c)
                        <x-accounts.chip :on="$cat === $c" wire:click="setCat('{{ $c }}')">{{ $c }}</x-accounts.chip>
                    @endforeach
                </div>
                <label class="flex items-center gap-[6px] text-[13px] text-[#374151]"><input type="checkbox" wire:model.live="disposed" class="accent-[#4f46e5]"> Disposed দেখাও</label>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[860px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Asset</th>
                            <th class="px-[8px] py-[10px] font-semibold">Bought</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Cost</th>
                            <th class="w-[140px] px-[8px] py-[10px] font-semibold">Depreciated</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Book value</th>
                            <th class="px-[18px] py-[10px] font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            @php $c = AssetBook::calc($r); [$bc, $bl] = $badge($r); $on = $a && $a->id === $r->id; @endphp
                            <tr wire:key="fa-{{ $r->id }}" @class(['border-t border-[#f0f1f4]', 'bg-[#eef2ff] shadow-[inset_3px_0_0_#4f46e5]' => $on, 'opacity-60' => $r->status === 'disposed'])>
                                <td class="py-[11px] pl-[18px] pr-[8px]">
                                    <button type="button" wire:click="select({{ $r->id }})" class="border-0 bg-transparent p-0 text-left text-[13px] font-bold text-[#4338ca]">{{ $r->name }}</button>
                                    <div class="text-[11.5px] text-[#6b7280]">{{ $r->code ?: '#' . $r->id }} · {{ $r->category ?: 'Other' }} · {{ $r->assignee?->display_name ?? ($r->location ?: '—') }}</div>
                                </td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-[#374151]">{{ $r->purchase_date->format('M Y') }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right">{{ $money($r->cost) }}</td>
                                <td class="px-[8px] py-[11px]">
                                    <div class="mb-[4px] flex justify-between text-[11.5px] text-[#4b5563]"><span>{{ $money($c['accum']) }}</span><span>{{ $c['pct'] }}%</span></div>
                                    <div class="h-[6px] overflow-hidden rounded-[4px] bg-[#eef0f3]"><div class="h-full rounded-[4px]" style="width: {{ $c['pct'] }}%; background: {{ $c['pct'] >= 100 ? '#9ca3af' : '#f59e0b' }}"></div></div>
                                </td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold">{{ $money($c['book']) }}</td>
                                <td class="px-[18px] py-[11px]"><span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $bc }}">{{ $bl }}</span></td>
                            </tr>
                        @empty
                            <tr class="border-t border-[#f0f1f4]"><td colspan="6" class="px-[18px] py-[30px] text-center text-[#6b7280]">কোনো asset নেই। <a href="#" wire:click.prevent="openAdd">+ Add asset</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="acc-no-print flex min-w-0 flex-[1_1_360px] flex-col gap-[16px]">
            @if ($a)
                @php $c = AssetBook::calc($a); [$bc, $bl] = $badge($a); $years = AssetBook::years($a); $max = max(1, (float) $a->cost); @endphp
                <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white" wire:key="detail-{{ $a->id }}">
                    <div class="border-b border-[#f0f1f4] px-[18px] py-[16px]">
                        <div class="flex items-start justify-between gap-[8px]">
                            <div><h2 class="m-0 text-[17px] font-bold">{{ $a->name }}</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">{{ $a->code ?: '#' . $a->id }} · {{ $a->category ?: 'Other' }}</div></div>
                            <span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $bc }}">{{ $bl }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-[14px] px-[18px] py-[14px]">
                        <div class="grid grid-cols-2 gap-[10px]">
                            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#6b7280]">কেনা দাম</div><div class="mt-[2px] font-bold">{{ $money($a->cost) }}</div></div>
                            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#6b7280]">এখনকার দাম</div><div class="mt-[2px] font-bold text-[#4338ca]">{{ $money($c['book']) }}</div></div>
                            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#6b7280]">মাসিক অবচয়</div><div class="mt-[2px] font-bold">{{ $money($c['monthly']) }}</div></div>
                            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[11.5px] text-[#6b7280]">বাকি জীবন</div><div class="mt-[2px] font-bold">{{ $c['full'] ? 'Fully depreciated' : intdiv($c['left'], 12) . 'y ' . ($c['left'] % 12) . 'm' }}</div></div>
                        </div>
                        <div class="grid grid-cols-[100px_1fr] gap-x-[10px] gap-y-[6px] text-[13px]">
                            <span class="text-[#6b7280]">Method</span><span>Straight line · {{ rtrim(rtrim(number_format($a->useful_life_months / 12, 1), '0'), '.') }} years · {{ (float) $a->salvage_value > 0 ? 'salvage ' . $money($a->salvage_value) : 'no salvage' }}</span>
                            <span class="text-[#6b7280]">Bought</span><span>{{ $a->purchase_date->format('M Y') }}{{ $a->vendor ? ' · ' . $a->vendor : '' }}</span>
                            <span class="text-[#6b7280]">Location</span><span>{{ $a->assignee?->display_name ?? ($a->location ?: '—') }}</span>
                            <span class="text-[#6b7280]">Warranty</span><span>{{ $a->warranty_until ? ($a->warranty_until->isPast() ? 'Ended ' : 'Ends ') . $a->warranty_until->format('M Y') : '—' }}</span>
                        </div>
                        <div>
                            <div class="mb-[8px] text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]">Book value by year</div>
                            <div class="flex h-[70px] items-end gap-[6px]">
                                @foreach ($years as $y)
                                    <div class="flex flex-1 flex-col items-center gap-[4px]" title="{{ $y['year'] }} · {{ $money($y['book']) }}">
                                        <div class="w-full max-w-[26px] rounded-t-[4px]" style="height: {{ max(2, (int) round($y['book'] / $max * 60)) }}px; background: {{ $y['past'] ? '#6366f1' : '#c7d2fe' }}"></div>
                                        <div class="text-[10px] text-[#6b7280]">{{ substr((string) $y['year'], 2) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="mb-[6px] text-[12px] font-bold uppercase tracking-[0.04em] text-[#374151]">History</div>
                            @foreach ($this->history($a) as $h)
                                <div class="mb-[8px] flex items-start gap-[10px]">
                                    <span class="mt-[6px] h-[7px] w-[7px] flex-none rounded-full bg-[#a5b4fc]"></span>
                                    <div><div class="text-[13px]">{{ $h['text'] }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $h['meta'] }}</div></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if ($a->status !== 'disposed')
                        <div class="flex flex-wrap justify-end gap-[8px] border-t border-[#f0f1f4] bg-[#fcfcfd] px-[18px] py-[14px]">
                            <button type="button" wire:click="openEdit({{ $a->id }})" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Edit</button>
                            @if ($a->in_repair_since)
                                <button type="button" wire:click="backInUse({{ $a->id }})" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Back in use</button>
                            @endif
                            <button type="button" wire:click="openRepair({{ $a->id }})" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Record repair</button>
                            <button type="button" wire:click="openDispose({{ $a->id }})" class="rounded-[8px] border border-[#fecaca] bg-white px-[14px] py-[9px] text-[13px] text-[#b91c1c]">Sell / dispose</button>
                        </div>
                    @endif
                </section>
            @endif

            <section class="flex flex-col gap-[10px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <h2 class="m-0 text-[15px] font-bold">Reminders</h2>
                @forelse ($this->reminders as $rm)
                    <button type="button" wire:click="select({{ $rm['id'] }})" class="rounded-[10px] border px-[12px] py-[10px] text-left text-[13px] {{ $tone[$rm['tone']] }}"><b>{{ $rm['title'] }}</b><div class="mt-[2px] text-[12px]">{{ $rm['sub'] }}</div></button>
                @empty
                    <div class="text-[13px] text-[#6b7280]">কোনো reminder নেই। Asset edit করে service / tax token-এর তারিখ দিন।</div>
                @endforelse
            </section>
        </aside>
    </div>

    {{-- Add / edit asset --}}
    @if ($showForm)
        <x-accounts.modal :title="$editId ? 'Edit asset' : '+ Add asset'" close="$set('showForm', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Name</label><input type="text" wire:model="form.name" placeholder="যেমন: Juki sewing machines ×3" class="{{ $input }}">@error('form.name')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Category</label><select wire:model="form.category" class="{{ $input }}">@foreach (AssetBook::CATEGORIES as $cc)<option value="{{ $cc }}">{{ $cc }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Cost (৳)</label><input type="number" min="0" step="0.01" wire:model="form.cost" class="{{ $input }}">@error('form.cost')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Bought on</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="form.purchase_date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}">@error('form.purchase_date')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Life (years)</label><input type="number" min="0.5" step="0.5" wire:model="form.life_years" class="{{ $input }}">@error('form.life_years')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Salvage (৳)</label><input type="number" min="0" wire:model="form.salvage" placeholder="0" class="{{ $input }}">@error('form.salvage')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Bought from</label><input type="text" wire:model="form.vendor" class="{{ $input }}"></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Location</label><input type="text" wire:model="form.location" placeholder="যেমন: Mirpur showroom" class="{{ $input }}"></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Assigned to</label><select wire:model="form.assigned_profile_id" class="{{ $input }}"><option value="">—</option>@foreach ($this->people as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Warranty until</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="form.warranty_until" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}"></div>
            </div>
            <div class="flex flex-col gap-[8px]">
                <span class="text-[13px] font-semibold">Reminders (service, tax token, fitness…)</span>
                @foreach ($form['reminders'] ?? [] as $i => $rm)
                    <div class="flex gap-[8px]" wire:key="rm-{{ $i }}">
                        <input type="text" wire:model="form.reminders.{{ $i }}.label" placeholder="যেমন: AC service" class="{{ $input }} flex-1">
                        <input type="text" readonly placeholder="YYYY-MM-DD" wire:model="form.reminders.{{ $i }}.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }} w-[170px]">
                        <button type="button" wire:click="removeReminder({{ $i }})" class="px-[6px] text-[#9ca3af]" aria-label="Remove">×</button>
                    </div>
                @endforeach
                <a href="#" wire:click.prevent="addReminder" class="text-[12.5px]">+ Add reminder</a>
            </div>
            @unless ($editId)
                <div class="flex flex-col gap-[8px] rounded-[10px] bg-[#f9fafb] p-[12px]">
                    <span class="text-[13px] font-semibold">টাকা কিভাবে দেওয়া হয়েছে?</span>
                    <div class="flex flex-wrap gap-[6px]">
                        <x-accounts.chip :on="$form['mode'] === 'pay'" wire:click="$set('form.mode', 'pay')">Paid now</x-accounts.chip>
                        <x-accounts.chip :on="$form['mode'] === 'link'" wire:click="$set('form.mode', 'link')">Link a voucher</x-accounts.chip>
                        <x-accounts.chip :on="$form['mode'] === 'none'" wire:click="$set('form.mode', 'none')">Already in books</x-accounts.chip>
                    </div>
                    @if ($form['mode'] === 'pay')
                        <select wire:model="form.account_id" class="{{ $input }}">@foreach ($this->cashAccounts as $acc)<option value="{{ $acc['id'] }}">{{ $acc['name'] }} · {{ $money($acc['balance']) }}</option>@endforeach</select>
                        <span class="text-[12px] text-[#6b7280]">Draft payment হবে: Dr Fixed assets / Cr ঐ account।</span>
                    @elseif ($form['mode'] === 'link')
                        <input type="text" wire:model="form.voucher_ref" placeholder="Voucher ref, যেমন PV-0412" class="{{ $input }}">@error('form.voucher_ref')<span class="{{ $err }}">{{ $message }}</span>@enderror
                    @else
                        <div class="flex flex-col gap-[6px]"><label class="text-[12.5px] text-[#4b5563]">Depreciation already charged (৳)</label><input type="number" min="0" wire:model="form.accumulated" class="{{ $input }}">@error('form.accumulated')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                    @endif
                </div>
            @endunless
            <x-slot:footer>
                <button type="button" wire:click="$set('showForm', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveAsset" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    {{-- Repair --}}
    @if ($showRepair)
        <x-accounts.modal title="Record repair" close="$set('showRepair', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Cost (৳)</label><input type="number" min="0" step="0.01" wire:model="repair.amount" class="{{ $input }}">@error('repair.amount')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Date</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="repair.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}"></div>
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Paid from</label><select wire:model="repair.account_id" class="{{ $input }}">@foreach ($this->cashAccounts as $acc)<option value="{{ $acc['id'] }}">{{ $acc['name'] }} · {{ $money($acc['balance']) }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Note</label><input type="text" wire:model="repair.note" placeholder="কী ঠিক করা হলো" class="{{ $input }}"></div>
            </div>
            <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model="repair.still" class="accent-[#4f46e5]"> এখনো repair-এ আছে</label>
            <div class="text-[12.5px] text-[#6b7280]">Repairs &amp; maintenance-এ একটা expense draft হবে।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showRepair', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveRepair" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Create draft</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    {{-- Dispose --}}
    @if ($showDispose)
        @php $da = $this->assets->firstWhere('id', $dispose['id']); $gl = (float) ($dispose['proceeds'] ?: 0) - ($da?->bookValue() ?? 0); @endphp
        <x-accounts.modal title="Sell / dispose · {{ $da?->name }}" close="$set('showDispose', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Sold for (৳) · 0 = scrapped</label><input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="dispose.proceeds" class="{{ $input }}">@error('dispose.proceeds')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Date</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="dispose.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}"></div>
                @if ((float) ($dispose['proceeds'] ?: 0) > 0)
                    <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Received in</label><select wire:model="dispose.account_id" class="{{ $input }}">@foreach ($this->cashAccounts as $acc)<option value="{{ $acc['id'] }}">{{ $acc['name'] }}</option>@endforeach</select>@error('dispose.account_id')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                @endif
            </div>
            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px] text-[13px]">
                Book value {{ $money($da?->bookValue() ?? 0) }} ·
                @if ($gl >= 0)<b class="text-[#047857]">Gain {{ $money($gl) }}</b>@else<b class="text-[#b91c1c]">Loss {{ $money(abs($gl)) }}</b>@endif
            </div>
            <div class="text-[12.5px] text-[#6b7280]">Draft journal হবে — post হলে asset register থেকে বাদ যাবে।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showDispose', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveDispose" class="rounded-[9px] border-0 bg-[#b91c1c] px-[20px] py-[10px] text-[14px] font-bold text-white">Create draft</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @livewire('accounts.voucher-form')
</div>
