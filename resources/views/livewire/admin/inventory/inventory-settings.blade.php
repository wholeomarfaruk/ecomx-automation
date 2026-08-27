<div x-data x-init="$store.pageName = { name: 'Inventory Settings', slug: 'inventory-settings' }">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.inventory.stock') }}"
            class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <h1 class="text-lg font-semibold text-gray-900">Inventory Settings</h1>
    </div>

    <form wire:submit.prevent="save" class="space-y-6 max-w-3xl">

        {{-- Stock Levels --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Stock Levels</h2>
            <div class="space-y-4">
                <div class="max-w-xs">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Low Stock Threshold</label>
                    <input wire:model="lowStockThreshold" type="number" min="0" max="1000"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1.5">A variant is flagged "Low Stock" when its quantity is at or below this number (and above zero).</p>
                    @error('lowStockThreshold') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center justify-between gap-4 cursor-pointer pt-2 border-t border-gray-100">
                    <span>
                        <span class="block text-sm font-medium text-gray-700">Allow Negative Stock</span>
                        <span class="block text-xs text-gray-400">Let sales go through even if it would take stock below zero (backorders). Off by default — oversell attempts are blocked.</span>
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="allowNegativeStock" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Order Integration --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Order Integration</h2>
            <div class="space-y-4">
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span>
                        <span class="block text-sm font-medium text-gray-700">Deduct Stock on Order Confirm</span>
                        <span class="block text-xs text-gray-400">Automatically reduce stock when an order (admin-created or updated) becomes Confirmed. POS sales always deduct immediately regardless of this setting.</span>
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="deductOnOrderConfirm" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
                <label class="flex items-center justify-between gap-4 cursor-pointer pt-2 border-t border-gray-100">
                    <span>
                        <span class="block text-sm font-medium text-gray-700">Restock on Cancel / Return</span>
                        <span class="block text-xs text-gray-400">Automatically add stock back when a previously confirmed order is cancelled or returned.</span>
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="restockOnCancelOrReturn" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Warehouse --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Default Warehouse</h2>
            <div class="max-w-xs">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Default Warehouse</label>
                <select wire:model="defaultWarehouseId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">— None —</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}{{ $warehouse->is_default ? ' (current default)' : '' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1.5">Used for orders and admin stock adjustments unless a specific warehouse applies (e.g. a POS register linked to a branch).</p>
                @error('defaultWarehouseId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                Save Settings
            </button>
        </div>
    </form>

</div>
