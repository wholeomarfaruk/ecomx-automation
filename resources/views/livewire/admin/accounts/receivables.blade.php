<div x-data x-init="$store.pageName = { name: 'Receivables', slug: 'accounts-receivables' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">আমি পাবো (Receivables)</h1>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search customer…"
            class="px-3 py-2 text-sm rounded-lg border border-gray-300 w-64">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Open Invoices</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Due</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $customer->full_name }}</p>
                                <p class="text-xs text-gray-400">{{ $customer->phone }}</p>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500">
                                @foreach ($customer->openInvoices as $inv)
                                    <div>{{ $inv->invoice_number }} — due {{ number_format($inv->amountDue(), 2) }}</div>
                                @endforeach
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-red-500">{{ number_format($customer->totalDue, 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                <button wire:click="openPayModal({{ $customer->id }})" type="button" class="text-sm text-indigo-600 hover:text-indigo-700">Receive Payment</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No outstanding receivables.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $customers->links() }}</div>
    </div>

    @if ($payCustomerId)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('payCustomerId', null)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">টাকা পেলাম (Receive Payment)</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Amount</label>
                        <input type="number" step="0.01" wire:model="payAmount" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('payAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Received into</label>
                        <select wire:model="payCashAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select account</option>
                            @foreach ($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('payCashAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model="payDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>

                    @if ($payInvoices->isNotEmpty())
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Apply to specific invoices (optional — oldest-first otherwise)</label>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                @foreach ($payInvoices as $inv)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" wire:model="selectedInvoices.{{ $inv->id }}">
                                        {{ $inv->invoice_number }} — due {{ number_format($inv->amountDue(), 2) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('payCustomerId', null)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="receivePayment" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
