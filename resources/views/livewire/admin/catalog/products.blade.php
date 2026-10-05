<div x-data x-init="$store.pageName = { name: 'Products', slug: 'catalog-products' }">

    @php
        // Row actions: superadmin sees all, other roles only what they're granted.
        $authUser   = auth()->user();
        $isSuper    = $authUser?->hasRole('superadmin') ?? false;
        $canProduct = fn (string $perm) => $isSuper || (bool) $authUser?->can($perm);
    @endphp

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-5 gap-3 flex-1 max-w-3xl">
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
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3" title="Units in stock: simple products + variants (combos are built from these)">
                <p class="text-xs text-gray-400">Total Stock</p>
                <p class="text-xl font-semibold text-indigo-600 mt-0.5">{{ number_format($totalStock, floor($totalStock) == $totalStock ? 0 : 2) }}</p>
            </div>
        </div>
        @if($canProduct('product.create'))
            <button wire:click="openCreateModal" type="button"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Product
            </button>
        @endif
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
            <div class="flex items-center gap-1.5" title="Selling price range (sale price if set, otherwise regular price; variants included)">
                <input wire:model.live.debounce.500ms="minPrice" type="number" min="0" step="0.01" placeholder="Min price"
                    class="w-28 text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <span class="text-gray-400 text-sm">–</span>
                <input wire:model.live.debounce.500ms="maxPrice" type="number" min="0" step="0.01" placeholder="Max price"
                    class="w-28 text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @if($minPrice !== '' || $maxPrice !== '')
                    <button wire:click="clearPriceRange" type="button" title="Clear price range"
                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>
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
                                    $stockInfo = $product->stock_info;
                                    $stockClickable = $product->product_type !== \App\Enums\Product\ProductType::COMBO && $canProduct('product.edit');
                                @endphp
                                @if($stockClickable)
                                    <button type="button" wire:click="openStockModal({{ $product->id }})" title="View & manage stock"
                                        class="inline-flex flex-col items-center rounded-lg px-2 py-1 -mx-2 -my-1 hover:bg-indigo-50/60 transition cursor-pointer">
                                @endif
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $stockStyles[$product->stock_status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $stockLabels[$product->stock_status] ?? $product->stock_status }}
                                </span>
                                <div class="text-xs text-gray-400 mt-1">
                                    @if($stockInfo['quantity'] === null)
                                        <span class="text-gray-300">—</span>
                                    @elseif($product->product_type === \App\Enums\Product\ProductType::COMBO)
                                        {{ (int) $stockInfo['quantity'] }} bundle{{ (int) $stockInfo['quantity'] === 1 ? '' : 's' }}
                                    @elseif($product->product_type === \App\Enums\Product\ProductType::VARIABLE)
                                        @php
                                            $activeVariants = $product->variants->where('status', 'active');
                                            $stockedVariants = $activeVariants->filter(fn ($v) => (float) $v->stock_quantity > 0)->count();
                                        @endphp
                                        {{ rtrim(rtrim(number_format($stockInfo['quantity'], 3), '0'), '.') ?: '0' }}
                                        <span class="text-gray-300" title="Active variants with stock">({{ $stockedVariants }}/{{ $activeVariants->count() }} in stock)</span>
                                    @else
                                        {{ rtrim(rtrim(number_format($stockInfo['quantity'], 3), '0'), '.') ?: '0' }}
                                    @endif
                                </div>
                                @php
                                    $held = $heldStock[$product->id] ?? null;
                                    $free = $held ? max(0, (float) ($stockInfo['quantity'] ?? 0) - $held['quantity']) : 0;
                                @endphp
                                @if($held && $product->product_type !== \App\Enums\Product\ProductType::COMBO)
                                    <div class="text-[11px] text-amber-600 mt-0.5" title="Units in Pending orders — taken from stock only when those orders are confirmed">
                                        {{ $held['orders'] }} pending order{{ $held['orders'] === 1 ? '' : 's' }}
                                        <span class="text-gray-400">· free {{ rtrim(rtrim(number_format($free, 3), '0'), '.') ?: '0' }}</span>
                                    </div>
                                @endif
                                @if($stockClickable)
                                    </button>
                                @endif
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
                                <div x-data="{
                                        open: false,
                                        top: 0,
                                        right: 0,
                                        toggle() {
                                            const r = this.$refs.btn.getBoundingClientRect();
                                            this.top   = r.bottom + window.scrollY + 4;
                                            this.right = window.innerWidth - r.right;
                                            this.open  = !this.open;
                                        }
                                    }"
                                    class="flex justify-end">

                                    <button x-ref="btn" @click="toggle()" type="button" title="Actions"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/>
                                        </svg>
                                    </button>

                                    <template x-teleport="body">
                                        <div x-show="open"
                                             @click.outside="open = false"
                                             @keydown.escape.window="open = false"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             :style="`position: absolute; top: ${top}px; right: ${right}px; z-index: 9999;`"
                                             class="w-52 bg-white rounded-xl shadow-xl border border-gray-200 py-1 text-sm origin-top-right">

                                            @if($canProduct('product.view'))
                                                <button wire:click="openQuickView({{ $product->id }})" @click="open = false" type="button"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                    </svg>
                                                    Quick View
                                                </button>
                                            @endif

                                            @if($product->url)
                                                <button type="button" @click="open = false; copyProductLink(@js($product->url))"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                                                    </svg>
                                                    Copy Link
                                                </button>
                                                <a href="{{ $product->url }}" target="_blank" rel="noopener" @click="open = false"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                                    </svg>
                                                    Open in Store
                                                </a>
                                            @endif

                                            @if($canProduct('product.edit'))
                                                <a href="{{ route('admin.catalog.products.edit', $product->id) }}" wire:navigate
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                                    </svg>
                                                    Edit
                                                </a>
                                                @if($product->product_type !== \App\Enums\Product\ProductType::COMBO)
                                                    <button wire:click="openStockModal({{ $product->id }})" @click="open = false" type="button"
                                                        class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                                                        </svg>
                                                        Manage Stock
                                                    </button>
                                                @endif
                                                <button wire:click="toggleStatus({{ $product->id }})" @click="open = false" type="button"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/>
                                                    </svg>
                                                    {{ $product->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            @endif

                                            @if($canProduct('product.delete'))
                                                <div class="my-1 border-t border-gray-100"></div>
                                                <button type="button"
                                                    @click="open = false; Swal.fire({
                                                        title: 'Delete product?',
                                                        text: '{{ addslashes($product->name) }} will be removed permanently.',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#ef4444',
                                                        confirmButtonText: 'Delete'
                                                    }).then(r => { if (r.isConfirmed) $wire.deleteProduct({{ $product->id }}) })"
                                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-red-600 hover:bg-red-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                    </svg>
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </template>
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
                                    @if($canProduct('product.create'))
                                        <button wire:click="openCreateModal"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                            Add First Product
                                        </button>
                                    @endif
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

    {{-- Stock Modal (Stock column cell) --}}
    <div x-cloak x-data="{ open: @entangle('stockModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-4xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 truncate">{{ $stockProduct?->name ?? 'Stock' }}</h2>
                    @if($stockProduct)
                        <p class="text-xs text-gray-400 font-mono">{{ $stockProduct->code }}</p>
                    @endif
                </div>
                @if($stockProduct)
                    <a href="{{ route('admin.catalog.products.edit', $stockProduct->id) }}" wire:navigate
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition shrink-0">
                        Edit Product
                    </a>
                @endif
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 overflow-y-auto">
                @if($stockProduct)
                    @livewire('admin.catalog.product-stocks', ['productId' => $stockProduct->id, 'pendingType' => $stockProduct->product_type->value], key('stock-modal-' . $stockProduct->id))
                @endif
            </div>
        </div>
    </div>

    {{-- Quick View Modal — read-only details; purchase price is deliberately never shown --}}
    <div x-cloak x-data="{ open: @entangle('quickViewModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-4xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 truncate">{{ $quickProduct?->name ?? 'Product' }}</h2>
                    @if($quickProduct)
                        <p class="text-xs text-gray-400 font-mono">{{ $quickProduct->code }} · {{ $quickProduct->slug }}</p>
                    @endif
                </div>
                @if($quickProduct?->url)
                    <button type="button" @click="copyProductLink(@js($quickProduct->url))"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                        </svg>
                        Copy Link
                    </button>
                @endif
                @if($quickProduct && $canProduct('product.edit'))
                    <a href="{{ route('admin.catalog.products.edit', $quickProduct->id) }}" wire:navigate
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition shrink-0">
                        Edit Product
                    </a>
                @endif
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @if($quickProduct)
                @php
                    $qp         = $quickProduct;
                    $qpImages   = collect([$qp->featured_image_id])->merge($qp->image_ids ?? [])->filter()->unique()->values()
                                    ->map(fn ($id) => file_path($id))->filter()->values();
                    $qpStock    = $qp->stock_info;
                    $qpIsCombo  = $qp->product_type === \App\Enums\Product\ProductType::COMBO;
                    $qpIsVar    = $qp->product_type === \App\Enums\Product\ProductType::VARIABLE;
                    $qpFmtQty   = fn ($q) => rtrim(rtrim(number_format((float) $q, 3), '0'), '.') ?: '0';
                    $qpStatusStyles = [
                        'active'   => 'bg-emerald-50 text-emerald-600',
                        'inactive' => 'bg-gray-100 text-gray-500',
                        'draft'    => 'bg-amber-50 text-amber-600',
                        'archived' => 'bg-red-50 text-red-500',
                    ];
                    $qpStockStyles = [
                        'in_stock'     => 'bg-emerald-50 text-emerald-600',
                        'low_stock'    => 'bg-amber-50 text-amber-600',
                        'out_of_stock' => 'bg-red-50 text-red-500',
                        'backorder'    => 'bg-indigo-50 text-indigo-600',
                    ];
                @endphp
                <div class="px-6 py-5 overflow-y-auto space-y-6" wire:key="quick-view-{{ $qp->id }}">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">

                        {{-- Images --}}
                        <div class="md:col-span-2" x-data="{ active: @js($qpImages->first()) }">
                            <div class="aspect-square rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center">
                                @if($qpImages->isNotEmpty())
                                    <img :src="active" alt="" class="h-full w-full object-cover">
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                    </svg>
                                @endif
                            </div>
                            @if($qpImages->count() > 1)
                                <div class="grid grid-cols-5 gap-2 mt-2">
                                    @foreach($qpImages as $img)
                                        <button type="button" @click="active = @js($img)"
                                            class="aspect-square rounded-lg overflow-hidden border-2 transition"
                                            :class="active === @js($img) ? 'border-indigo-500' : 'border-transparent hover:border-gray-300'">
                                            <img src="{{ $img }}" alt="" class="h-full w-full object-cover">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="md:col-span-3 space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $qpStatusStyles[$qp->status] ?? 'bg-gray-100 text-gray-500' }}">{{ $qp->status }}</span>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600">{{ $qp->product_type->label() }}</span>
                                @if($qp->featured)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600">Featured</span>
                                @endif
                            </div>

                            {{-- Pricing (selling prices only) --}}
                            <div class="flex items-end gap-3">
                                @if($qp->sale_price)
                                    <span class="text-2xl font-semibold text-gray-900">{{ number_format($qp->sale_price, 2) }}</span>
                                    <span class="text-sm text-gray-400 line-through mb-1">{{ number_format($qp->price, 2) }}</span>
                                @elseif($qp->price)
                                    <span class="text-2xl font-semibold text-gray-900">{{ number_format($qp->price, 2) }}</span>
                                @else
                                    <span class="text-sm text-gray-400">{{ $qpIsVar ? 'Priced per variant' : 'No price set' }}</span>
                                @endif
                            </div>

                            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                                <div>
                                    <dt class="text-xs text-gray-400">Brand</dt>
                                    <dd class="text-gray-700">{{ $qp->brand?->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-400">Categories</dt>
                                    <dd class="text-gray-700">{{ $qp->categories->pluck('name')->join(', ') ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-400">Stock</dt>
                                    <dd class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $qpStockStyles[$qp->stock_status] ?? 'bg-gray-100 text-gray-500' }}">
                                            {{ \Illuminate\Support\Str::headline($qp->stock_status) }}
                                        </span>
                                        @if($qpStock['quantity'] !== null)
                                            <span class="text-gray-700">{{ $qpFmtQty($qpStock['quantity']) }}{{ $qpIsCombo ? ' bundles' : '' }}</span>
                                        @endif
                                    </dd>
                                </div>
                                @if($qp->combo_price)
                                    <div>
                                        <dt class="text-xs text-gray-400">Combo Price</dt>
                                        <dd class="text-gray-700">{{ number_format($qp->combo_price, 2) }}</dd>
                                    </div>
                                @endif
                                @if((float) $qp->weight > 0)
                                    <div>
                                        <dt class="text-xs text-gray-400">Weight</dt>
                                        <dd class="text-gray-700">{{ $qpFmtQty($qp->weight) }}</dd>
                                    </div>
                                @endif
                                @if((float) $qp->length > 0 || (float) $qp->width > 0 || (float) $qp->height > 0)
                                    <div>
                                        <dt class="text-xs text-gray-400">Dimensions (L × W × H)</dt>
                                        <dd class="text-gray-700">{{ $qpFmtQty($qp->length) }} × {{ $qpFmtQty($qp->width) }} × {{ $qpFmtQty($qp->height) }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-xs text-gray-400">Combo / Gift</dt>
                                    <dd class="text-gray-700">{{ $qp->combo_allowed ? 'Combo allowed' : 'No combo' }} · {{ $qp->gift_allowed ? 'Gift allowed' : 'No gift' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-400">Created</dt>
                                    <dd class="text-gray-700">{{ $qp->created_at?->format('d M Y') ?? '—' }}</dd>
                                </div>
                            </dl>

                            @if($qp->short_description)
                                <div>
                                    <p class="text-xs text-gray-400 mb-1">Short Description</p>
                                    <div class="text-sm text-gray-600 prose prose-sm max-w-none">{!! $qp->short_description !!}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Variants --}}
                    @if($qpIsVar && $qp->variants->isNotEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Variants ({{ $qp->variants->count() }})</h3>
                            <div class="overflow-x-auto rounded-xl border border-gray-100">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50/60">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Variant</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">SKU</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Price</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Sale Price</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Stock</th>
                                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($qp->variants as $variant)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-700">{{ collect($variant->options_map)->map(fn ($v, $k) => "{$k}: {$v}")->join(', ') ?: '—' }}</td>
                                                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $variant->sku ?: '—' }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ $variant->price ? number_format($variant->price, 2) : '—' }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ $variant->sale_price ? number_format($variant->sale_price, 2) : '—' }}</td>
                                                <td class="px-4 py-2 text-right {{ (float) $variant->stock_quantity > 0 ? 'text-gray-700' : 'text-red-500' }}">{{ $qpFmtQty($variant->stock_quantity) }}</td>
                                                <td class="px-4 py-2 text-center">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium capitalize {{ $variant->status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">{{ $variant->status }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Combo items --}}
                    @if($qpIsCombo && $qp->comboItems->isNotEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Combo Items ({{ $qp->comboItems->count() }})</h3>
                            <div class="rounded-xl border border-gray-100 divide-y divide-gray-100">
                                @foreach($qp->comboItems as $item)
                                    <div class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                                        <div class="min-w-0">
                                            <p class="text-gray-700 truncate">{{ $item->product?->name ?? 'Removed product' }}</p>
                                            <p class="text-xs text-gray-400">
                                                @if($item->variant)
                                                    {{ collect($item->variant->options_map)->map(fn ($v, $k) => "{$k}: {$v}")->join(', ') }}
                                                @elseif($item->allow_variant)
                                                    Customer picks variant
                                                @endif
                                            </p>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 shrink-0">× {{ $qpFmtQty($item->quantity) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Description --}}
                    @if($qp->description)
                        <div x-data="{ more: false }">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Description</h3>
                            <div class="relative text-sm text-gray-600 prose prose-sm max-w-none overflow-hidden" :class="more ? '' : 'max-h-40'">
                                {!! $qp->description !!}
                                <div x-show="!more" class="absolute inset-x-0 bottom-0 h-12 bg-linear-to-t from-white"></div>
                            </div>
                            <button type="button" @click="more = !more" class="mt-1 text-xs font-medium text-indigo-600 hover:text-indigo-700" x-text="more ? 'Show less' : 'Show more'"></button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @script
    <script>
        window.copyProductLink = async (url) => {
            try {
                await navigator.clipboard.writeText(url);
            } catch (e) {
                const ta = Object.assign(document.createElement('textarea'), { value: url });
                ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            }
            Toast.fire({ icon: 'success', title: 'Product link copied' });
        };
    </script>
    @endscript

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
