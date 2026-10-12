{{--
    Accounts Panel dashboard — renders inside layouts.accounts.app.
    Links go to the existing Admin > Accounts pages (users with the admin
    panel only); anything without a page yet stays "#".
--}}
@php
    $hasAdmin = $this->hasAdminPanel;
    $link = fn (?string $name, array $params = []) => $name && Route::has($name) && ($hasAdmin || str_starts_with($name, 'accounts.')) ? route($name, $params) : '#';

    $s  = $this->summary;
    $st = $this->stock;

    $toneDot = [
        'green'  => 'bg-emerald-100 text-emerald-800',
        'red'    => 'bg-red-100 text-red-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'amber'  => 'bg-amber-100 text-amber-800',
        'gray'   => 'bg-gray-100 text-gray-700',
    ];
    $statusBadge = [
        'paid'     => 'bg-emerald-100 text-emerald-800',
        'due'      => 'bg-amber-100 text-amber-800',
        'upcoming' => 'bg-gray-100 text-gray-700',
        'overdue'  => 'bg-red-100 text-red-800',
    ];
    $alertBox = [
        'red'   => 'bg-red-50 text-red-900',
        'amber' => 'bg-amber-50 text-amber-900',
        'green' => 'bg-emerald-50 text-emerald-900',
    ];
    $pending      = $this->pendingVouchers;
    $pendingTotal = array_sum(array_column($pending, 'amount'));
@endphp

