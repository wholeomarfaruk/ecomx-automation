<div x-data x-init="$store.pageName = { name: 'Payables', slug: 'accounts-payables' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">আমি দিবো (Payables)</h1>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search supplier…"
            class="px-3 py-2 text-sm rounded-lg border border-gray-300 w-64">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Supplier</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Open Bills</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Due</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Advance</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($suppliers as $supplier)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $supplier->name }}</p>
                                <p class="text-xs text-gray-400">{{ $supplier->code }}</p>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500">
                                @foreach ($supplier->openBills as $bill)
                                    <div>Bill #{{ $bill->id }} — due {{ number_format($bill->amountDue(), 2) }}</div>
                                @endforeach
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-red-500">{{ number_format($supplier->totalDue, 2) }}</td>
                            <td class="px-5 py-3 text-xs">
                                @if ($supplier->openAdvances->isNotEmpty())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-600 mb-1">
                                        {{ number_format($supplier->totalAdvance, 2) }} unapplied
                                    </span>
                                    @if ($supplier->openBills->isNotEmpty())
                                        <button wire:click="openApplyAdvanceModal({{ $supplier->openAdvances->first()->id }})" type="button"
                                            class="block text-indigo-600 hover:text-indigo-700">Apply to bill</button>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($supplier->openBills->isNotEmpty())
                                    <button wire:click="openPayModal({{ $supplier->id }})" type="button" class="text-sm text-indigo-600 hover:text-indigo-700">Pay</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400">No outstanding payables.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $suppliers->links() }}</div>
    </div>

    @if ($paySupplierId)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('paySupplierId', null)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">টাকা দিলাম (Pay Supplier)</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Amount</label>
                        <input type="number" step="0.01" wire:model="payAmount" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('payAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Paid from</label>
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

                    @if ($payBills->isNotEmpty())
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Apply to specific bills (optional — oldest-first otherwise)</label>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                @foreach ($payBills as $bill)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" wire:model="selectedBills.{{ $bill->id }}">
                                        Bill #{{ $bill->id }} — due {{ number_format($bill->amountDue(), 2) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('paySupplierId', null)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="payNow" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif

    @if ($applyAdvanceId)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('applyAdvanceId', null)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Apply Advance to Bill</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model="applyAdvanceDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('applyAdvanceDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if ($applyAdvanceBillOptions->isNotEmpty())
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Apply to specific bills (optional — oldest-first otherwise, capped by the advance's remaining balance)</label>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                @foreach ($applyAdvanceBillOptions as $bill)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" wire:model="applyAdvanceBills.{{ $bill->id }}">
                                        Bill #{{ $bill->id }} — due {{ number_format($bill->amountDue(), 2) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @error('applyAdvanceBills') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('applyAdvanceId', null)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="applyAdvanceNow" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Apply</button>
                </div>
            </div>
        </div>
    @endif
</div>
