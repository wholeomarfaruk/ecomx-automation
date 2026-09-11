<div x-data x-init="$store.pageName = { name: 'Cash & Bank', slug: 'accounts-cash-bank' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">হাতে আছে (Cash &amp; Bank)</h1>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            নতুন অ্যাকাউন্ট
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($accounts as $account)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 {{ ! $account->is_active ? 'opacity-50' : '' }}">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-400">{{ $account->code }} @if($account->is_system) · system @endif</p>
                        <p class="font-medium text-gray-800">{{ $account->name }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $account->type->badgeClass() }}">
                        {{ ucfirst($account->subtype ?? $account->type->value) }}
                    </span>
                </div>
                <p class="text-2xl font-semibold text-gray-800 mt-3">
                    {{ $account->currency?->symbol ?? '৳' }} {{ number_format($account->balance(), 2) }}
                </p>
                <div class="flex items-center justify-between mt-4">
                    <a href="{{ route('admin.accounts.cash-accounts.ledger', $account->id) }}" class="text-sm text-indigo-600 hover:text-indigo-700">Statement</a>
                    @if (! $account->is_system)
                        <button wire:click="toggleActive({{ $account->id }})" type="button" class="text-xs text-gray-400 hover:text-gray-600">
                            {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($createModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">নতুন অ্যাকাউন্ট যোগ (New Account)</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Type</label>
                        <select wire:model="newParentId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select type</option>
                            @foreach ($cashLikeParents as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        @error('newParentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Name</label>
                        <input type="text" wire:model="newName" placeholder="e.g. City Bank" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Opening balance</label>
                        <input type="number" step="0.01" wire:model="newOpeningBalance" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
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
