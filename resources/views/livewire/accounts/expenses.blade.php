{{-- Expenses — design: Expenses.dc.html --}}
@php
    $s = $this->summary;
    $cats = $this->cats;
    $rows = $this->rows;
    $monthLabel = $this->start()->format('F');
    $catName = $cat ? ($this->categories->firstWhere('id', $cat)?->name ?? '') : '';
    $field = 'text-[13px] px-[10px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $err = 'text-[12px] text-[#b91c1c]';
    $barColor = $s['pct'] > 100 ? '#dc2626' : ($s['pct'] > 80 ? '#f59e0b' : '#10b981');
    $ordinal = fn ($d) => $d . (in_array($d % 100, [11, 12, 13]) ? 'th' : ([1 => 'st', 2 => 'nd', 3 => 'rd'][$d % 10] ?? 'th'));
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]">

    <x-accounts.page-head title="খরচ · Expenses" subtitle="ব্যবসা চালাতে কোথায় কত খরচ হচ্ছে, budget-এর মধ্যে আছে কিনা">
        <label for="ex-month" class="text-[13px] text-[#4b5563]">Month</label>
        <select id="ex-month" wire:model.live="month" class="rounded-[9px] border border-[#d1d5db] bg-white py-[8px] pl-[10px] pr-[30px] text-[13px]">
            @foreach ($this->months as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
        </select>
        <a href="{{ route('accounts.settings', ['section' => 'categories']) }}" wire:navigate class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] !text-[#374151]">Edit budgets</a>
        <a href="#quick-add" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">+ Expense</a>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat :label="$this->start()->isSameMonth(today()) ? 'এই মাসের খরচ' : $monthLabel . '-এর খরচ'" :value="$money($s['posted'])" color="#b91c1c" :sub="'Posted · ' . $money($s['draft']) . ' in draft'" />
        <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
            <div class="text-[12.5px] text-[#4b5563]">Budget</div>
            <div class="mt-[6px] text-[24px] font-bold">{{ $money($s['budget']) }}</div>
            <div class="mt-[6px] flex items-center gap-[8px]">
                <div class="h-[6px] flex-1 overflow-hidden rounded-[4px] bg-[#eef0f3]"><div class="h-full rounded-[4px]" style="width: {{ min($s['pct'], 100) }}%; background: {{ $barColor }}"></div></div>
                <span class="text-[12px] text-[#6b7280]">{{ $s['pct'] }}% used · {{ $s['days'] }}</span>
            </div>
        </div>
        <x-accounts.stat label="Budget পার" :value="$s['over'] . ' category'" color="#b45309" :sub="$s['overNote']" />
        <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
            <div class="text-[12.5px] text-[#4b5563]">গত মাস · {{ $s['lastLabel'] }}</div>
            <div class="mt-[6px] text-[24px] font-bold">{{ $money($s['last']) }}</div>
            <div class="mt-[6px] flex h-[26px] items-end gap-[5px]">
                @foreach ($s['trend'] as $t)
                    <div class="flex-1 rounded-t-[3px]" style="height: {{ $t['h'] }}px; background: {{ $t['now'] ? '#4f46e5' : '#c7d2fe' }}" title="{{ $t['label'] }} {{ $money($t['value']) }}"></div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-start gap-[16px]">

        <div class="flex min-w-0 flex-[999_1_640px] flex-col gap-[16px]">

            {{-- Categories --}}
            <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
                <div class="flex items-center justify-between border-b border-[#f0f1f4] px-[18px] py-[14px]">
                    <h2 class="m-0 text-[15px] font-bold">Category অনুযায়ী · vs budget</h2>
                    <span class="text-[12.5px] text-[#6b7280]">Posted + draft</span>
                </div>
                <div class="flex flex-col px-[18px] pb-[14px] pt-[6px]">
                    @forelse ($cats as $c)
                        @php $p = $c['pct']; @endphp
                        <button type="button" wire:key="cat-{{ $c['id'] }}" wire:click="pickCat({{ $c['id'] }})" @class([
                            'mx-[-10px] rounded-[8px] border-0 border-t border-[#f0f1f4] px-[10px] py-[12px] text-left text-[#111827]',
                            'bg-[#eef2ff]' => $cat === $c['id'], 'bg-transparent' => $cat !== $c['id'],
                        ])>
                            <div class="flex justify-between gap-[10px] text-[13px]">
                                <span class="font-semibold">{{ $c['name'] }}</span>
                                <span><b @class(['text-[#b91c1c]' => $c['over'], 'text-[#111827]' => ! $c['over']])>{{ $money($c['used']) }}</b> <span class="text-[#6b7280]">/ {{ $c['budget'] > 0 ? $money($c['budget']) : 'no budget' }}</span></span>
                            </div>
                            <div class="mt-[6px] h-[8px] overflow-hidden rounded-[5px] bg-[#eef0f3]"><div class="h-full rounded-[5px]" style="width: {{ $c['budget'] > 0 ? min($p, 100) : 0 }}%; background: {{ $c['over'] ? '#dc2626' : ($p > 85 ? '#f59e0b' : '#6366f1') }}"></div></div>
                            <div @class(['mt-[4px] text-[11.5px]', 'font-semibold text-[#b91c1c]' => $c['over'], 'text-[#6b7280]' => ! $c['over']])>
                                @if ($c['over'])
                                    {{ $money($c['used'] - $c['budget']) }} over budget
                                @else
                                    {{ $c['draft'] ? $money($c['draft']) . ' in draft · ' : '' }}{{ $c['budget'] > 0 ? $money($c['budget'] - $c['used']) . ' left' : 'Budget দেওয়া নেই' }}
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="py-[24px] text-center text-[13px] text-[#6b7280]">এই মাসে কোনো খরচ নেই। <a href="{{ route('accounts.settings', ['section' => 'categories']) }}" wire:navigate>Budget দিন</a></div>
                    @endforelse
                </div>
            </section>

            {{-- List --}}
            <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
                <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                    <div>
                        <h2 class="m-0 text-[15px] font-bold">{{ $cat ? $catName . ' · ' . $monthLabel : 'সব খরচ · ' . $monthLabel }}</h2>
                        <div class="mt-[2px] text-[12.5px] text-[#6b7280]">Category-তে click করলে filter হবে</div>
                    </div>
                    @if ($cat)
                        <button type="button" wire:click="$set('cat', 0)" class="rounded-[20px] border border-[#e5e7eb] bg-white px-[12px] py-[6px] text-[12.5px] text-[#374151]">Clear filter ×</button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[760px] text-[13px]">
                        <thead>
                            <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                                <th class="py-[10px] pl-[18px] pr-[8px] font-semibold">Date</th>
                                <th class="px-[8px] py-[10px] font-semibold">Expense</th>
                                <th class="px-[8px] py-[10px] font-semibold">Paid from</th>
                                <th class="px-[8px] py-[10px] font-semibold">Receipt</th>
                                <th class="px-[8px] py-[10px] text-right font-semibold">Amount</th>
                                <th class="px-[18px] py-[10px] font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr wire:key="ex-{{ $r['id'] }}" class="border-t border-[#f0f1f4]">
                                    <td class="whitespace-nowrap py-[11px] pl-[18px] pr-[8px] text-[#374151]">{{ $r['date'] }}</td>
                                    <td class="px-[8px] py-[11px]"><div class="font-semibold">{{ $r['text'] }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $r['ref'] }} · {{ $r['cat'] }} · {{ $r['to'] }}</div></td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-[#374151]">{{ $r['acc'] }}</td>
                                    <td class="px-[8px] py-[11px]">@if ($r['receipt'])<span class="text-[12px] text-[#047857]">Attached</span>@else<span class="text-[12px] font-semibold text-[#b45309]">Missing</span>@endif</td>
                                    <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold text-[#b91c1c]">{{ $money($r['amount']) }}</td>
                                    <td class="px-[18px] py-[11px]">
                                        @if ($r['posted'])
                                            <span class="whitespace-nowrap rounded-[6px] bg-[#d1fae5] px-[8px] py-[2px] text-[11.5px] font-semibold text-[#065f46]">Posted</span>
                                        @else
                                            <a href="{{ route('accounts.posting') }}" wire:navigate class="whitespace-nowrap rounded-[6px] bg-[#fef3c7] px-[8px] py-[2px] text-[11.5px] font-semibold !text-[#92400e]">{{ $r['status']->label() }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#f0f1f4]"><td colspan="6" class="px-[18px] py-[30px] text-center text-[#6b7280]">এই মাসে কোনো খরচ নেই।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="flex min-w-0 flex-[1_1_340px] flex-col gap-[16px]">
            {{-- Quick add --}}
            <section id="quick-add" class="flex scroll-mt-[20px] flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <h2 class="m-0 text-[15px] font-bold">দ্রুত খরচ লিখুন</h2>
                <div class="flex flex-col gap-[6px]"><label for="qa-cat" class="text-[13px] font-semibold">Category</label>
                    <select id="qa-cat" wire:model.live="qa.account_id" class="{{ $field }}">@foreach ($this->categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                    @error('qa.account_id')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="grid grid-cols-2 gap-[10px]">
                    <div class="flex flex-col gap-[6px]"><label for="qa-amt" class="text-[13px] font-semibold">Amount (৳)</label><input id="qa-amt" type="number" min="0" step="0.01" wire:model.blur="qa.amount" placeholder="0" class="{{ $field }}">@error('qa.amount')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                    <div class="flex flex-col gap-[6px]"><label for="qa-date" class="text-[13px] font-semibold">Date</label><input id="qa-date" type="text" readonly placeholder="YYYY-MM-DD" wire:model="qa.date" class="flatpickr-only-date cursor-pointer bg-white {{ $field }} py-[8px]">@error('qa.date')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                </div>
                <div class="flex flex-col gap-[6px]"><label for="qa-acc" class="text-[13px] font-semibold">Paid from</label>
                    <select id="qa-acc" wire:model="qa.from" class="{{ $field }}">@foreach ($this->cashAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }} · {{ $money($a['balance']) }}</option>@endforeach</select>
                    @error('qa.from')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label for="qa-to" class="text-[13px] font-semibold">Paid to (optional)</label><input id="qa-to" type="text" wire:model="qa.to" placeholder="যেমন: Facebook, DESCO, দোকানের নাম" class="{{ $field }}"></div>
                <div class="flex flex-col gap-[6px]"><label for="qa-note" class="text-[13px] font-semibold">Note</label><input id="qa-note" type="text" wire:model="qa.note" placeholder="কী জন্য" class="{{ $field }}"></div>
                <label class="block cursor-pointer rounded-[10px] border-[1.5px] border-dashed border-[#c7d2fe] p-[12px] text-center text-[12.5px] text-[#4b5563]">
                    <input type="file" wire:model="receipt" accept="image/*,application/pdf" class="sr-only">
                    @if ($receipt)
                        <span class="font-semibold text-[#047857]">{{ $receipt->getClientOriginalName() }}</span> · <span class="text-[#4338ca]">Change</span>
                    @else
                        Receipt-এর ছবি দিন · <span class="text-[#4338ca]">Choose file</span>
                    @endif
                    <div wire:loading wire:target="receipt" class="mt-[2px] text-[11.5px] text-[#6b7280]">Uploading…</div>
                    @if ($this->receiptLimit > 0)<div class="mt-[2px] text-[11.5px] text-[#6b7280]">{{ $money($this->receiptLimit) }}-এর বেশি হলে লাগবেই</div>@endif
                </label>
                @error('receipt')<span class="{{ $err }}">{{ $message }}</span>@enderror
                <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model="qa.repeat" class="accent-[#4f46e5]"> প্রতি মাসে repeat করো</label>
                <div class="flex justify-end gap-[8px]">
                    <button type="button" wire:click="save(false)" wire:loading.attr="disabled" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Save as draft</button>
                    @if (\App\Support\AccountsAccess::can('post'))
                        <button type="button" wire:click="save(true)" wire:loading.attr="disabled" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Save expense</button>
                    @endif
                </div>
            </section>

            {{-- Recurring --}}
            <section class="flex flex-col gap-[8px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <h2 class="m-0 text-[15px] font-bold">Recurring · প্রতি মাসে</h2>
                @foreach ($this->recurring as ['r' => $r, 'draft' => $hasDraft])
                    <div wire:key="rec-{{ $r->id }}" class="flex items-center justify-between border-t border-[#f0f1f4] py-[7px] text-[13px]">
                        <div><div class="font-semibold">{{ $r->name }}{{ $r->payee ? ' · ' . $r->payee : '' }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $ordinal($r->day_of_month ?: $r->next_run_date->day) }} · {{ $r->fromAccount?->name ?? '—' }}{{ $r->varies ? ' · amount varies' : '' }}</div></div>
                        @if ($hasDraft)
                            <a href="{{ route('accounts.posting') }}" wire:navigate class="rounded-[6px] bg-[#fef3c7] px-[8px] py-[2px] text-[11.5px] font-semibold !text-[#92400e]">Draft created</a>
                        @else
                            <span class="text-[11.5px] text-[#6b7280]">{{ $r->next_run_date->format('d M') }}</span>
                        @endif
                    </div>
                @endforeach
                <div class="flex items-center justify-between border-t border-[#f0f1f4] py-[7px] text-[13px]">
                    <div><div class="font-semibold">Asset depreciation</div><div class="text-[11.5px] text-[#6b7280]">Month end · auto from Fixed Assets</div></div>
                    @if (Route::has('accounts.fixed-assets'))<a href="{{ route('accounts.fixed-assets') }}" wire:navigate class="text-[12px]">View</a>@endif
                </div>
            </section>
        </aside>
    </div>

    @livewire('accounts.voucher-form')
</div>
