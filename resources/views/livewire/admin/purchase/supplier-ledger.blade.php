<div x-data x-init="$store.pageName = { name: 'Supplier Ledger', slug: 'purchase-supplier-ledger' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.purchase.suppliers') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ $supplier->name }}</h1>
                <p class="text-xs text-gray-400 font-mono">{{ $supplier->code }}</p>
            </div>
        </div>
        <button wire:click="openInvoiceModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Record Transaction
        </button>
    </div>

    {{-- Stat tiles --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Current Balance</p>
            <p class="text-xl font-semibold {{ $supplier->balance > 0 ? 'text-red-500' : 'text-emerald-600' }} mt-0.5">{{ number_format($supplier->balance, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Total Purchased</p>
            <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ number_format($purchaseTotal, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Total Paid</p>
            <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ number_format($paidTotal, 2) }}</p>
        </div>
    </div>

    {{-- Ledger --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Invoice No.</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $invoice)
                        @php
                            $typeStyles = [
                                'purchase' => 'bg-indigo-50 text-indigo-600',
                                'advance'  => 'bg-blue-50 text-blue-600',
                                'payment'  => 'bg-emerald-50 text-emerald-600',
                                'return'   => 'bg-red-50 text-red-500',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-400 font-mono tabular-nums">#{{ str_pad($invoice->serial_number, 6, '0', STR_PAD_LEFT) }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $typeStyles[$invoice->type] ?? 'bg-gray-100 text-gray-500' }}">
                                        {{ $invoice->type }}
                                    </span>
                                    @if($invoice->is_adjusted)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-600">Adjusted</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-600">{{ $invoice->invoice_number ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $invoice->invoice_date?->format('d M, Y') ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm text-gray-500">{{ $invoice->items_count ?: '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($invoice->amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button type="button" x-data
                                    @click="Swal.fire({
                                        title: 'Delete transaction?',
                                        text: 'This will remove #{{ $invoice->serial_number }} and adjust the supplier balance.',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#ef4444',
                                        confirmButtonText: 'Delete'
                                    }).then(r => { if (r.isConfirmed) $wire.deleteInvoice({{ $invoice->id }}) })"
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No transactions yet</p>
                                <p class="text-xs text-gray-400 mt-0.5">Record a purchase, advance, payment, or return to start the ledger</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    {{-- Record Transaction Modal --}}
    <div x-cloak x-data="{ open: @entangle('invoiceModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Record Transaction</h2>
                    <p class="text-xs text-gray-400">{{ $supplier->name }}</p>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-5 space-y-5">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Type <span class="text-red-500">*</span></label>
                        <select wire:model.live="invoiceType" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="purchase">Purchase</option>
                            <option value="advance">Advance</option>
                            <option value="payment">Payment</option>
                            <option value="return">Return</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Invoice No.</label>
                        <input wire:model="invoiceNumber" type="text" placeholder="Supplier's invoice #"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Date</label>
                        <input wire:model="invoiceDate" type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                @if(in_array($invoiceType, ['purchase', 'return']))
                    {{-- Item-based --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Add Item</label>
                        <div class="relative mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            </svg>
                            <input wire:model.live.debounce.300ms="variantSearch" type="text" placeholder="Search product variant by name or SKU…"
                                class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        @if($variantSearch !== '')
                            <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-40 overflow-y-auto mb-3">
                                @forelse($variantOptions as $variant)
                                    <button wire:click="addItem({{ $variant->id }})" type="button"
                                        class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 transition text-left">
                                        <div>
                                            <span class="text-sm text-gray-800">{{ $variant->product->name }}</span>
                                            <span class="block text-xs font-mono text-gray-400">{{ $variant->sku }}</span>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                        </svg>
                                    </button>
                                @empty
                                    <p class="px-3 py-2 text-xs text-gray-400">No matching variants.</p>
                                @endforelse
                            </div>
                        @endif
                        <button wire:click="addItem" type="button"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Add custom line item
                        </button>
                    </div>

                    @error('items') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                    @if(!empty($items))
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50/60 text-left text-xs text-gray-500 uppercase">
                                        <th class="px-3 py-2">Item</th>
                                        <th class="px-3 py-2 w-20">Qty</th>
                                        <th class="px-3 py-2 w-24">Unit Price</th>
                                        <th class="px-3 py-2 w-24 text-right">Amount</th>
                                        <th class="px-3 py-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($items as $i => $item)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.name" type="text" placeholder="Item name"
                                                    class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                @error('items.' . $i . '.name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-3 py-2">
                                                <input wire:model.live="items.{{ $i }}.quantity" type="number" step="0.001" min="0"
                                                    class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input wire:model.live="items.{{ $i }}.unit_price" type="number" step="0.01" min="0"
                                                    class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.amount" type="number" step="0.01" min="0"
                                                    class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm text-right focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                @error('items.' . $i . '.amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <button wire:click="removeItem({{ $i }})" type="button" class="text-gray-400 hover:text-red-500 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="flex items-center justify-end px-3 py-2.5 bg-gray-50/60 border-t border-gray-100">
                                <span class="text-xs text-gray-500 mr-2">Total:</span>
                                <span class="text-sm font-semibold text-gray-800">{{ number_format($this->itemsTotal, 2) }}</span>
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Manual amount (advance / payment) --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Amount <span class="text-red-500">*</span></label>
                        <input wire:model="manualAmount" type="number" step="0.01" min="0" placeholder="0.00"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('manualAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input wire:model="invoiceIsAdjusted" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Mark as adjusted</span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Notes</label>
                    <textarea wire:model="invoiceNotes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0">
                <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="saveInvoice" type="button" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Save Transaction</button>
            </div>
        </div>
    </div>

</div>
