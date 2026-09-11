<div x-data x-init="$store.pageName = { name: 'Accounting Periods', slug: 'accounts-fiscal-periods' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">Fiscal Year &amp; Accounting Periods</h1>
        <button wire:click="openCreateYearModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            + New Fiscal Year
        </button>
    </div>

    @foreach ($fiscalYears as $year)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-4">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/40">
                <p class="font-medium text-gray-800">{{ $year->name }}</p>
                <p class="text-xs text-gray-400">{{ $year->start_date->format('d M Y') }} — {{ $year->end_date->format('d M Y') }}</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 p-5">
                @foreach ($year->periods as $period)
                    <div class="border border-gray-100 rounded-xl p-3 flex items-center justify-between">
                        <span class="text-sm text-gray-700">{{ $period->start_date->format('M Y') }}</span>
                        <button wire:click="toggleLock({{ $period->id }})" type="button"
                            class="px-2 py-0.5 rounded-full text-xs font-medium {{ $period->is_locked ? 'bg-red-50 text-red-500' : 'bg-emerald-50 text-emerald-600' }}">
                            {{ $period->is_locked ? 'Locked' : 'Open' }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if ($createYearModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createYearModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">New Fiscal Year</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Name</label>
                        <input type="text" wire:model="newYearName" placeholder="e.g. FY2026" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newYearName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Start Date</label>
                        <input type="date" wire:model="newYearStartDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('createYearModal', false)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="createYear" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Create</button>
                </div>
            </div>
        </div>
    @endif
</div>
