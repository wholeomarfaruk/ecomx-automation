<div x-data x-init="$store.pageName = { name: 'Expenses', slug: 'accounts-expenses' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">খরচ (Expenses)</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.accounts.expenses.recurring') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                নিয়মিত খরচ
            </a>
            <button wire:click="openCreateModal" type="button"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                + নতুন খরচ
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search…"
                class="w-64 px-3 py-2 text-sm rounded-lg border border-gray-300">
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Note</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-sm text-gray-800">
                                {{ $entry->lines->firstWhere('debit', '>', 0)?->account?->name }}
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-400">{{ $entry->description }}</td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($entry->totalDebit(), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $entries->links() }}</div>
    </div>

    @if ($createModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">নতুন খরচ (New Expense)</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Category</label>
                        <select wire:model="newExpenseAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select category</option>
                            @foreach ($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('newExpenseAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Amount</label>
                        <input type="number" step="0.01" wire:model="newAmount" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Paid from</label>
                        <select wire:model="newPaidFromAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select account</option>
                            @foreach ($paidFromAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('newPaidFromAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model="newDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Note</label>
                        <input type="text" wire:model="newDescription" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('createModal', false)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="save" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
