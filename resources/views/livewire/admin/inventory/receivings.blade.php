@php
    $in = 'rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
@endphp
<div x-data x-init="$store.pageName = { name: 'Receiving History', slug: 'inventory-receivings' }">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Receiving History · GRN</h1>
            <p class="text-xs text-gray-500">প্রতিটা delivery একটা receiving — ভেতরে product / variant অনুযায়ী আলাদা batch।</p>
        </div>
        <a href="{{ route('admin.inventory.stock-in') }}" wire:navigate class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Stock In</a>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4"><div class="text-xs text-gray-500">Receivings</div><div class="mt-1 text-xl font-semibold">{{ number_format($summary->n) }}</div></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4"><div class="text-xs text-gray-500">Quantity received</div><div class="mt-1 text-xl font-semibold">{{ $num($summary->qty) }}</div></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4"><div class="text-xs text-gray-500">Value (at cost)</div><div class="mt-1 text-xl font-semibold">৳{{ number_format((float) $summary->cost, 2) }}</div></div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 p-4">
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="GRN, PO, challan or batch no…" class="{{ $in }} w-64">
            <select wire:model.live="supplier" class="{{ $in }}"><option value="">All suppliers</option>@foreach ($suppliers as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
            <select wire:model.live="status" class="{{ $in }}"><option value="">Any status</option><option value="received">Received</option><option value="cancelled">Cancelled</option></select>
            <input wire:model.live="from" type="text" readonly placeholder="From" class="flatpickr-only-date {{ $in }} w-32 cursor-pointer bg-white">
            <input wire:model.live="to" type="text" readonly placeholder="To" class="flatpickr-only-date {{ $in }} w-32 cursor-pointer bg-white">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-[11px] uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-2.5">GRN</th><th class="px-3 py-2.5">Received</th><th class="px-3 py-2.5">Supplier · PO</th><th class="px-3 py-2.5">Warehouse</th>
                        <th class="px-3 py-2.5 text-right">Lines</th><th class="px-3 py-2.5 text-right">Qty</th><th class="px-3 py-2.5 text-right">Value</th><th class="px-4 py-2.5">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receivings as $r)
                        <tr wire:key="grn-{{ $r->id }}" class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3"><a href="{{ route('admin.inventory.receivings.show', $r) }}" wire:navigate class="font-mono font-medium text-indigo-600 hover:text-indigo-800">{{ $r->grn_number }}</a>
                                @if ($r->reference)<div class="text-xs text-gray-400">Challan {{ $r->reference }}</div>@endif</td>
                            <td class="whitespace-nowrap px-3 py-3 text-gray-700">{{ $r->received_at->format('d M Y, g:i A') }}<div class="text-xs text-gray-400">{{ $r->createdBy?->name }}</div></td>
                            <td class="px-3 py-3 text-gray-700">{{ $r->supplier?->name ?? '—' }}@if ($r->purchaseOrder)<div class="text-xs text-gray-400">PO {{ $r->purchaseOrder->order_number }}</div>@endif</td>
                            <td class="px-3 py-3 text-gray-700">{{ $r->warehouse?->name }}</td>
                            <td class="px-3 py-3 text-right">{{ $r->line_count }}</td>
                            <td class="px-3 py-3 text-right font-medium">{{ $num($r->total_quantity) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right">৳{{ number_format((float) $r->total_cost, 2) }}</td>
                            <td class="px-4 py-3">
                                @if ($r->status === 'cancelled')<span class="rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Cancelled</span>
                                @else<span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Received</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">কোনো receiving নেই।</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $receivings->links() }}</div>
    </div>
</div>
