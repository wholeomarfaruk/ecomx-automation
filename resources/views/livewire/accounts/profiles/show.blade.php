{{-- Profile details — design: ProfileDetail.dc.html --}}
@php
    $drCr = fn ($v) => \App\Services\Profiles\ProfileLedger::drCr((float) $v);
    $p = $profile;
    $pos = $this->positions;
    $btn = 'text-[13px] px-[14px] py-[9px] border border-[#e5e7eb] rounded-[8px] bg-white text-[#374151]';
    $small = 'text-[13px] px-[10px] py-[7px] border border-[#d1d5db] rounded-[8px]';
    $input = 'w-full text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white focus:border-[#4f46e5] focus:ring-2 focus:ring-[#e0e7ff]';
    $payType = $this->payType();
    $collectType = $this->collectType();
    $hasAdmin = auth()->user()?->hasPanel('admin');
    $since = $p->roles->min('created_at') ?? $p->created_at;
    $roleColor = ['customer' => '#065f46', 'supplier' => '#075985', 'lender' => '#3730a3', 'owner' => '#92400e', 'employee' => '#115e59'];
@endphp

<div class="box-border flex w-full max-w-[1400px] flex-col gap-[16px] px-[28px] pb-[40px] pt-[20px]">

    @if (session('accounts.status'))
        <div class="acc-no-print rounded-[10px] border border-[#a7f3d0] bg-[#ecfdf5] px-[14px] py-[10px] text-[13px] text-[#065f46]">{{ session('accounts.status') }}</div>
    @endif

    <a href="{{ route('accounts.profiles.index') }}" wire:navigate class="acc-no-print flex w-max items-center gap-[6px] text-[13px]">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
        Master Profiles</a>

    {{-- Header --}}
    <section class="flex flex-wrap items-start justify-between gap-[18px] rounded-[14px] border border-[#e5e7eb] bg-white p-[20px]">
        <div class="flex min-w-0 flex-[1_1_420px] items-start gap-[16px]">
            <div class="flex h-[60px] w-[60px] flex-none items-center justify-center rounded-[14px] bg-[#eef2ff] text-[20px] font-bold text-[#3730a3]">{{ $p->initials() }}</div>
            <div class="flex min-w-0 flex-col gap-[6px]">
                <div class="flex flex-wrap items-center gap-[8px]">
                    <h1 class="m-0 text-[22px] font-bold">{{ $p->display_name }}</h1>
                    @foreach ($p->roles->sortBy(fn ($r) => array_search($r->role, \App\Models\MasterProfile::ROLES)) as $r)
                        <x-accounts.role-badge :role="$r->role" />
                    @endforeach
                    <span class="rounded-[6px] bg-[#f3f4f6] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#374151]">{{ $p->status === 'inactive' ? 'Inactive' : 'Active' }}</span>
                </div>
                <div class="text-[13px] text-[#4b5563]">{{ $p->code }} · {{ $p->typeLabel() }}@if ($p->contact_person) · Contact: {{ $p->contact_person }}@endif</div>
                <div class="flex flex-wrap gap-[14px] text-[13px] text-[#374151]">
                    @if ($p->phone)
                        <a href="tel:{{ $p->phone }}" class="flex items-center gap-[6px] !text-[#374151]"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"></path></svg>{{ $p->phone }}</a>
                    @endif
                    @if ($p->address || $p->city)
                        <span class="flex items-center gap-[6px]"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>{{ collect([$p->policeStation?->name, $p->city?->name])->filter()->implode(', ') ?: $p->address }}</span>
                    @endif
                    <span class="text-[#6b7280]">Since {{ $since?->format('M Y') }}</span>
                </div>
            </div>
        </div>
        <div class="acc-no-print flex flex-wrap gap-[8px]">
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" @click.outside="open = false" class="{{ $btn }}">Statement (PDF / SMS)</button>
                <div x-cloak x-show="open" x-transition class="absolute right-0 z-30 mt-1 w-52 overflow-hidden rounded-[10px] border border-[#e5e7eb] bg-white text-[13px] shadow-lg">
                    <a href="{{ route('accounts.profiles.statement', ['profile' => $p, 'from' => $from, 'to' => $to, 'role' => $ledgerRole ?: null]) }}" target="_blank" class="block px-[14px] py-[9px] !text-[#374151] hover:bg-[#f9fafb]">PDF / Print statement</a>
                    <button type="button" wire:click="sendStatement" @click="open = false" class="block w-full px-[14px] py-[9px] text-left text-[#374151] hover:bg-[#f9fafb]">Send balance by SMS</button>
                </div>
            </div>
            <a href="{{ route('accounts.profiles.edit', $p) }}" wire:navigate class="{{ $btn }} !text-[#374151]">Edit</a>
            @if ($payType)
                <button type="button" class="{{ $btn }}" wire:click="openVoucher('{{ $payType }}', {{ $p->id }})">টাকা দিন · Pay</button>
            @endif
            @if ($collectType)
                <button type="button" wire:click="openVoucher('{{ $collectType }}', {{ $p->id }})" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">টাকা নিন · Collect</button>
            @endif
        </div>
    </section>

    {{-- Position --}}
    @if ($pos['cards'] || $pos['net'])
        <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(260px,1fr))]">
            @foreach ($pos['cards'] as $c)
                <x-accounts.stat :label="$c['label']" :value="$c['value']" :color="$c['color']" :sub="$c['sub']" />
            @endforeach
            @if ($pos['net'])
                <div class="flex items-center justify-between gap-[12px] rounded-[14px] border border-[#c7d2fe] bg-[#eef2ff] px-[18px] py-[16px]">
                    <div>
                        <div class="text-[12.5px] text-[#3730a3]">Net position</div>
                        <div class="mt-[6px] text-[24px] font-bold text-[#312e81]">{{ $pos['net']['value'] }}</div>
                        <div class="mt-[4px] text-[12px] text-[#4338ca]">{{ $pos['net']['sub'] }}</div>
                    </div>
                    @if ($pos['net']['setoff'])
                        <button type="button" wire:click="$set('confirmSetOff', true)" class="acc-no-print whitespace-nowrap rounded-[8px] border border-[#a5b4fc] bg-white px-[12px] py-[9px] text-[12.5px] font-semibold text-[#3730a3]">Set-off {{ $money($pos['net']['setoff']) }}</button>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div class="flex flex-wrap items-start gap-[16px]">

        {{-- Left: tabs --}}
        <section class="min-w-0 flex-[999_1_560px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div role="tablist" aria-label="Profile sections" class="acc-no-print flex gap-[2px] overflow-x-auto border-b border-[#e5e7eb] px-[12px]">
                @foreach ($this::TABS as $key => $label)
                    <button type="button" role="tab" wire:click="setTab('{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                            @class(['whitespace-nowrap border-0 border-b-[3px] bg-transparent px-[12px] pb-[12px] pt-[14px] text-[13.5px]',
                                    'border-[#4f46e5] font-bold text-[#312e81]' => $tab === $key, 'border-transparent text-[#4b5563]' => $tab !== $key])>{{ $label }}</button>
                @endforeach
            </div>

            @if ($tab === 'ledger')
                @php $st = $this->statement; $wa = $this->whatsappUrl(); @endphp
                <div class="acc-no-print flex flex-wrap items-center justify-between gap-[10px] px-[18px] py-[14px]">
                    <div class="flex flex-wrap items-center gap-[8px]">
                        <label for="led-from" class="text-[13px] text-[#4b5563]">From</label>
                        <input id="led-from" type="date" wire:model.live="from" class="{{ $small }}">
                        <label for="led-to" class="text-[13px] text-[#4b5563]">To</label>
                        <input id="led-to" type="date" wire:model.live="to" class="{{ $small }}">
                        <label for="led-role" class="text-[13px] text-[#4b5563]">Role</label>
                        <select id="led-role" wire:model.live="ledgerRole" class="{{ $small }} bg-white py-[8px] pr-[30px]">
                            <option value="">All roles</option>
                            @foreach ($p->roles->sortBy(fn ($r) => array_search($r->role, \App\Models\MasterProfile::ROLES)) as $r)
                                <option value="{{ $r->role }}">{{ ucfirst($r->role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-[8px]">
                        <button type="button" onclick="window.print()" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[8px] text-[13px] text-[#374151]">Print</button>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" @click.outside="open = false" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[8px] text-[13px] text-[#374151]">Send via SMS / WhatsApp</button>
                            <div x-cloak x-show="open" class="absolute right-0 z-30 mt-1 w-44 overflow-hidden rounded-[10px] border border-[#e5e7eb] bg-white text-[13px] shadow-lg">
                                <button type="button" wire:click="sendStatement" @click="open = false" class="block w-full px-[14px] py-[9px] text-left text-[#374151] hover:bg-[#f9fafb]">SMS</button>
                                @if ($wa)
                                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="block px-[14px] py-[9px] !text-[#374151] hover:bg-[#f9fafb]">WhatsApp</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="from,to,ledgerRole">
                    <table class="min-w-[760px] text-[13px]">
                        <thead>
                            <tr class="bg-[#f9fafb] text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                                <th class="px-[18px] py-[10px] font-semibold">Date</th>
                                <th class="px-[8px] py-[10px] font-semibold">Voucher</th>
                                <th class="px-[8px] py-[10px] font-semibold">Particulars</th>
                                <th class="px-[8px] py-[10px] text-right font-semibold">Debit</th>
                                <th class="px-[8px] py-[10px] text-right font-semibold">Credit</th>
                                <th class="px-[18px] py-[10px] text-right font-semibold">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-[#f0f1f4]">
                                <td class="whitespace-nowrap px-[18px] py-[10px] text-[#374151]">{{ $from ? \Carbon\Carbon::parse($from)->format('d M') : '—' }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[10px] font-semibold text-[#4338ca]">OB</td>
                                <td class="px-[8px] py-[10px]"><div>Opening balance</div><div class="text-[11.5px] text-[#6b7280]"></div></td>
                                <td class="px-[8px] py-[10px]"></td><td class="px-[8px] py-[10px]"></td>
                                <td class="whitespace-nowrap px-[18px] py-[10px] text-right font-semibold"><span class="{{ $st['opening'] >= 0 ? 'text-[#047857]' : 'text-[#b91c1c]' }}">{{ $drCr($st['opening']) }}</span></td>
                            </tr>
                            @foreach ($st['rows'] as $l)
                                <tr class="border-t border-[#f0f1f4]">
                                    <td class="whitespace-nowrap px-[18px] py-[10px] text-[#374151]">{{ $l['date']->format('d M') }}</td>
                                    <td class="whitespace-nowrap px-[8px] py-[10px]"><a href="#" wire:click.prevent="showVoucherEntry({{ $l['entry_id'] }})" class="font-semibold">{{ $l['ref'] }}</a></td>
                                    <td class="px-[8px] py-[10px]"><div>{{ $l['text'] }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $l['sub'] }}</div></td>
                                    <td class="whitespace-nowrap px-[8px] py-[10px] text-right">{{ $l['debit'] > 0 ? $money($l['debit']) : '' }}</td>
                                    <td class="whitespace-nowrap px-[8px] py-[10px] text-right">{{ $l['credit'] > 0 ? $money($l['credit']) : '' }}</td>
                                    <td class="whitespace-nowrap px-[18px] py-[10px] text-right font-semibold"><span class="{{ $l['balance'] >= 0 ? 'text-[#047857]' : 'text-[#b91c1c]' }}">{{ $drCr($l['balance']) }}</span></td>
                                </tr>
                            @endforeach
                            @if (! $st['rows'])
                                <tr class="border-t border-[#f0f1f4]"><td colspan="6" class="px-[18px] py-[18px] text-center text-[#6b7280]">এই সময়ে কোনো লেনদেন নেই।</td></tr>
                            @endif
                            <tr class="border-t-2 border-[#e5e7eb] bg-[#f9fafb] font-bold">
                                <td class="px-[18px] py-[12px]" colspan="3">Closing balance · {{ $to ? \Carbon\Carbon::parse($to)->format('d M Y') : today()->format('d M Y') }}</td>
                                <td class="px-[8px] py-[12px] text-right">{{ $money($st['debit']) }}</td>
                                <td class="px-[8px] py-[12px] text-right">{{ $money($st['credit']) }}</td>
                                <td class="px-[18px] py-[12px] text-right {{ $st['closing'] >= 0 ? 'text-[#047857]' : 'text-[#b91c1c]' }}">{{ $drCr($st['closing']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-[18px] pb-[14px] pt-[10px] text-[12px] text-[#6b7280]">Dr = উনি আমাদের কাছে দেনা (পাবো) · Cr = আমরা ওনার কাছে দেনা (দিবো)</div>
            @endif

            @if ($tab === 'kisti')
                <div class="flex flex-col gap-[18px] px-[18px] py-[16px]">
                    @forelse ($this->plans as $plan)
                        <div class="flex flex-col gap-[14px]">
                            <div class="flex flex-wrap items-center justify-between gap-[10px]">
                                <div>
                                    <div class="font-bold">{{ $plan['title'] }}</div>
                                    <div class="text-[12.5px] text-[#6b7280]">{{ $plan['sub'] }}</div>
                                </div>
                                <span class="rounded-[6px] bg-[#e0e7ff] px-[9px] py-[3px] text-[12px] font-semibold text-[#3730a3]">{{ $plan['badge'] }}</span>
                            </div>
                            <div class="grid gap-[8px] [grid-template-columns:repeat(auto-fill,minmax(120px,1fr))]">
                                @foreach ($plan['boxes'] as $k)
                                    <div @class(['rounded-[10px] border p-[10px]',
                                        'border-[#a7f3d0] bg-[#ecfdf5] text-[#064e3b]' => $k['state'] === 'paid',
                                        'border-[#fde68a] bg-[#fffbeb] text-[#78350f]' => $k['state'] === 'next',
                                        'border-[#e5e7eb] bg-white text-[#374151]' => $k['state'] === 'later'])>
                                        <div class="text-[11.5px] opacity-80">Kisti {{ $k['no'] }} · {{ $k['date'] }}</div>
                                        <div class="mt-[2px] font-bold">{{ $k['amount'] }}</div>
                                        <div class="mt-[2px] text-[11.5px]">{{ $k['note'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-[13px] text-[#6b7280]">কোনো কিস্তি plan নেই।</div>
                    @endforelse
                    @if ($p->hasRole('customer') || $p->hasRole('supplier'))
                        <button type="button" wire:click="openPlanForm" class="acc-no-print self-start rounded-[8px] border border-dashed border-[#c7d2fe] bg-white px-[12px] py-[8px] text-[13px] font-semibold text-[#4338ca]">+ New kisti plan</button>
                    @elseif ($p->hasRole('lender'))
                        <a href="{{ route('accounts.profiles.lenders', ['new' => 1]) }}" wire:navigate class="acc-no-print self-start rounded-[8px] border border-dashed border-[#c7d2fe] bg-white px-[12px] py-[8px] text-[13px] font-semibold">+ New loan</a>
                    @endif
                </div>
            @endif

            @if ($tab === 'sales')
                <div class="overflow-x-auto">
                    <table class="min-w-[600px] text-[13px]">
                        <thead><tr class="bg-[#f9fafb] text-left text-[11.5px] uppercase text-[#6b7280]"><th class="px-[18px] py-[10px] font-semibold">Date</th><th class="px-[8px] py-[10px] font-semibold">Invoice</th><th class="px-[8px] py-[10px] font-semibold">Items</th><th class="px-[8px] py-[10px] text-right font-semibold">Amount</th><th class="px-[18px] py-[10px] font-semibold">Payment</th></tr></thead>
                        <tbody>
                            @forelse ($this->sales as $o)
                                <tr class="border-t border-[#f0f1f4]">
                                    <td class="px-[18px] py-[10px]">{{ ($o->placed_at ?? $o->created_at)?->format('d M') }}</td>
                                    <td class="px-[8px] py-[10px]">
                                        @if ($hasAdmin)<a href="{{ route('admin.sales.orders.show', $o->id) }}">#{{ $o->id }}</a>@else #{{ $o->id }} @endif
                                    </td>
                                    <td class="px-[8px] py-[10px]">{{ rtrim(rtrim(number_format((float) $o->items_sum_quantity, 3), '0'), '.') }} pcs · {{ $o->items_count }} items</td>
                                    <td class="px-[8px] py-[10px] text-right">{{ $money($o->total_amount) }}</td>
                                    <td class="px-[18px] py-[10px]">{{ (float) $o->due_amount > 0.5 ? 'Due ' . $money($o->due_amount) : ($o->payment_status?->label() ?? ucfirst((string) $o->payment_status?->value)) }}</td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#f0f1f4]"><td colspan="5" class="px-[18px] py-[18px] text-center text-[#6b7280]">কোনো বিক্রি নেই।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($tab === 'purchases')
                <div class="overflow-x-auto">
                    <table class="min-w-[600px] text-[13px]">
                        <thead><tr class="bg-[#f9fafb] text-left text-[11.5px] uppercase text-[#6b7280]"><th class="px-[18px] py-[10px] font-semibold">Date</th><th class="px-[8px] py-[10px] font-semibold">Purchase</th><th class="px-[8px] py-[10px] font-semibold">Items</th><th class="px-[8px] py-[10px] text-right font-semibold">Amount</th><th class="px-[18px] py-[10px] font-semibold">Due</th></tr></thead>
                        <tbody>
                            @forelse ($this->purchases as $r)
                                <tr class="border-t border-[#f0f1f4]">
                                    <td class="px-[18px] py-[10px]">{{ $r['date'] }}</td>
                                    <td class="px-[8px] py-[10px]">@if ($hasAdmin && $p->supplier)<a href="{{ route('admin.purchase.suppliers.ledger', $p->supplier->id) }}">{{ $r['ref'] }}</a>@else {{ $r['ref'] }} @endif</td>
                                    <td class="px-[8px] py-[10px]">{{ $r['items'] }}</td>
                                    <td class="px-[8px] py-[10px] text-right">{{ $r['amount'] }}</td>
                                    <td class="px-[18px] py-[10px]">{{ $r['due'] }}</td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#f0f1f4]"><td colspan="5" class="px-[18px] py-[18px] text-center text-[#6b7280]">কোনো কেনাকাটা নেই।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($tab === 'docs')
                <div class="flex flex-col gap-[8px] px-[18px] py-[16px]">
                    @forelse ($p->documents()->latest()->get() as $d)
                        <div wire:key="doc-{{ $d->id }}" class="flex items-center justify-between rounded-[10px] border border-[#eef0f3] px-[12px] py-[10px]">
                            <div><div class="font-semibold">{{ $d->title }}</div><div class="text-[12px] text-[#6b7280]">{{ $d->summary() }}</div></div>
                            <div class="flex items-center gap-[12px]">
                                <a href="{{ route('accounts.profiles.documents.show', $d) }}" target="_blank">View</a>
                                <button type="button" wire:click="deleteDocument({{ $d->id }})" wire:confirm="Remove this document?" class="text-[12.5px] text-[#b91c1c]">Remove</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-[13px] text-[#6b7280]">কোনো document নেই।</div>
                    @endforelse
                    <div x-data="{ open: false }" class="flex flex-col gap-[8px]">
                        <button type="button" @click="open = !open" class="self-start rounded-[8px] border border-dashed border-[#c7d2fe] bg-white px-[12px] py-[8px] text-[13px] font-semibold text-[#4338ca]">+ Upload document</button>
                        <div x-show="open" x-cloak class="grid gap-[10px] rounded-[10px] border border-[#eef0f3] p-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                            <input type="text" wire:model="docTitle" placeholder="Title (e.g. Trade license)" class="{{ $input }}">
                            <input type="date" wire:model="docExpires" title="Expires on" class="{{ $input }}">
                            <input type="file" wire:model="docFile" accept=".pdf,image/*" class="text-[13px]">
                            <button type="button" wire:click="uploadDocument" wire:loading.attr="disabled" class="rounded-[9px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Upload</button>
                            @error('docFile') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            @endif

            @if ($tab === 'activity')
                <div class="flex flex-col gap-[12px] px-[18px] py-[16px]">
                    <label for="note-in" class="text-[13px] font-semibold">Add note</label>
                    <textarea id="note-in" rows="2" wire:model="note" wire:keydown.ctrl.enter="addNote" placeholder="যেমন: ২০ তারিখে বাকি দেবে বলেছে"
                              class="resize-y rounded-[9px] border border-[#d1d5db] px-[12px] py-[10px] text-[13px]"></textarea>
                    <div class="flex items-center justify-between">
                        @error('note') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @else <span></span> @enderror
                        <button type="button" wire:click="addNote" class="rounded-[8px] bg-[#4f46e5] px-[14px] py-[7px] text-[13px] font-semibold text-white">Save note</button>
                    </div>
                    @forelse ($this->activity as $a)
                        <div class="flex items-start gap-[10px]">
                            <span class="mt-[6px] h-[8px] w-[8px] flex-none rounded-full {{ $a->type === 'note' ? 'bg-[#f59e0b]' : 'bg-[#a5b4fc]' }}"></span>
                            <div><div class="text-[13px]">{{ $a->text }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $a->created_at?->format('d M') }} · {{ $a->user?->name ?? 'System' }}</div></div>
                        </div>
                    @empty
                        <div class="text-[13px] text-[#6b7280]">এখনো কিছু নেই।</div>
                    @endforelse
                </div>
            @endif
        </section>

        {{-- Right: details --}}
        <aside class="flex min-w-0 flex-[1_1_300px] flex-col gap-[14px]">
            <section class="flex flex-col gap-[10px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <h2 class="m-0 text-[15px] font-bold">Details</h2>
                @php $ob = \App\Models\JournalEntry::where('source_type', \App\Models\MasterProfile::class)->where('source_id', $p->id)->where('transaction_type', 'opening_balance')->with('lines')->first(); @endphp
                <div class="grid grid-cols-[110px_1fr] gap-x-[10px] gap-y-[8px] text-[13px]">
                    <span class="text-[#6b7280]">Alt phone</span><span>{{ $p->alt_phone ?: '—' }}</span>
                    <span class="text-[#6b7280]">Email</span><span class="break-all">{{ $p->email ?: '—' }}</span>
                    <span class="text-[#6b7280]">Address</span><span>{{ collect([$p->address, $p->policeStation?->name, $p->city?->name])->filter()->implode(', ') ?: '—' }}</span>
                    <span class="text-[#6b7280]">NID</span><span>{{ $p->national_id ?: '—' }}</span>
                    <span class="text-[#6b7280]">TIN</span><span>{{ $p->tax_number ?: '—' }}</span>
                    <span class="text-[#6b7280]">BIN (VAT)</span><span>{{ $p->vat_number ?: '—' }}</span>
                    <span class="text-[#6b7280]">Trade license</span><span>{{ $p->trade_license ?: '—' }}</span>
                    <span class="text-[#6b7280]">Opening bal.</span><span>{{ $ob ? $money($ob->lines->sum('debit')) . ($ob->purpose === 'opening_receive' ? ' Dr' : ' Cr') . ' · ' . $ob->entry_date->format('d M Y') : '—' }}</span>
                </div>
            </section>
            <section class="flex flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <h2 class="m-0 text-[15px] font-bold">Role settings</h2>
                @foreach ($p->roles->sortBy(fn ($r) => array_search($r->role, \App\Models\MasterProfile::ROLES)) as $r)
                    @php
                        $s = $r->settings ?? [];
                        $lines = match ($r->role) {
                            'customer' => ['Credit limit' => isset($s['credit_limit']) && $s['credit_limit'] ? $money($s['credit_limit']) : '—', 'Terms' => ($s['terms_days'] ?? 'cash') === 'cash' ? 'Cash only' : ($s['terms_days'] . ' days'), 'Price group' => ucfirst($s['price_group'] ?? 'retail'), 'Kisti' => ! empty($s['allow_kisti']) ? 'Allowed' : 'No'],
                            'supplier' => ['Category' => $s['category'] ?? '—', 'Terms' => \App\Livewire\Accounts\Profiles\Suppliers::TERMS[$s['terms'] ?? 'cash'] ?? '—', 'Pay to' => $s['payout_account'] ?? '—'],
                            'lender'   => ['Type' => ['bank' => 'Bank / NBFI', 'business' => 'Business', 'personal' => 'Personal'][$s['lender_type'] ?? ''] ?? '—', 'Loans' => $p->loans()->count()],
                            'owner'    => ['Profit share' => isset($s['share_percent']) ? $s['share_percent'] . '%' : '—'],
                            'employee' => ['Designation' => $s['designation'] ?? '—', 'Department' => $s['department'] ?? '—', 'Salary' => isset($s['salary']) ? $money($s['salary']) : '—', 'Paid via' => ucfirst($s['pay_via'] ?? '—'), 'Login' => $p->user?->email ?? 'No access'],
                            default    => [],
                        };
                    @endphp
                    <div class="grid grid-cols-[110px_1fr] gap-x-[10px] gap-y-[6px] rounded-[10px] border border-[#eef0f3] px-[12px] py-[10px] text-[13px]">
                        <span class="col-span-full font-semibold" style="color: {{ $roleColor[$r->role] ?? '#374151' }}">{{ ucfirst($r->role) }}</span>
                        @foreach ($lines as $k => $v)
                            <span class="text-[#6b7280]">{{ $k }}</span><span>{{ $v }}</span>
                        @endforeach
                    </div>
                @endforeach
                @php $missing = collect(\App\Models\MasterProfile::ROLES)->reject(fn ($r) => $p->hasRole($r)); @endphp
                @if ($missing->isNotEmpty())
                    <div x-data="{ open: false }" class="relative">
                        <a href="#" @click.prevent="open = !open" class="text-[13px] font-semibold">+ Add role ({{ $missing->map(fn ($r) => ucfirst($r))->implode(' / ') }})</a>
                        <div x-cloak x-show="open" @click.outside="open = false" class="absolute left-0 z-30 mt-1 w-44 overflow-hidden rounded-[10px] border border-[#e5e7eb] bg-white text-[13px] shadow-lg">
                            @foreach ($missing as $r)
                                <a href="{{ route('accounts.profiles.edit', ['profile' => $p, 'role' => $r]) }}" wire:navigate class="block px-[14px] py-[8px] !text-[#374151] hover:bg-[#f9fafb]">{{ ucfirst($r) }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
            <section class="flex flex-col gap-[10px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <h2 class="m-0 text-[15px] font-bold">Tags</h2>
                <div class="flex flex-wrap gap-[6px]">
                    @forelse ($p->tags ?? [] as $t)
                        <span class="rounded-[6px] bg-[#f3f4f6] px-[9px] py-[3px] text-[12px] text-[#374151]">{{ $t }}</span>
                    @empty
                        <a href="{{ route('accounts.profiles.edit', $p) }}" wire:navigate class="text-[13px]">+ Add tags</a>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    {{-- Set-off confirm --}}
    @if ($confirmSetOff && ($pos['net']['setoff'] ?? null))
        <x-accounts.modal title="Set-off" close="$set('confirmSetOff', false)">
            <div class="text-[13.5px] text-[#374151]">
                {{ $p->display_name }}-এর কাছে পাবো {{ $money($this->balances->receivable) }}, আর ওনাকে দিবো {{ $money($this->balances->payable) }}।
                একটি contra voucher ({{ $money($pos['net']['setoff']) }}) post হবে: Dr Payable / Cr Receivable।
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('confirmSetOff', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[18px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="setOff" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#4f46e5] px-[22px] py-[10px] text-[14px] font-bold text-white">Post set-off</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    {{-- Voucher lines --}}
    @if ($voucherEntryId && ($e = $this->voucherEntry))
        <x-accounts.modal :title="$e->entry_number . ' · ' . ($e->transaction_type?->label() ?? '')" close="$set('voucherEntryId', null)" width="max-w-[640px]">
            <div class="text-[13px] text-[#4b5563]">{{ $e->entry_date->format('d M Y') }} · {{ $e->description }} · <span class="font-semibold">{{ $e->status->label() }}</span>@if ($e->postedBy) by {{ $e->postedBy->name }}@endif</div>
            <table class="text-[13px]">
                <thead><tr class="text-left text-[11.5px] uppercase text-[#6b7280]"><th class="py-[6px] font-semibold">Account</th><th class="py-[6px] text-right font-semibold">Debit</th><th class="py-[6px] text-right font-semibold">Credit</th></tr></thead>
                <tbody>
                    @foreach ($e->lines as $ln)
                        <tr class="border-t border-[#f0f1f4]"><td class="py-[7px]">{{ $ln->account?->code }} · {{ $ln->account?->name }}@if ($ln->memo)<div class="text-[11.5px] text-[#6b7280]">{{ $ln->memo }}</div>@endif</td>
                            <td class="py-[7px] text-right">{{ (float) $ln->debit > 0 ? $money($ln->debit) : '' }}</td><td class="py-[7px] text-right">{{ (float) $ln->credit > 0 ? $money($ln->credit) : '' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </x-accounts.modal>
    @endif

    {{-- New kisti plan --}}
    @if ($showPlanForm)
        <x-accounts.modal title="নতুন কিস্তি plan" close="$set('showPlanForm', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                @if ($p->hasRole('customer') && $p->hasRole('supplier'))
                    <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">For</label>
                        <select wire:model="plan.role" class="{{ $input }}"><option value="customer">Customer (we collect)</option><option value="supplier">Supplier (we pay)</option></select></div>
                @endif
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">মোট (৳)</label><input type="number" min="0" step="0.01" wire:model="plan.total" class="{{ $input }}">
                    @error('plan.total') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Invoice / reference</label><input type="text" wire:model="plan.reference" placeholder="INV-1188" class="{{ $input }}"></div>
            </div>
            <div class="flex flex-wrap gap-[6px]">
                @foreach (\App\Services\Profiles\InstallmentScheduler::FREQUENCIES as $f)
                    <x-accounts.chip :on="$plan['frequency'] === $f" wire:click="$set('plan.frequency', '{{ $f }}')">{{ ucfirst($f) }}</x-accounts.chip>
                @endforeach
            </div>
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-3">
                @if ($plan['frequency'] === 'custom')
                    <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Days from start</label><input type="text" wire:model="plan.offsets" placeholder="15, 30, 45" class="{{ $input }}">
                        @error('plan.offsets') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror</div>
                @else
                    <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Number of kisti</label><input type="number" min="1" wire:model="plan.count" class="{{ $input }}">
                        @error('plan.count') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror</div>
                @endif
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Start date</label><input type="date" wire:model="plan.start_date" class="{{ $input }}"></div>
                <div class="flex flex-col gap-[6px] {{ $plan['frequency'] === 'custom' ? 'sm:col-span-3' : '' }}"><label class="text-[13px] font-semibold">বেশি দিলে (support)</label>
                    <select wire:model="plan.support_rule" class="{{ $input }}"><option value="next_kisti">পরের কিস্তি কমবে</option><option value="principal">শেষের কিস্তি কমবে</option></select></div>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showPlanForm', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[18px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="savePlan" class="rounded-[9px] border-0 bg-[#4f46e5] px-[22px] py-[10px] text-[14px] font-bold text-white">Create plan</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @include('livewire.accounts.profiles.partials.voucher-modal')
</div>
