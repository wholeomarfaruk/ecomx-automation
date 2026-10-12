@php
    $in = 'w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';
    $cell = 'w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
@endphp
<div x-data x-init="$store.pageName = { name: 'Stock In', slug: 'inventory-stock-in' }">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inventory.stock') }}" wire:navigate
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Stock In · Goods Receiving</h1>
                <p class="text-xs text-gray-500">একবারে পুরো delivery receive করুন — প্রতিটা product / variant আলাদা batch-এ যাবে।</p>
            </div>
        </div>
        <a href="{{ route('admin.inventory.receivings') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">Receiving history</a>
    </div>

    <form wire:submit.prevent="save" class="space-y-5">

        {{-- Receiving details --}}
        <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-800">Receiving details</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Purchase Order <span class="text-gray-400">(optional)</span></label>
                    <select wire:model.live="purchaseOrderId" class="{{ $in }}">
                        <option value="">— Direct receiving (no PO) —</option>
                        @foreach ($purchaseOrders as $po)
                            <option value="{{ $po->id }}">{{ $po->order_number }} — {{ $po->supplier->name ?? 'Unknown supplier' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">PO বাছলে বাকি থাকা সব line নিজে থেকে নিচে চলে আসবে।</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Supplier</label>
                    <div wire:key="supplier-{{ $purchaseOrderId }}">
                        <x-searchable-select field="supplierId" :value="$supplierId" :options="$suppliers" placeholder="— None —" search-placeholder="Search suppliers…" :disabled="$purchaseOrderId !== ''" />
                    </div>
                    @error('supplierId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Warehouse <span class="text-red-500">*</span></label>
                    <select wire:model.live="warehouseId" class="{{ $in }}">
                        @foreach ($warehouses as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                    @error('warehouseId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Received at <span class="text-red-500">*</span></label>
                    <input wire:model="receivedAt" type="text" readonly class="flatpickr {{ $in }} cursor-pointer bg-white" placeholder="YYYY-MM-DD HH:MM">
                    @error('receivedAt') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Supplier challan / invoice no</label>
                    <input wire:model="reference" type="text" placeholder="যেমন: CH-2231" class="{{ $in }}">
                    @error('reference') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Note</label>
                    <input wire:model="note" type="text" placeholder="যেমন: 2 carton damaged, supplier-কে জানানো হয়েছে" class="{{ $in }}">
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-gray-100 p-5">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Items received</h2>
                    <p class="text-xs text-gray-400">Product বাছলে তার সব variant আলাদা line হয়ে আসবে — যেগুলো আসেনি সেগুলো মুছে দিন।</p>
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <div class="w-72">
                        <label class="mb-1 block text-[11px] text-gray-500">+ Add product (all variants)</label>
                        <div wire:key="add-product-{{ count($lines) }}">
                            <x-searchable-select field="addProductId" :value="$addProductId" :options="$products" placeholder="Search a product…" search-placeholder="Search products…" />
                        </div>
                    </div>
                    <div class="w-56">
                        <label class="mb-1 block text-[11px] text-gray-500">Scan / type SKU</label>
                        <input wire:model="scan" wire:keydown.enter.prevent="scanSku" type="text" placeholder="SKU + Enter" class="{{ $cell }} py-2 font-mono">
                    </div>
                    <button type="button" wire:click="addLine" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">+ Blank line</button>
                </div>
            </div>

            @error('lines') <p class="px-5 pt-3 text-xs text-red-500">{{ $message }}</p> @enderror

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-[11px] uppercase tracking-wide text-gray-500">
                            <th class="w-8 px-3 py-2.5">#</th>
                            <th class="px-2 py-2.5">Product / Variant</th>
                            <th class="w-28 px-2 py-2.5">Qty</th>
                            <th class="w-32 px-2 py-2.5">Unit cost</th>
                            <th class="w-56 px-2 py-2.5">Batch</th>
                            <th class="w-60 px-2 py-2.5">Mfg · Expiry</th>
                            <th class="w-28 px-2 py-2.5 text-right">Total</th>
                            <th class="w-20 px-3 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $i => $line)
                            @php
                                $pid = (int) $line['product_id'];
                                $vopts = $variantOptions[$pid] ?? collect();
                                $bopts = $batches[$pid . '|' . ($line['variant_id'] !== '' ? $line['variant_id'] : '')] ?? collect();
                                $po = $line['purchase_order_item_id'] ? ($poItems[$line['purchase_order_item_id']] ?? null) : null;
                            @endphp
                            <tr wire:key="line-{{ $line['uid'] }}" class="border-t border-gray-100 align-top">
                                <td class="px-3 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-2 py-2.5">
                                    @if ($line['purchase_order_item_id'])
                                        <div class="font-medium text-gray-800">{{ $productNames[$pid] ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">{{ $line['variant_id'] !== '' ? ($vopts[$line['variant_id']] ?? '') : '' }}</div>
                                        @if ($po)
                                            <span class="mt-1 inline-block rounded bg-indigo-50 px-1.5 py-0.5 text-[11px] text-indigo-700">PO line · received {{ $num($po['received']) }} of {{ $num($po['ordered']) }}</span>
                                        @endif
                                    @else
                                        <div wire:key="p-{{ $line['uid'] }}">
                                            <x-searchable-select field="lines.{{ $i }}.product_id" :value="$line['product_id']" :options="$products" placeholder="— Product —" search-placeholder="Search products…" />
                                        </div>
                                        @if ($vopts->isNotEmpty())
                                            <div class="mt-1.5" wire:key="v-{{ $line['uid'] }}-{{ $pid }}">
                                                <x-searchable-select field="lines.{{ $i }}.variant_id" :value="$line['variant_id']" :options="$vopts" placeholder="— Variant —" search-placeholder="Search SKU / size / colour…" />
                                            </div>
                                        @endif
                                    @endif
                                    @error("lines.{$i}.product_id") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    @error("lines.{$i}.variant_id") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-2 py-2.5">
                                    <input wire:model.live.debounce.400ms="lines.{{ $i }}.quantity" type="number" step="0.001" min="0" class="{{ $cell }}">
                                    @error("lines.{$i}.quantity") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-2 py-2.5">
                                    <input wire:model.live.debounce.400ms="lines.{{ $i }}.unit_cost" type="number" step="0.01" min="0" placeholder="0.00" class="{{ $cell }}">
                                    @if ($line['product_id'] !== '' && $line['unit_cost'] !== '' && $line['batch_id'] === '')
                                        <button type="button" wire:click="applyToProduct({{ $i }}, 'unit_cost')" class="mt-1 text-[11px] text-indigo-600 hover:text-indigo-800">Same for all variants</button>
                                    @endif
                                    @error("lines.{$i}.unit_cost") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-2 py-2.5">
                                    <select wire:model.live="lines.{{ $i }}.batch_id" class="{{ $cell }}">
                                        <option value="">+ New batch</option>
                                        @foreach ($bopts as $b)
                                            <option value="{{ $b->id }}">{{ $b->batch_no }} — {{ $num($b->quantity) }} in stock{{ $b->expiry_date ? ' · exp ' . $b->expiry_date->format('d M y') : '' }}</option>
                                        @endforeach
                                    </select>
                                    @if ($line['batch_id'] === '')
                                        <input wire:model="lines.{{ $i }}.batch_no" type="text" placeholder="Auto (BATCH-{{ now()->format('Ymd') }}-…)" class="{{ $cell }} mt-1.5 font-mono text-xs">
                                    @endif
                                    @error("lines.{$i}.batch_id") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    @error("lines.{$i}.batch_no") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-2 py-2.5">
                                    @if ($line['batch_id'] === '')
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <input wire:model="lines.{{ $i }}.manufacture_date" type="text" readonly placeholder="Mfg" class="flatpickr-only-date {{ $cell }} cursor-pointer bg-white">
                                            <input wire:model="lines.{{ $i }}.expiry_date" type="text" readonly placeholder="Expiry" class="flatpickr-only-date {{ $cell }} cursor-pointer bg-white">
                                        </div>
                                        @if ($line['expiry_date'] !== '' && $line['product_id'] !== '')
                                            <button type="button" wire:click="applyToProduct({{ $i }}, 'expiry_date')" class="mt-1 text-[11px] text-indigo-600 hover:text-indigo-800">Same expiry for all variants</button>
                                        @endif
                                    @else
                                        <div class="pt-1.5 text-xs text-gray-500">{{ $line['manufacture_date'] ?: '—' }} · {{ $line['expiry_date'] ?: '—' }}</div>
                                    @endif
                                    @error("lines.{$i}.expiry_date") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="whitespace-nowrap px-2 py-3 text-right font-medium text-gray-700">
                                    {{ $line['quantity'] !== '' && $line['unit_cost'] !== '' ? number_format((float) $line['quantity'] * (float) $line['unit_cost'], 2) : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right">
                                    <button type="button" wire:click="duplicateLine({{ $i }})" title="Same item, another batch" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-indigo-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                                    </button>
                                    <button type="button" wire:click="removeLine({{ $i }})" title="Remove" class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-12 text-center text-sm text-gray-400">এখনো কিছু যোগ করা হয়নি — PO বাছুন, product যোগ করুন বা SKU scan করুন।</td></tr>
                        @endforelse
                    </tbody>
                    @if ($lines)
                        <tfoot>
                            <tr class="border-t-2 border-gray-200 bg-gray-50 text-sm font-semibold text-gray-800">
                                <td colspan="2" class="px-3 py-3">{{ $totals['lines'] }} lines</td>
                                <td class="px-2 py-3">{{ $num($totals['qty']) }}</td>
                                <td colspan="3"></td>
                                <td class="whitespace-nowrap px-2 py-3 text-right">{{ number_format($totals['cost'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-gray-400">Save করলে একটা GRN হবে, প্রতিটা line-এর জন্য আলাদা batch। হিসাবের entry supplier invoice থেকে হয় — এখান থেকে নয়।</p>
            <button type="submit" wire:loading.attr="disabled" @disabled(! $lines)
                class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Save receiving{{ $lines ? ' · ' . $totals['lines'] . ' lines' : '' }}</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
