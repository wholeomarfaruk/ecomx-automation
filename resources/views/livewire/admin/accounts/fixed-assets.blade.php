<div x-data x-init="$store.pageName = { name: 'Fixed Assets', slug: 'accounts-fixed-assets' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">সম্পদ (Fixed Assets)</h1>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            + নতুন সম্পদ
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Asset</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Cost</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Accum. Depreciation</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Book Value</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($assets as $asset)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $asset->name }}</p>
                                <p class="text-xs text-gray-400">{{ $asset->categoryAccount->name }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-gray-600">{{ number_format($asset->cost, 2) }}</td>
                            <td class="px-5 py-3 text-right text-sm text-gray-600">{{ number_format($asset->accumulated_depreciation, 2) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($asset->bookValue(), 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $asset->status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($asset->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2">
                                @if ($asset->status === 'active')
                                    <button wire:click="openDepreciateModal({{ $asset->id }})" type="button" class="text-sm text-indigo-600 hover:text-indigo-700">Depreciate</button>
                                    <button wire:click="openDisposeModal({{ $asset->id }})" type="button" class="text-sm text-red-500 hover:text-red-700">Dispose</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-gray-400">No fixed assets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $assets->links() }}</div>
    </div>

    @if ($createModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">নতুন সম্পদ কেনা</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Name</label>
                        <input type="text" wire:model="newName" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Category</label>
                        <select wire:model="newCategoryAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Select category</option>
                            @foreach ($categoryAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('newCategoryAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Cost</label>
                            <input type="number" step="0.01" wire:model="newCost" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            @error('newCost') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Salvage value</label>
                            <input type="number" step="0.01" wire:model="newSalvageValue" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Purchase date</label>
                            <input type="date" wire:model="newPurchaseDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Useful life (months)</label>
                            <input type="number" wire:model="newUsefulLifeMonths" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            @error('newUsefulLifeMonths') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Paid from</label>
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
                    <button wire:click="createAsset" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif

    @if ($depreciateAssetId)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('depreciateAssetId', null)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">অবচয় (Run Depreciation)</h2>
                <label class="block text-sm text-gray-600 mb-1">Period (month)</label>
                <input type="date" wire:model="depreciatePeriod" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 mb-4">
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('depreciateAssetId', null)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="runDepreciation" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Run</button>
                </div>
            </div>
        </div>
    @endif

    @if ($disposeAssetId)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('disposeAssetId', null)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">বিক্রি / বাতিল (Dispose)</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Sale proceeds (0 if scrapped)</label>
                        <input type="number" step="0.01" wire:model="disposeProceeds" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Received into</label>
                        <select wire:model="disposeCashAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">—</option>
                            @foreach ($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model="disposeDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('disposeAssetId', null)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="dispose" type="button" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg">Dispose</button>
                </div>
            </div>
        </div>
    @endif
</div>
