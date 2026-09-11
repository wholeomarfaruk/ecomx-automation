<div x-data x-init="$store.pageName = { name: 'Owner', slug: 'accounts-owner-equity' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">মালিক (Owner)</h1>
        <div class="flex gap-2">
            <button wire:click="openModal('investment')" type="button"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-sm">
                + মালিক টাকা দিলো
            </button>
            <button wire:click="openModal('withdrawal')" type="button"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-xl hover:bg-red-600 transition shadow-sm">
                − মালিক টাকা তুললো
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs text-gray-400">Total Invested</p>
            <p class="text-2xl font-semibold text-emerald-600 mt-1">{{ number_format($totalInvested, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs text-gray-400">Total Withdrawn</p>
            <p class="text-2xl font-semibold text-red-500 mt-1">{{ number_format($totalWithdrawn, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs text-gray-400">নিট বিনিয়োগ (Net)</p>
            <p class="text-2xl font-semibold text-gray-800 mt-1">{{ number_format($netInvestment, 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->transaction_type->badgeClass() }}">
                                    {{ $entry->transaction_type->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($entry->totalDebit(), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-sm text-gray-400">No owner transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $entries->links() }}</div>
    </div>

    @if ($modal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('modal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    {{ $mode === 'investment' ? 'মালিক টাকা দিলো (Investment)' : 'মালিক টাকা তুললো (Withdrawal)' }}
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Amount</label>
                        <input type="number" step="0.01" wire:model="amount" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">{{ $mode === 'investment' ? 'Received into' : 'Paid from' }}</label>
                        <select wire:model="assetAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select account</option>
                            @foreach ($assetAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('assetAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model="entryDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('modal', false)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="save" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
