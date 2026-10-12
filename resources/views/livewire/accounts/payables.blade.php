{{-- Payables — design: Payables.dc.html --}}
@php
    $c = $this->cards;
    $plan = $this->plan;
    $ag = $this->aging;
    $rows = $this->rows;
    $srcStyle = ['supplier' => 'bg-[#e0f2fe] text-[#075985]', 'loan' => 'bg-[#e0e7ff] text-[#3730a3]', 'salary' => 'bg-[#ccfbf1] text-[#115e59]', 'bill' => 'bg-[#f3f4f6] text-[#374151]'];
    $status = ['overdue' => ['bg-[#fee2e2] text-[#991b1b]', 'Overdue'], 'week' => ['bg-[#fef3c7] text-[#92400e]', 'Due soon'], 'next' => ['bg-[#f3f4f6] text-[#374151]', 'Upcoming'], 'later' => ['bg-[#f3f4f6] text-[#374151]', 'Upcoming']];
    $input = 'w-full text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $err = 'text-[12px] text-[#b91c1c]';
    $ordinal = fn ($d) => $d . (in_array($d % 100, [11, 12, 13]) ? 'th' : ([1 => 'st', 2 => 'nd', 3 => 'rd'][$d % 10] ?? 'th'));
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]"
     x-data="{ sel: [], amounts: @js($this->all->pluck('amount', 'key')), get total() { return this.sel.reduce((a, k) => a + Number(this.amounts[k] || 0), 0) } }"
     x-on:clear-selection.window="sel = []">

    <x-accounts.page-head title="আমি দিবো · Payables" subtitle="Supplier, ঋণের কিস্তি, বেতন আর bill — কবে কত দিতে হবে">
        <button type="button" wire:click="openBill" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">+ Bill</button>
        <button type="button" onclick="window.print()" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Payment schedule (PDF)</button>
        <button type="button" wire:click="$dispatch('voucher-form', { type: 'payment' })" class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">টাকা দিন · Pay</button>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(190px,1fr))]">
        <x-accounts.stat label="মেয়াদ পার · Overdue" :value="$money($c['overdue'][0])" color="#b91c1c" :sub="$c['overdue'][1]" />
        <x-accounts.stat :label="'এই সপ্তাহে · ' . $c['week'][2]" :value="$money($c['week'][0])" color="#b45309" :sub="$c['week'][1]" />
        <x-accounts.stat :label="'পরের সপ্তাহে · ' . $c['next'][2]" :value="$money($c['next'][0])" :sub="$c['next'][1]" />
        <x-accounts.stat :label="'এরপর · ' . $c['later'][2]" :value="$money($c['later'][0])" :sub="$c['later'][1]" />
    </div>

    {{-- Cash plan --}}
    <section class="flex flex-col gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
        <div class="flex flex-wrap items-center justify-between gap-[10px]">
            <div>
                <h2 class="m-0 text-[15px] font-bold">টাকা যথেষ্ট আছে তো? · Next 30 days</h2>
                <div class="mt-[2px] text-[12.5px] text-[#6b7280]">আগামী ৩০ দিনে দিতে হবে বনাম এখন হাতে আছে</div>
            </div>
            @if ($plan['left'] >= 0)
                <span class="rounded-[6px] bg-[#d1fae5] px-[9px] py-[3px] text-[12px] font-semibold text-[#065f46]">Covered</span>
            @else
                <span class="rounded-[6px] bg-[#fee2e2] px-[9px] py-[3px] text-[12px] font-semibold text-[#991b1b]">Short</span>
            @endif
        </div>
        <div class="grid gap-[12px] text-[13px] [grid-template-columns:repeat(auto-fit,minmax(170px,1fr))]">
            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[#6b7280]">দিতে হবে (30 days)</div><div class="mt-[2px] text-[18px] font-bold text-[#b91c1c]">{{ $money($plan['payable']) }}</div></div>
            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[#6b7280]">Cash + bank + MFS</div><div class="mt-[2px] text-[18px] font-bold">{{ $money($plan['cash']) }}</div></div>
            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px]"><div class="text-[#6b7280]">আসার কথা (COD + due)</div><div class="mt-[2px] text-[18px] font-bold text-[#047857]">+ {{ $money($plan['inflow']) }}</div></div>
            @if ($plan['left'] >= 0)
                <div class="rounded-[10px] bg-[#ecfdf5] px-[12px] py-[10px]"><div class="text-[#065f46]">৩০ দিন পরে থাকবে (approx)</div><div class="mt-[2px] text-[18px] font-bold text-[#065f46]">{{ $money($plan['left']) }}</div></div>
            @else
                <div class="rounded-[10px] bg-[#fef2f2] px-[12px] py-[10px]"><div class="text-[#991b1b]">ঘাটতি (approx)</div><div class="mt-[2px] text-[18px] font-bold text-[#991b1b]">− {{ $money(abs($plan['left'])) }}</div></div>
            @endif
        </div>
        <div class="text-[12px] text-[#6b7280]">{{ $plan['tight'] }}</div>
    </section>

    <div class="flex flex-wrap items-start gap-[16px]">

        <section class="min-w-0 flex-[999_1_640px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="acc-no-print flex flex-col gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <div role="group" aria-label="When" class="flex flex-wrap gap-[6px]">
                    @foreach ($this::WHEN as $key => $label)
                        <x-accounts.chip :on="$when === $key" wire:click="setWhen('{{ $key }}')">{{ $label }}</x-accounts.chip>
                    @endforeach
                </div>
                <div role="group" aria-label="Source" class="flex flex-wrap gap-[6px]">
                    @foreach ($this::SOURCES as $key => $label)
                        <x-accounts.chip :on="$src === $key" wire:click="setSrc('{{ $key }}')">{{ $label }}</x-accounts.chip>
                    @endforeach
                </div>
            </div>

            <div x-show="sel.length" x-cloak class="acc-no-print flex flex-wrap items-center justify-between gap-[10px] border-b border-[#c7d2fe] bg-[#eef2ff] px-[18px] py-[10px] text-[13px] text-[#312e81]">
                <span><b x-text="sel.length + ' selected'"></b> · <span x-text="'৳' + Math.round(total).toLocaleString('en-IN')"></span></span>
                <div class="flex items-center gap-[8px]">
                    <label for="pay-from" class="text-[13px]">Pay from</label>
                    <select id="pay-from" wire:model="payFrom" class="rounded-[8px] border border-[#c7d2fe] bg-white px-[10px] py-[7px] text-[13px]">
                        @foreach ($this->cashAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }} · {{ $money($a['balance']) }}</option>@endforeach
                    </select>
                    <button type="button" x-on:click="$wire.createPayments(sel)" wire:loading.attr="disabled" class="rounded-[8px] border-0 bg-[#4f46e5] px-[14px] py-[7px] text-[13px] font-semibold text-white">Create payments</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[860px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="acc-no-print w-[28px] py-[10px] pl-[18px]"><span class="sr-only">Select</span></th>
                            <th class="px-[8px] py-[10px] font-semibold">Due</th>
                            <th class="px-[8px] py-[10px] font-semibold">Pay to</th>
                            <th class="px-[8px] py-[10px] font-semibold">For</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Amount</th>
                            <th class="px-[8px] py-[10px] font-semibold">Status</th>
                            <th class="acc-no-print px-[18px] py-[10px] text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr wire:key="pay-{{ $r['key'] }}" class="border-t border-[#f0f1f4]">
                                <td class="acc-no-print py-[11px] pl-[18px]">
                                    @if (! $r['draft'] && $r['ref']['type'] !== 'payroll')
                                        <input type="checkbox" value="{{ $r['key'] }}" x-model="sel" aria-label="Select payment" class="accent-[#4f46e5]">
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-[8px] py-[11px]">
                                    <div @class(['font-bold text-[#b91c1c]' => $r['when'] === 'overdue', 'font-semibold' => $r['when'] !== 'overdue'])>{{ $r['due']->format('d M') }}</div>
                                    <div class="text-[11.5px] text-[#6b7280]">{{ \App\Services\Accounts\PayablesSchedule::note($r['due']) }}</div>
                                </td>
                                <td class="px-[8px] py-[11px]">
                                    <div class="flex flex-wrap items-center gap-[6px]">
                                        @if ($r['profile_id'])<a href="{{ route('accounts.profiles.show', $r['profile_id']) }}" wire:navigate class="font-semibold !text-[#111827]">{{ $r['payee'] }}</a>@else<span class="font-semibold">{{ $r['payee'] }}</span>@endif
                                        <span class="rounded-[6px] px-[7px] py-[1px] text-[11px] font-semibold {{ $srcStyle[$r['src']] }}">{{ \App\Services\Accounts\PayablesSchedule::SOURCES[$r['src']] }}</span>
                                    </div>
                                </td>
                                <td class="px-[8px] py-[11px] text-[#374151]">{{ $r['for'] }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-bold">{{ ($r['varies'] ?? false) ? '~' : '' }}{{ $money($r['amount']) }}</td>
                                <td class="px-[8px] py-[11px]">
                                    @if ($r['draft'])
                                        <a href="{{ route('accounts.posting') }}" wire:navigate class="whitespace-nowrap rounded-[6px] bg-[#e0e7ff] px-[8px] py-[3px] text-[12px] font-semibold !text-[#3730a3]">Draft {{ $r['draft'] }}</a>
                                    @else
                                        <span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $status[$r['when']][0] }}">{{ $status[$r['when']][1] }}</span>
                                    @endif
                                </td>
                                <td class="acc-no-print whitespace-nowrap px-[18px] py-[11px] text-right">
                                    @if ($r['ref']['type'] === 'payroll')
                                        <a href="{{ route('accounts.profiles.payroll') }}" wire:navigate class="font-semibold">Prepare payroll</a>
                                    @elseif (! $r['draft'])
                                        <a href="#" wire:click.prevent="openPay('{{ $r['key'] }}')" class="font-semibold">Pay</a>
                                    @endif
                                    @if (! in_array($r['ref']['type'], ['payroll', 'balance'], true))
                                        <span class="text-[#d1d5db]"> · </span><a href="#" wire:click.prevent="openReschedule('{{ $r['key'] }}')">Reschedule</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($rows->isEmpty())
                <div class="px-[18px] py-[36px] text-center text-[#6b7280]">এই filter-এ কিছু দেওয়ার নেই।</div>
            @endif
            <div class="flex justify-between border-t-2 border-[#e5e7eb] bg-[#f9fafb] px-[18px] py-[11px] text-[13px] font-bold">
                <span>Shown total</span><span>{{ $money($rows->sum('amount')) }}</span>
            </div>
        </section>

        <aside class="acc-no-print flex min-w-0 flex-[1_1_340px] flex-col gap-[16px]">
            <section class="flex flex-col gap-[10px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <h2 class="m-0 text-[15px] font-bold">Supplier aging</h2>
                <div class="flex h-[12px] gap-[2px] overflow-hidden rounded-[6px] bg-[#eef0f3]">
                    @foreach (['notdue' => '#94a3b8', 'soon' => '#f59e0b', 'overdue' => '#dc2626'] as $k => $color)
                        @if ($ag['pct'][$k] > 0)<div style="width: {{ $ag['pct'][$k] }}%; background: {{ $color }}"></div>@endif
                    @endforeach
                </div>
                <div class="grid grid-cols-[1fr_auto] gap-[6px] text-[13px]">
                    <span class="flex items-center gap-[6px] text-[#4b5563]"><span class="h-[10px] w-[10px] rounded-[3px] bg-[#94a3b8]"></span>Not due yet</span><b>{{ $money($ag['notdue']) }}</b>
                    <span class="flex items-center gap-[6px] text-[#4b5563]"><span class="h-[10px] w-[10px] rounded-[3px] bg-[#f59e0b]"></span>Due in 7 days</span><b>{{ $money($ag['soon']) }}</b>
                    <span class="flex items-center gap-[6px] text-[#4b5563]"><span class="h-[10px] w-[10px] rounded-[3px] bg-[#dc2626]"></span>Overdue</span><b class="text-[#b91c1c]">{{ $money($ag['overdue']) }}</b>
                </div>
                <a href="{{ route('accounts.profiles.suppliers') }}" wire:navigate class="text-[13px]">All suppliers</a>
            </section>

            <section class="flex flex-col gap-[10px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]">
                <h2 class="m-0 text-[15px] font-bold">Recurring bills</h2>
                @foreach ($this->recurringBills as $rb)
                    <div wire:key="rb-{{ $rb->id }}" class="group flex justify-between border-t border-[#f0f1f4] py-[6px] text-[13px]">
                        <div>
                            <div class="font-semibold">{{ $rb->name }}</div>
                            <div class="text-[11.5px] text-[#6b7280]">{{ ucfirst($rb->cadence) }} · {{ $ordinal($rb->day_of_month ?: $rb->next_run_date->day) }}{{ $rb->varies ? ' · varies' : '' }}
                                <a href="#" wire:click.prevent="stopRecurring({{ $rb->id }})" wire:confirm="{{ $rb->name }} বন্ধ করবেন?" class="ml-[4px] hidden !text-[#b91c1c] group-hover:inline">Stop</a></div>
                        </div>
                        <b>{{ $rb->varies ? '~' : '' }}{{ $money($rb->amount) }}</b>
                    </div>
                @endforeach
                <a href="#" wire:click.prevent="openRecurring" class="text-[13px] font-semibold">+ Add recurring bill</a>
            </section>
        </aside>
    </div>

    {{-- Pay one row --}}
    @if ($payKey)
        @php $pr = $this->all->firstWhere('key', $payKey); @endphp
        <x-accounts.modal :title="'Pay · ' . ($pr['payee'] ?? '')" close="$set('payKey', null)">
            <div class="text-[13px] text-[#374151]">{{ $pr['for'] ?? '' }} · due {{ isset($pr) ? $pr['due']->format('d M Y') : '' }} · {{ $money($pr['amount'] ?? 0) }}</div>
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Pay from</label>
                    <select wire:model="pay.account_id" class="{{ $input }}">@foreach ($this->cashAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }} · {{ $money($a['balance']) }}</option>@endforeach</select>@error('pay.account_id')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Amount (৳)</label><input type="number" min="0" step="0.01" wire:model="pay.amount" class="{{ $input }}">@error('pay.amount')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Date</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="pay.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}">@error('pay.date')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('payKey', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="savePay(false)" class="rounded-[9px] border border-[#c7d2fe] bg-[#eef2ff] px-[16px] py-[10px] text-[14px] font-semibold text-[#3730a3]">Save as draft</button>
                @if (\App\Support\AccountsAccess::can('post'))
                    <button type="button" wire:click="savePay(true)" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Pay now</button>
                @endif
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    {{-- Reschedule --}}
    @if ($rescheduleKey)
        <x-accounts.modal title="Reschedule" close="$set('rescheduleKey', null)">
            <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">New due date</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="rescheduleDate" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}">@error('rescheduleDate')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('rescheduleKey', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="reschedule" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    {{-- + Bill --}}
    @if ($showBill)
        <x-accounts.modal title="+ Bill · supplier বাকিতে কেনা" close="$set('showBill', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Supplier</label>
                    <select wire:model="bill.profile_id" class="{{ $input }}"><option value="">— Select —</option>@foreach ($this->supplierOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>@error('bill.profile_id')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">For</label>
                    <select wire:model="bill.ledger_id" class="{{ $input }}">@foreach ($this->billLedgers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Amount (৳)</label><input type="number" min="0" step="0.01" wire:model="bill.amount" class="{{ $input }}">@error('bill.amount')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Bill date</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="bill.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}"></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Due date</label><input type="text" readonly placeholder="YYYY-MM-DD" wire:model="bill.due" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}">@error('bill.due')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Note</label><input type="text" wire:model="bill.note" placeholder="যেমন: fabric 160 m" class="{{ $input }}"></div>
            </div>
            <div class="text-[12.5px] text-[#6b7280]">Draft journal হবে: Dr inventory / expense, Cr supplier payable। Post হলে due date সহ bill এই list-এ আসবে।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showBill', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveBill" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Create draft</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    {{-- + Recurring bill --}}
    @if ($showRecurring)
        <x-accounts.modal title="+ Add recurring bill" close="$set('showRecurring', false)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Name</label><input type="text" wire:model="recurring.name" placeholder="যেমন: Office rent" class="{{ $input }}">@error('recurring.name')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Pay to</label><input type="text" wire:model="recurring.payee" placeholder="যেমন: DESCO" class="{{ $input }}"></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Expense category</label>
                    <select wire:model="recurring.account_id" class="{{ $input }}">@foreach (\App\Services\Accounts\ExpenseCategories::all() as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Pay from</label>
                    <select wire:model="recurring.from" class="{{ $input }}">@foreach ($this->cashAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select></div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Amount (৳)</label><input type="number" min="0" step="0.01" wire:model="recurring.amount" class="{{ $input }}">@error('recurring.amount')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Day of month</label><input type="number" min="1" max="28" wire:model="recurring.day" class="{{ $input }}">@error('recurring.day')<span class="{{ $err }}">{{ $message }}</span>@enderror</div>
            </div>
            <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model="recurring.varies" class="accent-[#4f46e5]"> Amount varies (বিদ্যুৎ, পানি) — draft-এ আসল amount বসাবেন</label>
            <div class="text-[12.5px] text-[#6b7280]">প্রতি মাসে ঐ দিনে একটা expense draft তৈরি হবে — Posting Pending-এ approve করবেন।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('showRecurring', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveRecurring" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @livewire('accounts.voucher-form')
</div>
