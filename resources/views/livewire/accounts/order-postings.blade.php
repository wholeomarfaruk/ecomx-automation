{{-- Orders to post — delivered sales and returns that aren't in the books yet. --}}
@php
    $sales = $this->sales;
    $returns = $this->returns;
    $rows = $tab === 'returns' ? $returns : $sales;
    $ready = $tab === 'returns' ? $returns : $sales->whereNull('blocked');
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1480px] flex-col gap-[18px]"
     x-data="{ sel: [] }" x-on:clear-selection.window="sel = []">

    <x-accounts.page-head title="অর্ডার পোস্টিং · Orders to post" subtitle="Deliver / return হওয়া অর্ডার — বিক্রি আর ফেরত হিসাবে তোলা বাকি">
        <a href="{{ route('accounts.posting') }}" wire:navigate class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] !text-[#374151]">Vouchers waiting</a>
        @if (\App\Support\AccountsAccess::can('settings'))
            <button type="button" wire:click="toggleAuto" class="rounded-[8px] border px-[14px] py-[9px] text-[13px] {{ $auto ? 'border-[#a7f3d0] bg-[#ecfdf5] font-semibold text-[#065f46]' : 'border-[#e5e7eb] bg-white text-[#374151]' }}">Auto-post: {{ $auto ? 'On' : 'Off' }}</button>
        @endif
    </x-accounts.page-head>

    @if (! $accountsOn)
        <div class="rounded-[12px] border border-[#fde68a] bg-[#fffbeb] px-[16px] py-[12px] text-[13px] text-[#78350f]">Accounts module বন্ধ — অর্ডার হিসাবে post হবে না।</div>
    @endif

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="বিক্রি post বাকি · Delivered" :value="$sales->count()" :sub="$money($sales->sum(fn ($r) => (float) $r['order']->total_amount)) . ' · ' . $sales->whereNotNull('blocked')->count() . ' blocked'" color="#b45309" />
        <x-accounts.stat label="ফেরত post বাকি · Returns" :value="$returns->count()" :sub="$money($returns->sum('amount')) . ' to reverse'" color="#b91c1c" />
        <x-accounts.stat label="Ready to post" :value="$sales->whereNull('blocked')->count() + $returns->count()" :sub="$auto ? 'Auto-post প্রতি ঘণ্টায় চলে' : 'Auto-post বন্ধ'" />
    </div>

    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[12px]">
            <div class="flex gap-[6px]">
                <x-accounts.chip :on="$tab === 'sales'" :count="$sales->count()" wire:click="$set('tab', 'sales')" x-on:click="sel = []">Delivered · বিক্রি</x-accounts.chip>
                <x-accounts.chip :on="$tab === 'returns'" :count="$returns->count()" wire:click="$set('tab', 'returns')" x-on:click="sel = []">Returned · ফেরত</x-accounts.chip>
            </div>
            @if (\App\Support\AccountsAccess::can('post'))
                <button type="button" x-on:click="$wire.postSelected(sel)" :disabled="sel.length === 0" wire:loading.attr="disabled"
                        class="rounded-[8px] bg-[#4f46e5] px-[14px] py-[8px] text-[13px] font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                    Post selected <span x-show="sel.length" x-text="'(' + sel.length + ')'"></span>
                </button>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[960px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="w-[34px] py-[10px] pl-[18px]">
                            <input type="checkbox" aria-label="Select all" class="accent-[#4f46e5]"
                                   x-on:change="sel = $event.target.checked ? [...$root.querySelectorAll('[data-ready]')].map(e => parseInt(e.value)) : []">
                        </th>
                        <th class="px-[8px] py-[10px] font-semibold">Order</th>
                        <th class="px-[8px] py-[10px] font-semibold">Customer</th>
                        <th class="px-[8px] py-[10px] font-semibold">{{ $tab === 'returns' ? 'Status' : 'Delivered' }}</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">{{ $tab === 'returns' ? 'To reverse' : 'Order total' }}</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">{{ $tab === 'returns' ? 'Cost back to stock' : 'Paid / COD' }}</th>
                        <th class="px-[8px] py-[10px] font-semibold">Post date</th>
                        <th class="px-[18px] py-[10px] text-right"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php $o = $r['order']; $blocked = $r['blocked'] ?? null; @endphp
                        <tr wire:key="op-{{ $tab }}-{{ $o->id }}" class="border-t border-[#f0f1f4] align-top">
                            <td class="py-[11px] pl-[18px]">
                                @unless ($blocked)<input type="checkbox" value="{{ $o->id }}" x-model.number="sel" data-ready class="accent-[#4f46e5]" aria-label="Select order #{{ $o->id }}">@endunless
                            </td>
                            <td class="px-[8px] py-[11px]">
                                <a href="{{ route('admin.sales.orders.show', $o->id) }}" target="_blank" class="font-bold">#{{ $o->id }}</a>
                                <div class="text-[11.5px] text-[#6b7280]">{{ $o->fulfillment_status?->label() ?? '' }}</div>
                            </td>
                            <td class="px-[8px] py-[11px]"><div class="font-semibold">{{ $o->customer?->full_name ?? '—' }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $o->customer?->phone }}</div></td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-[#374151]">
                                @if ($tab === 'returns'){{ $o->status->label() }}@else{{ ($o->courier_status_updated_at ?? $o->updated_at)?->format('d M Y') }}@endif
                            </td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right font-semibold">{{ $money($tab === 'returns' ? $r['amount'] : $o->total_amount) }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-right text-[#6b7280]">{{ $tab === 'returns' ? $money($r['cost']) : $money($o->paid_amount) }}</td>
                            <td class="whitespace-nowrap px-[8px] py-[11px] text-[#374151]">{{ \Carbon\Carbon::parse($r['date'])->format('d M Y') }}</td>
                            <td class="px-[18px] py-[11px] text-right">
                                @if ($blocked)
                                    <div class="ml-auto max-w-[280px] rounded-[8px] bg-[#fef3c7] px-[8px] py-[4px] text-left text-[12px] text-[#92400e]">{{ $blocked }}</div>
                                    <a href="{{ route('admin.sales.orders.show', $o->id) }}" target="_blank" class="text-[12px]">Pick batch</a>
                                @elseif (\App\Support\AccountsAccess::can('post'))
                                    <button type="button" wire:click="postOne({{ $o->id }}, '{{ $tab === 'returns' ? 'return' : 'sale' }}')" wire:loading.attr="disabled" class="font-semibold text-[#4338ca]">Post</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-[#f0f1f4]"><td colspan="8" class="px-[18px] py-[36px] text-center text-[#6b7280]">{{ $tab === 'returns' ? 'কোনো ফেরত post বাকি নেই।' : 'সব deliver হওয়া অর্ডার হিসাবে তোলা হয়েছে।' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-[#f0f1f4] px-[18px] py-[10px] text-[12px] text-[#6b7280]">
            @if ($tab === 'returns')
                Post হলে: Dr Sales return (+ VAT / charges / shipping on a full return) / Cr Receivable বা Customer credit (যতটা আগে পেয়েছেন) · cost stock-এ ফেরত।
            @else
                Post হলে: Dr Receivable / Cr Sales (+ shipping, VAT, charges, discount) আর Dr COGS / Cr Inventory (batch-এর দামে) · আগে নেওয়া COD / advance invoice-এ adjust হবে।
                Batch না বাছা product থাকলে post হবে না।
            @endif
        </div>
    </section>
</div>
