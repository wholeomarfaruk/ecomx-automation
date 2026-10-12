@php
    $r = $receiving;
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
@endphp
<div x-data x-init="$store.pageName = { name: '{{ $r->grn_number }}', slug: 'inventory-receivings' }">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inventory.receivings') }}" wire:navigate class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-900">Goods Receiving · <span class="font-mono">{{ $r->grn_number }}</span></h1>
            @if ($r->status === 'cancelled')<span class="rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Cancelled</span>
            @else<span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Received</span>@endif
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="window.print()" class="rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">Print GRN</button>
            @if ($r->status === 'received')
                <button type="button" wire:click="$set('showCancel', true)" class="rounded-lg border border-red-200 bg-white px-3.5 py-2 text-sm text-red-600 hover:bg-red-50">Cancel receiving</button>
            @endif
            <a href="{{ route('admin.inventory.stock-in') }}" wire:navigate class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ New Stock In</a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-5 hidden print:block">
            <div class="text-lg font-semibold">Goods Received Note · {{ $r->grn_number }}</div>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
            <div><div class="text-[11px] uppercase text-gray-400">Received</div><div class="font-medium">{{ $r->received_at->format('d M Y, g:i A') }}</div><div class="text-xs text-gray-500">by {{ $r->createdBy?->name ?? '—' }}</div></div>
            <div><div class="text-[11px] uppercase text-gray-400">Supplier</div><div class="font-medium">{{ $r->supplier?->name ?? '—' }}</div><div class="text-xs text-gray-500">{{ $r->supplier?->phone }}</div></div>
            <div><div class="text-[11px] uppercase text-gray-400">Purchase order · Challan</div><div class="font-medium">{{ $r->purchaseOrder?->order_number ?? 'Direct' }}</div><div class="text-xs text-gray-500">{{ $r->reference ?: '—' }}</div></div>
            <div><div class="text-[11px] uppercase text-gray-400">Warehouse</div><div class="font-medium">{{ $r->warehouse?->name }}</div></div>
        </div>
        @if ($r->note)<div class="mt-4 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $r->note }}</div>@endif
        @if ($r->status === 'cancelled')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">Cancelled {{ $r->cancelled_at?->format('d M Y, g:i A') }} by {{ $r->cancelledBy?->name ?? '—' }} — {{ $r->cancel_reason }}</div>
        @endif

        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[800px] text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-[11px] uppercase tracking-wide text-gray-500">
                        <th class="px-3 py-2.5">#</th><th class="px-3 py-2.5">Product / Variant</th><th class="px-3 py-2.5">Batch</th><th class="px-3 py-2.5">Mfg · Expiry</th>
                        <th class="px-3 py-2.5 text-right">Qty</th><th class="px-3 py-2.5 text-right">Unit cost</th><th class="px-3 py-2.5 text-right">Total</th><th class="px-3 py-2.5 text-right print:hidden">Batch left</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($r->items as $i => $it)
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2.5 text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-2.5"><div class="font-medium text-gray-800">{{ $it->product?->name }}</div>
                                @if ($it->variant)<div class="text-xs text-gray-500">{{ $it->variant->sku }}{{ $it->variant->options_map ? ' · ' . implode(' / ', $it->variant->options_map) : '' }}</div>@endif</td>
                            <td class="px-3 py-2.5 font-mono text-xs">{{ $it->batch_no }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-xs text-gray-600">{{ $it->manufacture_date?->format('d M Y') ?? '—' }} · {{ $it->expiry_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-right font-medium">{{ $num($it->quantity) }}</td>
                            <td class="px-3 py-2.5 text-right">{{ $it->unit_cost !== null ? number_format((float) $it->unit_cost, 2) : '—' }}</td>
                            <td class="px-3 py-2.5 text-right">{{ number_format((float) $it->line_total, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-gray-500 print:hidden">{{ $it->batch ? $num($it->batch->quantity) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 bg-gray-50 font-semibold">
                        <td colspan="4" class="px-3 py-2.5">{{ $r->line_count }} lines</td>
                        <td class="px-3 py-2.5 text-right">{{ $num($r->total_quantity) }}</td>
                        <td></td>
                        <td class="px-3 py-2.5 text-right">{{ number_format((float) $r->total_cost, 2) }}</td>
                        <td class="print:hidden"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-10 hidden grid-cols-3 gap-8 text-center text-xs text-gray-500 print:grid">
            <div class="border-t border-gray-300 pt-2">Received by</div><div class="border-t border-gray-300 pt-2">Checked by</div><div class="border-t border-gray-300 pt-2">Approved by</div>
        </div>
    </div>

    @if ($showCancel)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="$set('showCancel', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-gray-900">Cancel {{ $r->grn_number }}?</h3>
                <p class="mt-1 text-sm text-gray-500">প্রতিটা line-এর quantity তার batch থেকে বের করে নেওয়া হবে। কোনো batch-এর মাল বিক্রি হয়ে গেলে cancel হবে না। PO থাকলে আবার pending হবে।</p>
                <label class="mt-4 mb-1.5 block text-xs font-medium text-gray-600">Reason <span class="text-red-500">*</span></label>
                <input wire:model="cancelReason" type="text" placeholder="যেমন: ভুল supplier-এর নামে receive হয়েছিল" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('cancelReason') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                @error('cancel') <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ $message }}</p> @enderror
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="$set('showCancel', false)" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700">Keep</button>
                    <button type="button" wire:click="cancel" wire:loading.attr="disabled" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Cancel receiving</button>
                </div>
            </div>
        </div>
    @endif
</div>
