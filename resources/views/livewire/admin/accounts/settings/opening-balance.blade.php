<div x-data x-init="$store.pageName = { name: 'Opening Balance', slug: 'accounts-opening-balance' }">

    <h1 class="text-lg font-semibold text-gray-800 mb-2">⚖️ শুরুর ব্যালেন্স (Opening Balance)</h1>
    <p class="text-sm text-gray-500 mb-6">Enter each account's starting balance. Assets are positive, liabilities/loans are negative. The rest is automatically balanced against Opening Balance Equity.</p>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 max-w-2xl">
        <div class="mb-4">
            <label class="block text-sm text-gray-600 mb-1">Date</label>
            <input type="date" wire:model="entryDate" class="w-full sm:w-64 px-3 py-2 text-sm rounded-lg border border-gray-300">
        </div>

        <div class="space-y-2 max-h-96 overflow-y-auto">
            @foreach ($accounts as $account)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 flex-1">{{ $account->code }} — {{ $account->name }}</span>
                    <input type="number" step="0.01" wire:model="amounts.{{ $account->id }}" placeholder="0.00"
                        class="w-40 px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-right">
                </div>
            @endforeach
        </div>

        <div class="flex justify-end mt-6">
            <button wire:click="save" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save Opening Balance</button>
        </div>
    </div>
</div>
