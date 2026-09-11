<div x-data x-init="$store.pageName = { name: 'Loans', slug: 'accounts-loans' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">ঋণ (Loans)</h1>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            + ঋণ নিলাম
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Loan</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Principal</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Outstanding</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($loans as $loan)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $loan->name }}</p>
                                <p class="text-xs text-gray-400">{{ $loan->lender }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-gray-600">{{ number_format($loan->principal, 2) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-red-500">{{ number_format($loan->outstanding, 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $loan->status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($loan->status === 'active')
                                    <button wire:click="openRepayModal({{ $loan->id }})" type="button" class="text-sm text-indigo-600 hover:text-indigo-700">Repay</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400">No loans yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $loans->links() }}</div>
    </div>

    @if ($createModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">ঋণ নিলাম (Loan Received)</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Name</label>
                        <input type="text" wire:model="newName" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Lender</label>
                        <input type="text" wire:model="newLender" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Principal</label>
                            <input type="number" step="0.01" wire:model="newPrincipal" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            @error('newPrincipal') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Interest rate %</label>
                            <input type="number" step="0.01" wire:model="newInterestRate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Start date</label>
                            <input type="date" wire:model="newStartDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Term (months)</label>
                            <input type="number" wire:model="newTermMonths" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Received into</label>
                        <select wire:model="newCashAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select account</option>
                            @foreach ($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('newCashAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('createModal', false)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="createLoan" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif

    @if ($repayLoanId)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('repayLoanId', null)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">কিস্তি দিলাম (Repayment)</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">আসল (Principal)</label>
                            <input type="number" step="0.01" wire:model="repayPrincipal" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            @error('repayPrincipal') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">সুদ (Interest)</label>
                            <input type="number" step="0.01" wire:model="repayInterest" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Paid from</label>
                        <select wire:model="repayCashAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select account</option>
                            @foreach ($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('repayCashAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model="repayDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('repayLoanId', null)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="repay" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
