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
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.purchase.suppliers.ledger', $invoice->supplier_id) }}" wire:navigate
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition" title="Open ledger">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                    </svg>
                                </a>
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

</div>
