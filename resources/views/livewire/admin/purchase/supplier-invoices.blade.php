<div x-data x-init="$store.pageName = { name: 'Invoices', slug: 'purchase-invoices' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-3 gap-3 flex-1 max-w-xl">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Invoices</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
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
        <button wire:click="openInvoiceModal" type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Invoice
        </button>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by supplier, invoice #, notes…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <x-searchable-select field="filterSupplier" :value="$filterSupplier"
                :options="$suppliers->pluck('name', 'id')" placeholder="All Suppliers" search-placeholder="Search suppliers…" />
            <select wire:model.live="filterType"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Types</option>
                @foreach(\App\Enums\Purchase\SupplierInvoiceType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input wire:model.live="dateFrom" type="text" placeholder="From" readonly
                    class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-32 cursor-pointer">
            </div>
            <span class="text-gray-400 text-sm">–</span>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input wire:model.live="dateTo" type="text" placeholder="To" readonly
                    class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-32 cursor-pointer">
            </div>
            @if($search || $filterType || $filterSupplier || $dateFrom || $dateTo)
                <button wire:click="resetFilters" type="button" class="text-sm text-gray-500 hover:text-gray-700 transition">Clear</button>
            @endif
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Supplier</th>
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
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-400 font-mono tabular-nums">#{{ str_pad($invoice->serial_number, 6, '0', STR_PAD_LEFT) }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.purchase.suppliers.ledger', $invoice->supplier_id) }}" wire:navigate class="hover:underline">
                                    <span class="text-sm font-medium text-gray-800">{{ $invoice->supplier->name ?? '—' }}</span>
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $invoice->type->badgeClass() }}">
                                        {{ $invoice->type->label() }}
                                    </span>
                                    @if($invoice->is_adjusted)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-600">Adjusted</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm text-gray-600">{{ $invoice->invoice_number ?? '—' }}</span>
                                    @if(($invoice->documents ?? collect())->isNotEmpty())
                                        <div class="relative" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" @click.outside="open = false"
                                                class="inline-flex items-center gap-0.5 text-gray-400 hover:text-indigo-600 transition" title="{{ $invoice->documents->count() }} document(s)">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                                </svg>
                                                <span class="text-[11px]">{{ $invoice->documents->count() }}</span>
                                            </button>
                                            <div x-show="open" x-cloak x-transition
                                                class="absolute z-20 top-full left-0 mt-1 w-48 rounded-lg border border-gray-200 bg-white shadow-lg py-1">
                                                @foreach($invoice->documents as $document)
                                                    <a href="{{ file_path($document->id) }}" download="{{ $document->name }}" target="_blank"
                                                        class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                        </svg>
                                                        <span class="truncate">{{ $document->name }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $invoice->invoice_date?->format('d M, Y') ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm text-gray-500">{{ $invoice->items_count ?: '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-semibold tabular-nums {{ $invoice->type->isDebit() ? 'text-red-500' : 'text-emerald-600' }}">
                                    {{ $invoice->type->isDebit() ? '+' : '−' }}{{ number_format($invoice->amount, 2) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="openEditInvoiceModal({{ $invoice->id }})" type="button"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition" title="Edit invoice">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                    </button>
                                    <a href="{{ route('admin.purchase.suppliers.ledger', $invoice->supplier_id) }}" wire:navigate
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition" title="Open ledger">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No invoices found</p>
                                <p class="text-xs text-gray-400 mt-0.5">Try adjusting your filters, or record a transaction from a supplier's ledger</p>
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

    {{-- Add / Edit Invoice Modal --}}
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
                    <h2 class="text-base font-semibold text-gray-900">{{ $editingInvoiceId ? 'Edit Invoice' : 'Add Invoice' }}</h2>
                </div>
                <button wire:click="closeInvoiceModal" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-5 space-y-5">
                @if($editingIsLocked)
                    <div class="flex items-start gap-2.5 rounded-xl bg-amber-50 border border-amber-100 px-4 py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                        </svg>
                        <p class="text-xs text-amber-700">Only the most recent invoice can have its type, amount, or items changed. You can still update the invoice number, date, adjusted flag, notes, and documents here.</p>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Supplier <span class="text-red-500">*</span></label>
                    <x-searchable-select field="modalSupplierId" :value="$modalSupplierId" :disabled="(bool) $editingInvoiceId"
                        :options="$suppliers->pluck('name', 'id')" placeholder="— Select supplier —" search-placeholder="Search suppliers…" />
                    @error('modalSupplierId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                @if($modalSupplierId !== '' && ! $editingInvoiceId)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Purchase Order (optional)</label>
                        <x-searchable-select wire:key="po-select-{{ $modalSupplierId }}"
                            field="purchaseOrderId" :value="$purchaseOrderId"
                            :options="$purchaseOrders->mapWithKeys(fn ($po) => [
                                (string) $po->id => $po->order_number . ' — ordered ' . number_format($po->total_amount, 2) . ', invoiced ' . number_format($po->invoiced_total, 2),
                            ])"
                            placeholder="— None — (manual entry) —" search-placeholder="Search purchase orders…" />
                        <p class="text-xs text-gray-400 mt-1.5">Selecting a PO fills in its items below — fully editable afterwards. A PO can be picked again later for its next partial invoice.</p>
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Type <span class="text-red-500">*</span></label>
                        <select wire:model.live="invoiceType" @disabled($editingIsLocked || $purchaseOrderId !== '') class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                            @foreach(\App\Enums\Purchase\SupplierInvoiceType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
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
                    @unless($editingIsLocked)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Add Item</label>
                            <div class="relative mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                                </svg>
                                <input wire:model.live.debounce.300ms="productSearch" type="text" placeholder="Search product or variant by name / SKU…"
                                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                            @if($productSearch !== '')
                                <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-40 overflow-y-auto mb-3">
                                    @forelse($productOptions as $key => $label)
                                        <button wire:click="addItem('{{ $key }}')" type="button"
                                            class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 transition text-left">
                                            <span class="text-sm text-gray-800">{{ $label }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                            </svg>
                                        </button>
                                    @empty
                                        <p class="px-3 py-2 text-xs text-gray-400">No matching products.</p>
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
                    @endunless

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
                                        @unless($editingIsLocked)
                                            <th class="px-3 py-2 w-8"></th>
                                        @endunless
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($items as $i => $item)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.name" type="text" placeholder="Item name" @disabled($editingIsLocked)
                                                    class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                                @if($item['purchase_order_item_id'] !== '')
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-500 mt-1">From PO</span>
                                                @endif
                                                @error('items.' . $i . '.name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-3 py-2">
                                                <input wire:model.live="items.{{ $i }}.quantity" type="number" step="0.001" min="0" @disabled($editingIsLocked)
                                                    class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input wire:model.live="items.{{ $i }}.unit_price" type="number" step="0.01" min="0" @disabled($editingIsLocked)
                                                    class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $i }}.amount" type="number" step="0.01" min="0" @disabled($editingIsLocked)
                                                    class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm text-right focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                                @error('items.' . $i . '.amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                            </td>
                                            @unless($editingIsLocked)
                                                <td class="px-3 py-2 text-right">
                                                    <button wire:click="removeItem({{ $i }})" type="button" class="text-gray-400 hover:text-red-500 transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </td>
                                            @endunless
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

                    @if($invoiceType === 'purchase' && ! $editingInvoiceId)
                        <div class="rounded-xl border border-gray-200 px-4 py-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input wire:model.live="markPaidNow" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Mark as paid now</span>
                            </label>
                            @if($markPaidNow)
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Amount Paid <span class="text-red-500">*</span></label>
                                        <input wire:model="payNowAmount" type="number" step="0.01" min="0" placeholder="0.00"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @error('payNowAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Paid From <span class="text-red-500">*</span></label>
                                        <x-searchable-select field="payNowCashAccountId" :value="$payNowCashAccountId"
                                            :options="$cashAccounts->mapWithKeys(fn ($a) => [(string) $a->id => $a->code . ' — ' . $a->name])"
                                            placeholder="— Select account —" search-placeholder="Search accounts…" />
                                        @error('payNowCashAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                @elseif($invoiceType === 'payment')
                    {{-- Payment: applied against this supplier's open bills --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Amount <span class="text-red-500">*</span></label>
                        <input wire:model="manualAmount" type="number" step="0.01" min="0" placeholder="0.00" @disabled($editingIsLocked)
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                        @error('manualAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @unless($editingInvoiceId)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Paid From <span class="text-red-500">*</span></label>
                            <x-searchable-select field="paymentCashAccountId" :value="$paymentCashAccountId"
                                :options="$cashAccounts->mapWithKeys(fn ($a) => [(string) $a->id => $a->code . ' — ' . $a->name])"
                                placeholder="— Select cash/bank account —" search-placeholder="Search accounts…" />
                            @error('paymentCashAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if($openBills->isNotEmpty())
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Apply to specific bills (optional — oldest-first otherwise)</label>
                                <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-40 overflow-y-auto">
                                    @foreach($openBills as $bill)
                                        <label class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50">
                                            <input type="checkbox" wire:model="paymentBills.{{ $bill->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="flex-1">Bill #{{ $bill->id }}{{ $bill->supplierInvoice ? ' — Purchase #' . str_pad($bill->supplierInvoice->serial_number, 6, '0', STR_PAD_LEFT) : '' }}</span>
                                            <span class="text-gray-500">due {{ number_format($bill->amountDue(), 2) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-gray-400">This supplier has no open bills — the payment will still be recorded and reduce their balance.</p>
                        @endif
                    @endunless
                @else
                    {{-- Manual amount (advance) --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Amount <span class="text-red-500">*</span></label>
                        <input wire:model="manualAmount" type="number" step="0.01" min="0" placeholder="0.00" @disabled($editingIsLocked)
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                        @error('manualAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if($invoiceType === 'advance' && ! $editingInvoiceId)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Paid From <span class="text-red-500">*</span></label>
                            <x-searchable-select field="advanceCashAccountId" :value="$advanceCashAccountId"
                                :options="$cashAccounts->mapWithKeys(fn ($a) => [(string) $a->id => $a->code . ' — ' . $a->name])"
                                placeholder="— Select cash/bank account —" search-placeholder="Search accounts…" />
                            <p class="text-xs text-gray-400 mt-1.5">Posts Dr Supplier Advance / Cr this account — it can later be applied against a bill from Accounts &gt; Payables.</p>
                            @error('advanceCashAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
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

                <x-document-picker-field field="documentIds" :value="$documentIds" label="Documents"
                    placeholder="Attach invoice copies, receipts, or bills" />
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0">
                <button wire:click="closeInvoiceModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="saveInvoice" type="button" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">{{ $editingInvoiceId ? 'Update Invoice' : 'Save Invoice' }}</button>
            </div>
        </div>
    </div>

</div>
