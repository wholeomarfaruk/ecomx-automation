<div x-data x-init="$store.pageName = { name: 'Recurring Expenses', slug: 'accounts-recurring-expenses' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">নিয়মিত খরচ (Recurring)</h1>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            + নতুন
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cadence</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Next Run</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Active</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recurrences as $r)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $r->name }}</p>
                                <p class="text-xs text-gray-400">{{ $r->account->name }} ← {{ $r->fromAccount->name }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ ucfirst($r->cadence) }}</td>
                            <td class="px-5 py-3 text-right text-sm text-gray-800">{{ number_format($r->amount, 2) }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $r->next_run_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-center">
                                <button wire:click="toggleActive({{ $r->id }})" type="button"
                                    class="px-2 py-0.5 rounded-full text-xs font-medium {{ $r->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $r->is_active ? 'Active' : 'Paused' }}
                                </button>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($r->is_active)
                                    <button wire:click="runNow({{ $r->id }})" type="button" class="text-sm text-indigo-600 hover:text-indigo-700">Run Now</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-gray-400">No recurring expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $recurrences->links() }}</div>
    </div>

    @if ($createModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">নিয়মিত খরচ (Recurring Expense)</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Name</label>
                        <input type="text" wire:model="newName" placeholder="e.g. Office Rent" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Category</label>
                        <select wire:model="newAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select category</option>
                            @foreach ($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('newAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Amount</label>
                            <input type="number" step="0.01" wire:model="newAmount" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            @error('newAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Cadence</label>
                            <select wire:model="newCadence" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Paid from</label>
                        <select wire:model="newFromAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select account</option>
                            @foreach ($paidFromAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('newFromAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Next run date</label>
                        <input type="date" wire:model="newNextRunDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('createModal', false)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="createRecurring" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
