{{-- Orders waiting to post (inside Posting Pending / dashboard). Renders nothing when there are none. --}}
@php
    $items = $this->items;
    $shown = $items->take($limit);
    $ready = $items->whereNull('blocked')->count();
    $canPost = \App\Support\AccountsAccess::can('post');
@endphp
<div>
    @if ($items->isNotEmpty())
        <section class="overflow-hidden rounded-[14px] border border-[#fde68a] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#fde68a] bg-[#fffbeb] px-[18px] py-[12px]">
                <div class="flex flex-wrap items-center gap-[10px]">
                    <span class="h-[8px] w-[8px] rounded-full bg-[#d97706]"></span>
                    <h2 class="m-0 text-[15px] font-bold">অর্ডার post বাকি · Orders</h2>
                    <span class="rounded-full bg-[#fef3c7] px-[10px] py-[2px] text-[12px] font-semibold text-[#92400e]">{{ $items->count() }} orders · {{ $money($items->sum('amount')) }}</span>
                </div>
                <div class="flex gap-[8px]">
                    <a href="{{ route('accounts.posting.orders') }}" wire:navigate class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] text-[13px] !text-[#374151]">View all</a>
                    @if ($canPost && $ready)
                        <button type="button" wire:click="postAllReady" wire:confirm="{{ $ready }}টা ready order post করবেন?" wire:loading.attr="disabled" class="rounded-[8px] border-0 bg-[#4f46e5] px-[14px] py-[7px] text-[13px] font-semibold text-white">Post all ready ({{ $ready }})</button>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="py-[9px] pl-[18px] pr-[8px] font-semibold">Order</th>
                            <th class="px-[8px] py-[9px] font-semibold">Customer</th>
                            <th class="px-[8px] py-[9px] font-semibold">Type</th>
                            <th class="px-[8px] py-[9px] text-right font-semibold">Amount</th>
                            <th class="px-[8px] py-[9px] font-semibold">Date</th>
                            <th class="py-[9px] pl-[8px] pr-[18px] text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shown as $r)
                            @php $o = $r['order']; @endphp
                            <tr wire:key="opc-{{ $r['kind'] }}-{{ $o->id }}" class="border-t border-[#f0f1f4]">
                                <td class="py-[10px] pl-[18px] pr-[8px]"><a href="{{ route('admin.sales.orders.show', $o->id) }}" target="_blank" class="font-bold">#{{ $o->id }}</a></td>
                                <td class="px-[8px] py-[10px]">{{ $o->customer?->full_name ?? '—' }}</td>
                                <td class="px-[8px] py-[10px]">
                                    <span @class(['rounded-[6px] px-[7px] py-[1px] text-[11.5px] font-semibold', 'bg-[#d1fae5] text-[#065f46]' => $r['kind'] === 'sale', 'bg-[#fee2e2] text-[#991b1b]' => $r['kind'] === 'return'])>{{ $r['kind'] === 'sale' ? 'Sale · delivered' : 'Return' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-[8px] py-[10px] text-right font-semibold">{{ $money($r['amount']) }}</td>
                                <td class="whitespace-nowrap px-[8px] py-[10px] text-[#6b7280]">{{ \Carbon\Carbon::parse($r['date'])->format('d M') }}</td>
                                <td class="py-[10px] pl-[8px] pr-[18px] text-right">
                                    @if ($r['blocked'])
                                        <a href="{{ route('admin.sales.orders.show', $o->id) }}" target="_blank" title="{{ $r['blocked'] }}" class="whitespace-nowrap rounded-[6px] bg-[#fef3c7] px-[8px] py-[2px] text-[12px] font-semibold !text-[#92400e]">Pick batch first</a>
                                    @elseif ($canPost)
                                        <button type="button" wire:click="post({{ $o->id }}, '{{ $r['kind'] }}')" wire:loading.attr="disabled" class="font-semibold text-[#4338ca]">Post</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($items->count() > $limit)
                <div class="border-t border-[#f0f1f4] px-[18px] py-[9px] text-[12.5px]"><a href="{{ route('accounts.posting.orders') }}" wire:navigate>আরও {{ $items->count() - $limit }}টা অর্ডার দেখুন</a></div>
            @endif
        </section>
    @endif
</div>
