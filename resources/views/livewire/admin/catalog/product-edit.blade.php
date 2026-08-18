<div x-data x-init="$store.pageName = { name: 'Edit Product', slug: 'catalog-products-edit' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.catalog.products') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ $name ?: 'Edit Product' }}</h1>
                <p class="text-xs text-gray-400 font-mono">{{ $code }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @php
                $statusStyles = [
                    'active'   => 'bg-emerald-50 text-emerald-600',
                    'inactive' => 'bg-gray-100 text-gray-500',
                    'draft'    => 'bg-amber-50 text-amber-600',
                    'archived' => 'bg-red-50 text-red-500',
                ];
            @endphp
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium capitalize {{ $statusStyles[$status] ?? 'bg-gray-100 text-gray-500' }}">
                {{ $status }}
            </span>
            <button wire:click="save" type="button"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                </svg>
                Save Changes
            </button>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- Tabs (sidebar on desktop, top on mobile) --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-2 flex lg:flex-col gap-1 overflow-x-auto">
                @php
                    $tabs = [
                        'general'  => ['label' => 'General', 'icon' => 'M11.25 4.5l7.5 7.5-7.5 7.5m-6-15 7.5 7.5-7.5 7.5'],
                        'pricing'  => ['label' => 'Pricing', 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                        'media'    => ['label' => 'Media', 'icon' => 'm2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M3 16.5V6.75A2.25 2.25 0 0 1 5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v9.75'],
                        'variants' => ['label' => 'Variants', 'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z'],
                        'combo'    => ['label' => 'Combo', 'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25M21 7.5v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
                        'gift'     => ['label' => 'Gift', 'icon' => 'M12 8.25v13.5m0-13.5V6a2.25 2.25 0 0 0-2.25-2.25H9.375a1.125 1.125 0 0 0-1.125 1.125v.75A2.625 2.625 0 0 0 10.875 8.25H12Zm0 0h1.125A2.625 2.625 0 0 0 15.75 5.625v-.75A1.125 1.125 0 0 0 14.625 3.75H14.25A2.25 2.25 0 0 0 12 6v2.25ZM3.375 9.75h17.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H3.375A1.125 1.125 0 0 1 2.25 13.125v-2.25c0-.621.504-1.125 1.125-1.125Zm.375 4.5h16.5v6a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-6Z'],
                        'shipping' => ['label' => 'Shipping', 'icon' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.909c.526 0 .982.36 1.108.873l1.055 4.226M15 18.75V5.625c0-.621-.504-1.125-1.125-1.125h-9.75c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125H4.5'],
                        'seo'      => ['label' => 'SEO', 'icon' => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z'],
                    ];
                @endphp
                @foreach($tabs as $key => $tab)
                    @continue($key === 'combo' && $productType !== 'combo')
                    @continue($key === 'gift' && ! $giftAllowed)
                    <button type="button" wire:click="setTab('{{ $key }}')"
                        class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium transition whitespace-nowrap
                            {{ $activeTab === $key ? 'bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/>
                        </svg>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Panels --}}
        <div class="col-span-12 lg:col-span-9">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">

                {{-- General --}}
                <div @if($activeTab !== 'general') style="display:none" @endif>
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">General Information</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Product Name <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Slug</label>
                            <input wire:model="slug" type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('slug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">SKU / Code</label>
                            <input wire:model="code" type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Brand</label>
                            <select wire:model="brandId"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— None —</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select wire:model="status"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Stock Status</label>
                            <select wire:model="stockStatus"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="in_stock">In Stock</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                                <option value="backorder">Backorder</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Product Type</label>
                            <select wire:model.live="productType"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="simple">Simple</option>
                                <option value="variable">Variable</option>
                                <option value="combo">Combo</option>
                            </select>
                            @error('productType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        @if($productType !== 'combo')
                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer mt-6">
                                    <input wire:model="comboAllowed" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Allow as combo component</span>
                                </label>
                                <p class="text-xs text-gray-400 mt-1">When enabled, this product can be added as a component inside other combo products.</p>
                            </div>
                        @endif
                        <div>
                            <label class="inline-flex items-center gap-2 cursor-pointer mt-6">
                                <input wire:model.live="giftAllowed" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Offer free gift with this product</span>
                            </label>
                            <p class="text-xs text-gray-400 mt-1">When enabled, configure the gift items in the Gift tab.</p>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Categories</label>
                            <div class="flex flex-wrap gap-3 rounded-lg border border-gray-200 px-3 py-3 max-h-40 overflow-y-auto">
                                @forelse($categories as $category)
                                    <label class="inline-flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer">
                                        <input type="checkbox" wire:model="categoryIds" value="{{ $category->id }}"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ $category->name }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">No categories yet.</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input wire:model="featured" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Featured product</span>
                            </label>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Short Description</label>
                            <textarea wire:model="shortDescription" rows="2" placeholder="One or two line summary…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Full Description</label>
                            <textarea wire:model="description" rows="6" placeholder="Detailed product description…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div @if($activeTab !== 'pricing') style="display:none" @endif>
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Pricing</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Regular Price</label>
                            <input wire:model="price" type="number" step="0.01" min="0" placeholder="0.00"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('price') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Sale Price</label>
                            <input wire:model="salePrice" type="number" step="0.01" min="0" placeholder="0.00"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('salePrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        @if($productType !== 'combo')
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Purchase Price</label>
                                <input wire:model="purchasePrice" type="number" step="0.01" min="0" placeholder="0.00"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                @error('purchasePrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        @if($productType !== 'combo')
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Combo Price</label>
                                <input wire:model="comboPrice" type="number" step="0.01" min="0" placeholder="0.00"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <p class="text-xs text-gray-400 mt-1">Used when this product is added as a component inside another combo.</p>
                                @error('comboPrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Media --}}
                <div @if($activeTab !== 'media') style="display:none" @endif>
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Media</h2>
                    <div class="space-y-6">
                        <x-media-picker-field field="featuredImageId" :value="$featuredImageId" label="Featured Image" type="image" placeholder="Select featured image" />
                        <x-media-picker-field field="imageIds" :value="$imageIds" label="Gallery Images" type="image" :multiple="true" placeholder="Select gallery images" />
                        <x-media-picker-field field="videoIds" :value="$videoIds" label="Videos" type="video" :multiple="true" placeholder="Select videos" />
                    </div>
                </div>

                {{-- Variants --}}
                <div @if($activeTab !== 'variants') style="display:none" @endif>
                    @if($activeTab === 'variants')
                        @livewire('admin.catalog.product-variants', ['productId' => $productId], key('variants-' . $productId))
                    @endif
                </div>

                {{-- Combo --}}
                <div @if($activeTab !== 'combo') style="display:none" @endif>
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Combo Components</h2>

                    @if($productType !== 'combo')
                        <p class="text-sm text-gray-400">Set Product Type to "Combo" in the General tab to configure components.</p>
                    @else
                        <div class="relative mb-4">
                            <input wire:model.live.debounce.300ms="comboProductSearch" type="text"
                                placeholder="Search products to add…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @if($comboProductSearch !== '')
                                <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    @forelse($comboProductOptions as $option)
                                        <button type="button" wire:click="addComboItem({{ $option->id }})"
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

                        @error('comboItems') <p class="text-xs text-red-500 mb-3">{{ $message }}</p> @enderror

                        <div class="space-y-3">
                            @forelse($comboItems as $i => $item)
                                @php($variants = $comboVariantOptions->get((int) $item['product_id']))
                                <div class="rounded-lg border border-gray-200 px-3 py-2.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 text-sm text-gray-700">{{ $item['product_label'] }}</div>

                                        <input wire:model="comboItems.{{ $i }}.quantity" type="number" step="0.001" min="0.001"
                                            class="w-24 rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">

                                        <button type="button" wire:click="removeComboItem({{ $i }})"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    @if($variants && $variants->isNotEmpty())
                                        <div class="flex items-center gap-3 mt-2.5 pt-2.5 border-t border-gray-100">
                                            <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer shrink-0">
                                                <input wire:model.live="comboItems.{{ $i }}.allow_variant" type="checkbox"
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                Let customer choose variant
                                            </label>

                                            @if(! $item['allow_variant'])
                                                <select wire:model="comboItems.{{ $i }}.variant_id"
                                                    class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                    <option value="">— Select fixed variant —</option>
                                                    @foreach($variants as $variant)
                                                        <option value="{{ $variant->id }}">{{ $variant->sku }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="text-xs text-gray-400">Variant will be chosen by the customer at purchase time.</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @error('comboItems.' . $i . '.quantity') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            @empty
                                <p class="text-sm text-gray-400">No components added yet. Search above to add one.</p>
                            @endforelse
                        </div>
                    @endif
                </div>

                {{-- Gift --}}
                <div @if($activeTab !== 'gift') style="display:none" @endif>
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Gift Items</h2>

                    @if(! $giftAllowed)
                        <p class="text-sm text-gray-400">Enable "Offer free gift with this product" in the General tab to configure gift items.</p>
                    @else
                        <div class="relative mb-4">
                            <input wire:model.live.debounce.300ms="giftProductSearch" type="text"
                                placeholder="Search products to add as gift…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @if($giftProductSearch !== '')
                                <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    @forelse($giftProductOptions as $option)
                                        <button type="button" wire:click="addGiftItem({{ $option->id }})"
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

                        @error('giftItems') <p class="text-xs text-red-500 mb-3">{{ $message }}</p> @enderror

                        <div class="space-y-3">
                            @forelse($giftItems as $i => $item)
                                <div class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5">
                                    <div class="flex-1 text-sm text-gray-700">{{ $item['product_label'] }}</div>

                                    <input wire:model="giftItems.{{ $i }}.quantity" type="number" step="0.001" min="0.001"
                                        class="w-24 rounded-lg border border-gray-300 px-2 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">

                                    <button type="button" wire:click="removeGiftItem({{ $i }})"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('giftItems.' . $i . '.quantity') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            @empty
                                <p class="text-sm text-gray-400">No gift items added yet. Search above to add one.</p>
                            @endforelse
                        </div>
                    @endif
                </div>

                {{-- Shipping --}}
                <div @if($activeTab !== 'shipping') style="display:none" @endif>
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Shipping & Dimensions</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Weight (kg)</label>
                            <input wire:model="weight" type="number" step="0.001" min="0" placeholder="0.000"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('weight') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div></div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Length (cm)</label>
                            <input wire:model="length" type="number" step="0.001" min="0" placeholder="0.000"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('length') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Width (cm)</label>
                            <input wire:model="width" type="number" step="0.001" min="0" placeholder="0.000"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('width') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Height (cm)</label>
                            <input wire:model="height" type="number" step="0.001" min="0" placeholder="0.000"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('height') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- SEO --}}
                <div @if($activeTab !== 'seo') style="display:none" @endif>
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">SEO Settings</h2>
                    <div class="space-y-4">
                        <x-media-picker-field field="metaImageId" :value="$metaImageId" label="Meta Image" type="image" placeholder="Select image" />
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Title</label>
                            <input wire:model="metaTitle" type="text" placeholder="SEO title"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('metaTitle') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Description</label>
                            <textarea wire:model="metaDescription" rows="3" placeholder="SEO description"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Keywords</label>
                            <textarea wire:model="metaKeywords" rows="2" placeholder="Comma separated keywords"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-6 mt-6 border-t border-gray-100">
                    <a href="{{ route('admin.catalog.products') }}" wire:navigate
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button wire:click="save" type="button"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
