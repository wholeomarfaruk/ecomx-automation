<div x-data x-init="$store.pageName = { name: 'Products', slug: 'catalog-products' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-4 gap-3 flex-1 max-w-2xl">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Products</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Active</p>
                <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ $activeCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Draft</p>
                <p class="text-xl font-semibold text-amber-500 mt-0.5">{{ $draftCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Archived</p>
                <p class="text-xl font-semibold text-gray-400 mt-0.5">{{ $archivedCount }}</p>
            </div>
        </div>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Product
        </button>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, slug, or code…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="archived">Archived</option>
            </select>
            <select wire:model.live="filterBrand"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterStockStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Stock Statuses</option>
                <option value="in_stock">In Stock</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
                <option value="backorder">Backorder</option>
            </select>
            <select wire:model.live="filterProductType"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Product Types</option>
                <option value="simple">Simple</option>
                <option value="variable">Variable</option>
                <option value="combo">Combo</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Code</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Brand</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Price</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Stock</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50/50 transition group">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if($product->featured_image_id)
                                            <img src="{{ file_path($product->featured_image_id) }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-medium text-gray-800 truncate">{{ $product->name }}</span>
                                            @if($product->featured)
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.448a1 1 0 00-.364 1.118l1.287 3.959c.299.921-.755 1.688-1.538 1.118l-3.367-2.448a1 1 0 00-1.176 0l-3.367 2.448c-.783.57-1.837-.197-1.538-1.118l1.287-3.96a1 1 0 00-.364-1.117L2.062 9.386c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.689l1.286-3.958z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-mono text-gray-400 truncate">{{ $product->slug }}</span>
                                            @if($product->product_type->value !== 'simple')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide bg-indigo-50 text-indigo-500">{{ $product->product_type->label() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600">{{ $product->code }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $product->brand?->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($product->sale_price)
                                    <div class="text-sm font-medium text-gray-800">{{ number_format($product->sale_price, 2) }}</div>
                                    <div class="text-xs text-gray-400 line-through">{{ number_format($product->price, 2) }}</div>
                                @elseif($product->price)
                                    <div class="text-sm font-medium text-gray-800">{{ number_format($product->price, 2) }}</div>
                                @else
                                    <span class="text-sm text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $stockStyles = [
                                        'in_stock'     => 'bg-emerald-50 text-emerald-600',
                                        'low_stock'    => 'bg-amber-50 text-amber-600',
                                        'out_of_stock' => 'bg-red-50 text-red-500',
                                        'backorder'    => 'bg-indigo-50 text-indigo-600',
                                    ];
                                    $stockLabels = [
                                        'in_stock'     => 'In Stock',
                                        'low_stock'    => 'Low Stock',
                                        'out_of_stock' => 'Out of Stock',
                                        'backorder'    => 'Backorder',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $stockStyles[$product->stock_status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $stockLabels[$product->stock_status] ?? $product->stock_status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $statusStyles = [
                                        'active'   => 'bg-emerald-50 text-emerald-600',
                                        'inactive' => 'bg-gray-100 text-gray-500',
                                        'draft'    => 'bg-amber-50 text-amber-600',
                                        'archived' => 'bg-red-50 text-red-500',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$product->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $product->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.catalog.products.edit', $product->id) }}" wire:navigate
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                    </a>
                                    <button type="button" x-data
                                        @click="Swal.fire({
                                            title: 'Delete product?',
                                            text: '{{ addslashes($product->name) }} will be removed permanently.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#ef4444',
                                            confirmButtonText: 'Delete'
                                        }).then(r => { if (r.isConfirmed) $wire.deleteProduct({{ $product->id }}) })"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No products found</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Try adjusting filters or add a new product</p>
                                    </div>
                                    <button wire:click="openCreateModal"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                        Add First Product
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Quick-add Modal --}}
    <div x-cloak x-data="{ open: @entangle('createModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Add Product</h2>
                    <p class="text-xs text-gray-400">Start with the basics — you can add full details next</p>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="createProduct" class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Product Name <span class="text-red-500">*</span></label>
                    <input wire:model.live="newName" type="text" placeholder="e.g. Wireless Headphones" autofocus
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">
                        Slug <span class="text-gray-400 font-normal ml-1">(auto-generated, editable)</span>
                    </label>
                    <input wire:model="newSlug" type="text" placeholder="wireless-headphones"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('newSlug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">SKU / Code <span class="text-red-500">*</span></label>
                        <input wire:model="newCode" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Price</label>
                        <input wire:model="newPrice" type="number" step="0.01" min="0" placeholder="0.00"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newPrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Brand</label>
                    <select wire:model="newBrandId"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— None —</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Create & Continue
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
