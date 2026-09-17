<div x-data x-init="$store.pageName = { name: 'Order #{{ $order->id }} — Ledger', slug: 'accounts-order-ledger' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sales.orders.show', ['id' => $order->id]) }}"
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Order #{{ $order->id }} — Ledger</h1>
                <p class="text-sm text-gray-400">
                    {{ $order->customer?->full_name ?? 'Guest' }}
                    @if($order->customer)
                        · <a href="{{ route('admin.accounts.reports.customer-ledger', ['customerId' => $order->customer->id]) }}" class="text-indigo-600 hover:text-indigo-700">Customer Ledger →</a>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-6 text-right">
            <div>
                <p class="text-xs text-gray-400">Total Debit</p>
                <p class="text-lg font-semibold text-gray-800">{{ number_format($totalDebit, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Total Credit</p>
                <p class="text-lg font-semibold text-gray-800">{{ number_format($totalCredit, 2) }}</p>
            </div>
        </div>
    </div>

    @if($entries->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center text-sm text-gray-400">
            No accounting entries posted for this order yet.
        </div>
    @else
        <div class="space-y-4">
            @foreach($entries as $entry)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 bg-gray-50/40 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-gray-800 text-sm">{{ $entry->entry_number }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {{ $entry->transaction_type->badgeClass() }}">
                                {{ $entry->transaction_type->label() }}
                            </span>
                            @if($entry->purpose)
                                <span class="text-xs text-gray-400 font-mono">{{ $entry->purpose }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-500">{{ $entry->entry_date->format('d M Y') }}</span>
                    </div>
                    <div class="px-5 py-3">
                        @if($entry->description)
                            <p class="text-xs text-gray-400 mb-3">{{ $entry->description }}</p>
                        @endif
                        <table class="min-w-full">
                            <thead>
                                <tr class="text-left">
                                    <th class="pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Account</th>
                                    <th class="pb-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Debit</th>
                                    <th class="pb-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($entry->lines as $line)
                                    <tr>
                                        <td class="py-1.5 text-sm text-gray-700">
                                            <span class="text-gray-400 font-mono text-xs">{{ $line->account->code }}</span>
                                            {{ $line->account->name }}
                                            @if($line->subledger_type === \App\Models\Customer::class)
                                                <span class="text-xs text-gray-400">(customer)</span>
                                            @endif
                                        </td>
                                        <td class="py-1.5 text-right text-sm text-emerald-600">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                                        <td class="py-1.5 text-right text-sm text-red-500">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-gray-100">
                                    <td class="pt-1.5 text-xs font-medium text-gray-500">Total</td>
                                    <td class="pt-1.5 text-right text-xs font-medium text-gray-700">{{ number_format($entry->totalDebit(), 2) }}</td>
                                    <td class="pt-1.5 text-right text-xs font-medium text-gray-700">{{ number_format($entry->totalCredit(), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
