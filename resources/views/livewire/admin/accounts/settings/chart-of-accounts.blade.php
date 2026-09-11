<div x-data x-init="$store.pageName = { name: 'Chart of Accounts', slug: 'accounts-chart-of-accounts' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">Chart of Accounts</h1>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            + Add Account
        </button>
    </div>

    <div class="space-y-6">
        @foreach ($accountsByType as $type => $accounts)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/40">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ ucfirst($type) }}</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($accounts as $account)
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-800">{{ $account->code }} — {{ $account->name }}</span>
                                <div class="flex items-center gap-2">
                                    @if ($account->is_system)
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">System</span>
                                    @endif
                                    <span class="text-sm text-gray-600">{{ number_format($account->balance(), 2) }}</span>
                                </div>
                            </div>
                            @foreach ($account->children as $child)
                                <div class="flex items-center justify-between pl-6 mt-1">
                                    <span class="text-sm text-gray-500">{{ $child->code }} — {{ $child->name }}</span>
                                    <span class="text-sm text-gray-500">{{ number_format($child->balance(), 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if ($createModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Add Account</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Name</label>
                        <input type="text" wire:model="newName" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Parent (defines type)</label>
                        <select wire:model="newParentId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select parent account</option>
                            @foreach ($allAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('newParentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Description</label>
                        <input type="text" wire:model="newDescription" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('createModal', false)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="createAccount" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
