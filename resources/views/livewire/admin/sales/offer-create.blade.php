<div x-data x-init="$store.pageName = { name: 'Create Offer', slug: 'sales-offers-create' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sales.offers') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-900">Create Offer</h1>
        </div>
        <button wire:click="save" type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
            </svg>
            Create Offer
        </button>
    </div>

    <div class="grid grid-cols-12 gap-6">

        <div class="col-span-12 lg:col-span-8 space-y-6">

            {{-- General --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">General</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" placeholder="e.g. Buy 2 Get 1 Shirt"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Offer Type <span class="text-red-500">*</span></label>
                        <select wire:model="offerType" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach(\App\Enums\Sales\OfferType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="status" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach(\App\Enums\Sales\PromotionStatus::cases() as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Description</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            @include('livewire.admin.sales.partials.promotion-conditions')
            @include('livewire.admin.sales.partials.promotion-discount-rules')

            {{-- Target Products --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Target Products (Optional)</h2>
                <p class="text-xs text-gray-400 mb-3">Leave empty to apply this offer based on conditions only (e.g. brand or category), rather than specific products.</p>

                <div class="relative mb-4">
                    <input wire:model.live.debounce.300ms="productSearch" type="text" placeholder="Search products to add…"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @if($productSearch !== '')
                        <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            @forelse($productOptions as $option)
                                <button type="button" wire:click="addItem({{ $option->id }})"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between">
                                    <span>{{ $option->name }}</span>
                                    <span class="text-xs text-gray-400 font-mono">{{ $option->code }}</span>
                                </button>
                            @empty
                                <p class="px-3 py-2 text-xs text-gray-400">No matching products found.</p>
                            @endforelse
                        </div>
                    @endif
                </div>

                <div class="space-y-2">
                    @forelse($items as $i => $item)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-2.5">
                            <span class="text-sm text-gray-700">{{ $item['label'] }}</span>
                            <button type="button" wire:click="removeItem({{ $i }})"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No products added yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right --}}
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Schedule</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Starts At</label>
                        <input wire:model="startsAt" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Ends At</label>
                        <input wire:model="endsAt" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Priority</label>
                        <input wire:model="priority" type="number" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input wire:model="stackable" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Stackable with other promotions</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