<div class="mx-auto flex w-full max-w-[1400px] flex-col gap-5 font-['Plus_Jakarta_Sans','Hind_Siliguri',sans-serif] text-sm text-gray-900">

    {{-- Flash --}}
    @if (session('accounts.status'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition
             class="rounded-xl bg-emerald-50 px-4 py-3 text-emerald-900">
            {{ session('accounts.status') }}
        </div>
    @endif

    {{-- Title + date filter --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-['Hind_Siliguri','Plus_Jakarta_Sans',sans-serif] text-2xl font-bold">হিসাবের সারাংশ</h1>
            <p class="mt-0.5 text-[13px] text-gray-500">Cash, due, payable, loans and EMI at a glance</p>
        </div>

        <div class="flex flex-col items-end gap-2">
            <div role="group" aria-label="Period" class="flex flex-wrap gap-1 rounded-[10px] border border-gray-200 bg-white p-1">
                @foreach (\App\Livewire\Accounts\Dashboard::PERIODS as $key => $label)
                    <button type="button" wire:click="setPeriod('{{ $key }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-[13px] transition',
                                'bg-indigo-600 font-semibold text-white' => $period === $key,
                                'text-gray-700 hover:bg-gray-100' => $period !== $key,
                            ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($period === 'custom')
                <form wire:submit="applyCustom" class="flex flex-wrap items-center gap-2 text-[13px]">
                    <label for="from-date" class="text-gray-600">From</label>
                    <input id="from-date" type="date" wire:model="from"
                           class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-[13px] focus:border-indigo-500 focus:ring-indigo-500">
                    <label for="to-date" class="text-gray-600">To</label>
                    <input id="to-date" type="date" wire:model="to"
                           class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-[13px] focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-3.5 py-2 font-semibold text-white hover:bg-indigo-700">Apply</button>
                </form>
                @error('to') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
            @else
                <div class="flex items-center gap-1.5 text-[12.5px] text-gray-600">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                    <span>{{ $this->rangeLabel }}</span>
                    <span wire:loading class="text-gray-400">· updating…</span>
                </div>
            @endif
        </div>
    </div>

    {{-- KPI row 1 --}}
    <div class="grid grid-cols-[repeat(auto-fit,minmax(230px,1fr))] gap-3.5">
        <div class="rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="text-[12.5px] text-gray-600">আজকে আসলো · Cash In</div>
            <div class="mt-1.5 text-[26px] font-bold text-emerald-700">{{ $this->money($s['cash_in']) }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $s['cash_in_note'] }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="text-[12.5px] text-gray-600">আজকে গেলো · Cash Out</div>
            <div class="mt-1.5 text-[26px] font-bold text-red-700">{{ $this->money($s['cash_out']) }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $s['cash_out_note'] }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="text-[12.5px] text-gray-600">হাতে নগদ · Cash on Hand</div>
            <div class="mt-1.5 text-[26px] font-bold">{{ $this->money($s['cash_on_hand']) }}</div>
            <div class="mt-1 text-xs text-gray-500">Main cash box</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="text-[12.5px] text-gray-600">ব্যাংক ও মোবাইল ব্যাংকিং</div>
            <div class="mt-1.5 text-[26px] font-bold">{{ $this->money($s['bank_mfs']) }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $s['bank_mfs_note'] }}</div>
        </div>
    </div>

    {{-- KPI row 2 --}}
    <div class="grid grid-cols-[repeat(auto-fit,minmax(230px,1fr))] gap-3.5">
        <div class="rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="text-[12.5px] text-gray-600">আমি পাবো · Receivable (Due)</div>
            <div class="mt-1.5 text-[22px] font-bold text-emerald-700">{{ $this->money($s['receivable']) }}</div>
            <div class="mt-1 text-xs font-semibold text-amber-700">{{ $this->money($s['receivable_overdue']) }} মেয়াদোত্তীর্ণ (60+ দিন)</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="text-[12.5px] text-gray-600">আমি দিবো · Payable</div>
            <div class="mt-1.5 text-[22px] font-bold text-red-700">{{ $this->money($s['payable']) }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $this->money($s['payable_7_days']) }} due within 7 days</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="text-[12.5px] text-gray-600">ঋণ বাকি · Loan Outstanding</div>
            <div class="mt-1.5 text-[22px] font-bold text-indigo-700">{{ $this->money($s['loan_outstanding']) }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $s['lender_count'] }} lenders · support advance {{ $this->money($s['support_advance']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="text-[12.5px] text-gray-600">লাভ · Profit</div>
            <div class="mt-1.5 text-[22px] font-bold text-emerald-700">{{ $this->money($s['profit']) }}</div>
            <div class="mt-1 text-xs text-gray-500">Posted entries only</div>
        </div>
    </div>

    {{-- Stock --}}
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-[18px] py-3.5">
            <h2 class="text-[15px] font-bold">স্টক · Inventory</h2>
            <div class="flex items-center gap-3.5 text-[12.5px]">
                <span class="text-gray-500">Valued at weighted average cost</span>
                <a href="{{ $link('admin.inventory.movements') }}" class="font-semibold text-indigo-700 hover:text-indigo-900">Stock ledger</a>
            </div>
        </div>
        <div class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] divide-x divide-gray-100">
            <div class="px-[18px] py-4">
                <div class="text-[12.5px] text-gray-600">আজকে স্টক আসলো · Stock In</div>
                <div class="mt-1.5 flex items-baseline gap-1.5"><span class="text-2xl font-bold text-emerald-700">+{{ number_format($st['in_qty']) }}</span><span class="text-[13px] text-gray-500">pcs</span></div>
                <div class="mt-1 text-xs text-gray-500">Cost {{ $this->money($st['in_cost']) }} · {{ $st['in_note'] }}</div>
            </div>
            <div class="px-[18px] py-4">
                <div class="text-[12.5px] text-gray-600">আজকে স্টক গেলো · Stock Out</div>
                <div class="mt-1.5 flex items-baseline gap-1.5"><span class="text-2xl font-bold text-red-700">−{{ number_format($st['out_qty']) }}</span><span class="text-[13px] text-gray-500">pcs</span></div>
                <div class="mt-1 text-xs text-gray-500">COGS {{ $this->money($st['out_cogs']) }} · {{ $st['out_note'] }}</div>
            </div>
            <div class="px-[18px] py-4">
                <div class="text-[12.5px] text-gray-600">মোট স্টক · Total Stock</div>
                <div class="mt-1.5 flex items-baseline gap-1.5"><span class="text-2xl font-bold">{{ number_format($st['total_qty']) }}</span><span class="text-[13px] text-gray-500">pcs</span></div>
                <div class="mt-1 text-xs text-gray-500">{{ $st['product_count'] }} products · {{ $st['in_transit'] }} pcs in transit (COD)</div>
            </div>
            <div class="px-[18px] py-4">
                <div class="text-[12.5px] text-gray-600">স্টকের দাম · Stock Value</div>
                <div class="mt-1.5 text-2xl font-bold text-indigo-700">{{ $this->money($st['value_cost']) }}</div>
                <div class="mt-1 text-xs text-gray-500">At cost · sale value {{ $this->money($st['value_sale']) }}</div>
            </div>
        </div>
    </section>

    {{-- Posting pending + Master profiles --}}
    <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-3">

        {{-- Checkbox selection lives in Alpine — ticking boxes never hits the server. --}}
        <section id="posting-pending" x-data="{ selected: [] }"
                 x-init="$wire.$hook('commit', ({ succeed }) => succeed(() => $nextTick(() => {
                     selected = selected.filter(id => $root.querySelector(`[data-voucher][value='${id}']`));
                 })))"
                 class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2.5 border-b border-gray-100 bg-amber-50 px-[18px] py-3.5">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="h-2 w-2 rounded-full bg-amber-600"></span>
                    <h2 class="text-[15px] font-bold">পোস্টিং বাকি · Posting Pending</h2>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                        {{ count($pending) }} vouchers · {{ $this->money($pendingTotal) }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ $link('admin.accounts.settings.journal-entries') }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-[13px] text-gray-700 hover:bg-gray-50">Review all</a>
                    <button type="button" @click="$wire.postSelected(selected); selected = []" wire:loading.attr="disabled"
                            disabled :disabled="selected.length === 0"
                            class="rounded-lg bg-indigo-600 px-3.5 py-2 text-[13px] font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Post selected <span x-show="selected.length" x-text="'(' + selected.length + ')'"></span>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] border-collapse text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-wide text-gray-500">
                            <th class="w-10 py-2.5 pl-[18px]">
                                <input type="checkbox" aria-label="Select all vouchers"
                                       @change="selected = $event.target.checked ? [...$root.querySelectorAll('[data-voucher]')].map(el => el.value) : []"
                                       :checked="selected.length > 0 && selected.length === $root.querySelectorAll('[data-voucher]').length"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-2 py-2.5 font-semibold">Voucher</th>
                            <th class="px-2 py-2.5 font-semibold">Details</th>
                            <th class="px-2 py-2.5 font-semibold">Party</th>
                            <th class="px-2 py-2.5 text-right font-semibold">Amount</th>
                            <th class="px-2 py-2.5 font-semibold">By</th>
                            <th class="py-2.5 pr-[18px] text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pending as $v)
                            <tr wire:key="voucher-{{ $v['id'] }}" class="border-t border-gray-100">
                                <td class="py-3 pl-[18px]">
                                    <input type="checkbox" value="{{ $v['id'] }}" x-model="selected" data-voucher
                                           aria-label="Select {{ $v['ref'] }}"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-2 py-3"><div class="font-semibold">{{ $v['ref'] }}</div><div class="text-[11.5px] text-gray-500">{{ $v['type'] }}</div></td>
                                <td class="px-2 py-3 text-gray-700">{{ $v['detail'] }}</td>
                                <td class="px-2 py-3">{{ $v['party'] }}</td>
                                <td class="whitespace-nowrap px-2 py-3 text-right font-semibold">{{ $this->money($v['amount']) }}</td>
                                <td class="whitespace-nowrap px-2 py-3 text-gray-500">{{ $v['by'] }}</td>
                                <td class="whitespace-nowrap py-3 pr-[18px] text-right">
                                    <button type="button" wire:click="postVoucher({{ $v['id'] }})"
                                            wire:confirm="Post {{ $v['ref'] }}? Posted entries can't be edited."
                                            class="font-semibold text-indigo-700 hover:text-indigo-900">Post</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-[18px] py-8 text-center text-gray-500">সব পোস্ট করা হয়েছে।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="flex items-center justify-between">
                <h2 class="text-[15px] font-bold">Master Profiles</h2>
                <a href="{{ $link('accounts.profiles.create') }}" class="text-[13px] font-semibold text-indigo-700 hover:text-indigo-900">+ New profile</a>
            </div>
            <p class="text-[12.5px] leading-relaxed text-gray-600">একজন ব্যক্তি বা প্রতিষ্ঠান = একটি profile। একই profile একাধিক role নিতে পারে: Customer, Supplier, Lender, Owner, Login.</p>
            @foreach ($this->masters as $m)
                <a href="{{ $link($m['route']) }}" wire:key="master-{{ $m['label'] }}" class="flex items-center gap-3 rounded-[10px] border border-gray-100 px-3 py-2.5 text-gray-900 hover:border-gray-200 hover:bg-gray-50">
                    <div class="flex h-9 w-9 flex-none items-center justify-center rounded-lg text-[13px] font-bold {{ $toneDot[$m['tone']] }}">{{ $m['initial'] }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold">{{ $m['label'] }} <span class="font-medium text-gray-500">· {{ $m['count'] }}</span></div>
                        <div class="truncate text-xs text-gray-500">{{ $m['sub'] }}</div>
                    </div>
                    <div class="whitespace-nowrap text-right text-[13.5px] font-bold">{{ $this->money($m['amount']) }}</div>
                </a>
            @endforeach
        </section>
    </div>

    {{-- Installments / EMI --}}
    {{-- Frequency filter is client-side — rows are all rendered, Alpine shows/hides them. --}}
    <section x-data="{ freq: 'all' }" class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-[18px] py-3.5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-[15px] font-bold">কিস্তি ও EMI · Installments</h2>
                <a href="{{ $link('admin.accounts.loans.index') }}" class="text-[13px] font-semibold text-indigo-700 hover:text-indigo-900">+ New EMI rule</a>
            </div>
            <div role="group" aria-label="Frequency" class="flex flex-wrap gap-1.5">
                @foreach (\App\Livewire\Accounts\Dashboard::FREQUENCIES as $f)
                    <button type="button" @click="freq = '{{ $f }}'"
                            class="rounded-full border px-3 py-1.5 text-[12.5px] transition"
                            :class="freq === '{{ $f }}' ? 'border-indigo-600 bg-indigo-600 font-semibold text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        {{ ucfirst($f) }} <span class="opacity-70">{{ $this->freqCounts[$f] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] border-collapse text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-wide text-gray-500">
                        <th class="py-2.5 pl-[18px] font-semibold">Party</th>
                        <th class="px-2 py-2.5 font-semibold">Rule</th>
                        <th class="px-2 py-2.5 font-semibold">Kisti</th>
                        <th class="px-2 py-2.5 font-semibold">Due</th>
                        <th class="px-2 py-2.5 text-right font-semibold">EMI</th>
                        <th class="px-2 py-2.5 text-right font-semibold">Paid</th>
                        <th class="px-2 py-2.5 text-right font-semibold">Support</th>
                        <th class="py-2.5 pr-[18px] font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->allInstallments as $e)
                        <tr wire:key="emi-{{ $loop->index }}" data-freq="{{ $e['frequency'] }}"
                            x-show="freq === 'all' || freq === '{{ $e['frequency'] }}'" class="border-t border-gray-100">
                            <td class="py-3 pl-[18px]"><div class="font-semibold">{{ $e['party'] }}</div><div class="text-[11.5px] text-gray-500">{{ $e['role'] }}</div></td>
                            <td class="px-2 py-3"><span class="whitespace-nowrap rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-800">{{ $e['rule'] }}</span></td>
                            <td class="px-2 py-3 text-gray-700">{{ $e['no'] }}</td>
                            <td class="whitespace-nowrap px-2 py-3">{{ $e['due'] }}</td>
                            <td class="whitespace-nowrap px-2 py-3 text-right">{{ $this->money($e['emi']) }}</td>
                            <td class="whitespace-nowrap px-2 py-3 text-right font-semibold">{{ $this->money($e['paid']) }}</td>
                            <td class="whitespace-nowrap px-2 py-3 text-right font-semibold text-emerald-700">{{ $this->money($e['support']) }}</td>
                            <td class="py-3 pr-[18px]"><span class="whitespace-nowrap rounded-md px-2 py-1 text-xs font-semibold {{ $statusBadge[$e['status']] }}">{{ $e['status_label'] }}</span></td>
                        </tr>
                    @endforeach
                    <tr x-show="! [...$root.querySelectorAll('[data-freq]')].some(el => freq === 'all' || el.dataset.freq === freq)"
                        @if (count($this->allInstallments)) style="display: none" @endif>
                        <td colspan="8" class="px-[18px] py-8 text-center text-gray-500">এই ধরনের কোনো কিস্তি নেই।</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Aging + Payables + Cash & Bank --}}
    <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start gap-4">

        <section class="flex flex-col gap-3.5 rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="flex items-center justify-between">
                <h2 class="text-[15px] font-bold">বাকি কত দিনের · Due Aging</h2>
                <a href="{{ $link('admin.accounts.receivables.index') }}" class="text-[13px] text-indigo-700 hover:text-indigo-900">View all</a>
            </div>
            @foreach ($this->aging as $g)
                <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between text-[13px]"><span class="text-gray-600">{{ $g['label'] }}</span><span class="font-semibold">{{ $this->money($g['amount']) }}</span></div>
                    <div class="h-2 overflow-hidden rounded bg-gray-100"><div class="h-full rounded {{ $g['color'] }}" style="width: {{ $g['pct'] }}%"></div></div>
                </div>
            @endforeach
        </section>

        <section class="flex flex-col rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="flex items-center justify-between pb-2">
                <h2 class="text-[15px] font-bold">দিতে হবে শীঘ্রই · Payables</h2>
                <a href="{{ $link('admin.accounts.payables.index') }}" class="text-[13px] text-indigo-700 hover:text-indigo-900">View all</a>
            </div>
            @foreach ($this->payables as $p)
                <div class="flex items-center justify-between border-t border-gray-100 py-2.5">
                    <div>
                        <div class="font-semibold">{{ $p['party'] }}</div>
                        <div @class(['text-xs', 'font-semibold text-red-700' => $p['overdue'], 'text-gray-500' => ! $p['overdue']])>{{ $p['when'] }}</div>
                    </div>
                    <div class="font-bold text-red-700">{{ $this->money($p['amount']) }}</div>
                </div>
            @endforeach
        </section>

        <section class="flex flex-col rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <div class="flex items-center justify-between pb-2">
                <h2 class="text-[15px] font-bold">Cash &amp; Bank</h2>
                <a href="{{ $link('admin.accounts.cash-accounts.index') }}" class="text-[13px] text-indigo-700 hover:text-indigo-900">Transfer</a>
            </div>
            @foreach ($this->accounts as $a)
                <div class="flex items-center justify-between border-t border-gray-100 py-2.5">
                    <div><div class="font-semibold">{{ $a['name'] }}</div><div class="text-xs text-gray-500">{{ $a['kind'] }}</div></div>
                    <div class="font-bold">{{ $this->money($a['amount']) }}</div>
                </div>
            @endforeach
        </section>
    </div>

    {{-- Today's transactions + Alerts --}}
    <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-3">

        <section class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white xl:col-span-2">
            <div class="flex items-center justify-between border-b border-gray-100 px-[18px] py-3.5">
                <h2 class="text-[15px] font-bold">আজকের লেনদেন</h2>
                <a href="{{ $link('admin.accounts.transactions') }}" class="text-[13px] text-indigo-700 hover:text-indigo-900">সব দেখুন</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] border-collapse text-[13px]">
                    <tbody>
                        @forelse ($this->transactions as $t)
                            <tr class="border-t border-gray-100 first:border-t-0">
                                <td class="whitespace-nowrap py-3 pl-[18px] text-gray-500">{{ $t['time'] }}</td>
                                <td class="px-2 py-3"><div class="font-semibold">{{ $t['title'] }}</div><div class="text-[11.5px] text-gray-500">{{ $t['account'] }}</div></td>
                                <td class="px-2 py-3">
                                    @if ($t['posted'])
                                        <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[11.5px] font-semibold text-emerald-800">Posted</span>
                                    @else
                                        <span class="rounded-md bg-amber-100 px-2 py-0.5 text-[11.5px] font-semibold text-amber-800">Pending</span>
                                    @endif
                                </td>
                                <td @class(['whitespace-nowrap py-3 pr-[18px] text-right font-bold', 'text-emerald-700' => $t['amount'] >= 0, 'text-red-700' => $t['amount'] < 0])>
                                    {{ $this->money($t['amount'], signed: true) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-[18px] py-8 text-center text-gray-500">আজকে কোনো লেনদেন হয়নি।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="flex flex-col gap-2.5 rounded-2xl border border-gray-200 bg-white px-[18px] py-4">
            <h2 class="text-[15px] font-bold">সতর্কতা · Alerts</h2>
            @forelse ($this->alerts as $al)
                <div class="rounded-[10px] px-3 py-2.5 {{ $alertBox[$al['tone']] }}">
                    <div class="text-[13px] font-semibold">{{ $al['title'] }}</div>
                    <div class="mt-0.5 text-[12.5px] opacity-85">{{ $al['text'] }}</div>
                </div>
            @empty
                <p class="py-6 text-center text-gray-500">কোনো সতর্কতা নেই।</p>
            @endforelse
        </section>
    </div>
</div>
