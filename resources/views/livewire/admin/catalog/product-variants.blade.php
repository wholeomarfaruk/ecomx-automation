<div x-data>

    {{-- Attributes for this product --}}
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-gray-800 mb-1">Attributes</h2>
        <p class="text-xs text-gray-400 mb-4">Pick which attributes apply to this product, then choose the values you sell it in.</p>

        <div class="flex items-center gap-2 mb-4">
            <select wire:model="newAttributeId" class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">— Select an attribute to add —</option>
                @foreach($availableAttributes as $attribute)
                    <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                @endforeach
            </select>
            <button wire:click="addAttribute" type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add
            </button>
        </div>

        @if($orderedAttributes->isEmpty())
            <div class="border border-dashed border-gray-200 rounded-xl px-4 py-8 text-center">
                <p class="text-sm text-gray-400">No attributes added yet. Add Color, Size, etc. to start building variants.</p>
            </div>
        @else
            <ul id="product-attribute-list" class="space-y-3">
                @foreach($orderedAttributes as $productAttribute)
                    <li data-id="{{ $productAttribute->id }}" class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="flex items-center gap-3 px-4 py-3 bg-gray-50/60">
                            <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/>
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-gray-800 flex-1">{{ $productAttribute->attribute->name }}</span>
                            <button wire:click="removeAttribute({{ $productAttribute->id }})" type="button"
                                class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        @php
                            $selectedValues = $productAttribute->values->sortBy('sort_order')->values();
                            $unselectedValues = $productAttribute->attribute->values
                                ->whereNotIn('id', $selectedValues->pluck('attribute_value_id'));
                        @endphp
                        <div class="px-4 py-3">
                            @if($selectedValues->isNotEmpty())
                                <p class="text-[11px] text-gray-400 mb-2">Drag to set the order these show in on the product page</p>
                                <ul data-product-attribute-id="{{ $productAttribute->id }}" class="product-value-sortable flex flex-wrap gap-2 mb-2">
                                    @foreach($selectedValues as $selected)
                                        @php $attrValue = $selected->attributeValue; @endphp
                                        <li data-id="{{ $attrValue->id }}" class="inline-flex items-center rounded-lg border bg-indigo-600 border-indigo-600 text-white overflow-hidden">
                                            <span class="drag-handle cursor-grab active:cursor-grabbing pl-2 text-white/60 hover:text-white/90">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/>
                                                </svg>
                                            </span>
                                            <button wire:click="toggleValue({{ $productAttribute->id }}, {{ $attrValue->id }})" type="button"
                                                class="inline-flex items-center gap-1.5 px-2 py-1.5 text-xs font-medium">
                                                @if($selected->swatch_image_url)
                                                    <img src="{{ $selected->swatch_image_url }}" alt="" class="h-3.5 w-3.5 rounded-full object-cover border border-white/50 shrink-0">
                                                @elseif($attrValue->swatch_type === 'color')
                                                    <span class="h-3 w-3 rounded-full border border-white/50 shrink-0" style="background-color: {{ $attrValue->swatch_value }}"></span>
                                                @endif
                                                {{ $attrValue->value }}
                                            </button>
                                            {{-- Per-product swatch image override — shows this product's own photo for
                                                 this colour instead of the shared/global attribute-value swatch. --}}
                                            <button wire:click="openSwatchImagePicker({{ $selected->id }})" type="button"
                                                title="{{ $selected->swatch_image_url ? 'Change swatch image' : 'Set a product-specific swatch image' }}"
                                                class="px-2 py-1.5 border-l {{ $selected->swatch_image_url ? 'border-white/30' : 'border-gray-200' }} hover:bg-black/10 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                </svg>
                                            </button>
                                            @if($selected->swatch_image_url)
                                                <button wire:click="removeSwatchImage({{ $selected->id }})" type="button"
                                                    title="Remove swatch image (use the shared swatch again)"
                                                    class="px-2 py-1.5 border-l border-white/30 hover:bg-black/10 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if($unselectedValues->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach($unselectedValues as $attrValue)
                                        <button wire:click="toggleValue({{ $productAttribute->id }}, {{ $attrValue->id }})" type="button"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border bg-white border-gray-200 text-gray-600 hover:border-indigo-300 transition">
                                            @if($attrValue->swatch_type === 'color')
                                                <span class="h-3 w-3 rounded-full border border-white/50 shrink-0" style="background-color: {{ $attrValue->swatch_value }}"></span>
                                            @endif
                                            {{ $attrValue->value }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            @if($selectedValues->isEmpty() && $unselectedValues->isEmpty())
                                <p class="text-xs text-gray-400">This attribute has no values yet — add some from Catalog → Attributes.</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <button wire:click="generateVariants" type="button"
                class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                </svg>
                Generate Variants
            </button>
        @endif
    </div>

    {{-- Variants list --}}
    <div>
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-sm font-semibold text-gray-800">Variants</h2>
            <span class="text-xs text-gray-400">{{ $variants->count() }} {{ Str::plural('variant', $variants->count()) }}</span>
        </div>
        <p class="text-xs text-gray-400 mb-4">Drag to reorder. Click a variant to edit SKU, pricing, images, and color links.</p>

        @if($variants->isEmpty())
            <div class="border border-dashed border-gray-200 rounded-xl px-4 py-10 text-center">
                <p class="text-sm text-gray-400">No variants yet. Select attribute values above and click Generate Variants.</p>
            </div>
        @else
            <ul id="product-variant-list" class="border border-gray-200 rounded-xl divide-y divide-gray-100 overflow-hidden">
                @foreach($variants as $variant)
                    <li data-id="{{ $variant->id }}" class="flex items-center gap-3 px-4 py-3 bg-white hover:bg-gray-50/60 transition">
                        <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/>
                            </svg>
                        </span>

                        <div class="h-9 w-9 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                            @php $primaryMedia = $variant->media->firstWhere('is_primary', true) ?? $variant->media->first(); @endphp
                            @if($primaryMedia)
                                <img src="{{ file_path($primaryMedia->media_id) }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                @foreach($variant->values as $val)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                                        @if($val->productAttributeValue->swatch_image_url)
                                            <img src="{{ $val->productAttributeValue->swatch_image_url }}" alt="" class="h-2.5 w-2.5 rounded-full object-cover border border-gray-200">
                                        @elseif($val->productAttributeValue->attributeValue->swatch_type === 'color')
                                            <span class="h-2.5 w-2.5 rounded-full border border-gray-200" style="background-color: {{ $val->productAttributeValue->attributeValue->swatch_value }}"></span>
                                        @endif
                                        {{ $val->productAttributeValue->attributeValue->value }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs font-mono text-gray-400 mt-0.5">{{ $variant->sku }}</p>
                        </div>

                        <div class="text-right shrink-0">
                            @if($variant->sale_price)
                                <div class="text-sm font-medium text-gray-800">{{ number_format($variant->sale_price, 2) }}</div>
                                <div class="text-xs text-gray-400 line-through">{{ number_format($variant->price, 2) }}</div>
                            @elseif($variant->price)
                                <div class="text-sm font-medium text-gray-800">{{ number_format($variant->price, 2) }}</div>
                            @else
                                <span class="text-xs text-gray-300">No price</span>
                            @endif
                        </div>

                        <div class="text-right shrink-0 w-16">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $variant->stock_quantity > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                                {{ rtrim(rtrim(number_format($variant->stock_quantity, 3), '0'), '.') ?: '0' }} qty
                            </span>
                        </div>

                        <button wire:click="toggleVariantStatus({{ $variant->id }})" type="button"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 shrink-0
                                {{ $variant->status === 'active' ? 'bg-indigo-500 focus:ring-indigo-400' : 'bg-gray-300 focus:ring-gray-400' }}">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                {{ $variant->status === 'active' ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                        </button>

                        <div class="flex items-center gap-1 shrink-0">
                            <button wire:click="editVariant({{ $variant->id }})" type="button"
                                class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                </svg>
                            </button>
                            <button type="button" x-data
                                @click="Swal.fire({
                                    title: 'Delete variant?',
                                    text: '{{ $variant->sku }} will be removed permanently.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    confirmButtonText: 'Delete'
                                }).then(r => { if (r.isConfirmed) $wire.deleteVariant({{ $variant->id }}) })"
                                class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Variant Edit Modal --}}
    <div x-cloak x-data="{ open: @entangle('variantModal') }" x-show="open" x-transition
        @click.self="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-gray-900">Edit Variant</h2>
                    @if($editingVariant)
                        <p class="text-xs text-gray-400 font-mono truncate">{{ $editingVariant->sku }}</p>
                    @endif
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-5 space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">SKU <span class="text-red-500">*</span></label>
                        <input wire:model="variantSku" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('variantSku') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Price</label>
                        <input wire:model="variantPrice" type="number" step="0.01" min="0" placeholder="Use product price"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('variantPrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Sale Price</label>
                        <input wire:model="variantSalePrice" type="number" step="0.01" min="0" placeholder="0.00"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('variantSalePrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Purchase Price</label>
                        <input wire:model="variantPurchasePrice" type="number" step="0.01" min="0" placeholder="0.00"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('variantPurchasePrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Stock Quantity</label>
                        <input wire:model="variantStockQuantity" type="number" step="1" min="0" placeholder="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('variantStockQuantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="variantStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Reorder Level</label>
                        <input wire:model="variantReorderLevel" type="number" step="1" min="0" placeholder="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Flags as low stock at or below this — 0 uses the store default.</p>
                        @error('variantReorderLevel') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Reorder Quantity</label>
                        <input wire:model="variantReorderQuantity" type="number" step="1" min="0" placeholder="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Suggested quantity to reorder — shown on the Low Stock alert list.</p>
                        @error('variantReorderQuantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <x-media-picker-field field="variantImageIds" :value="$variantImageIds" label="Variant Images" type="image" :multiple="true" placeholder="Select variant images" />

                {{-- Color / product links --}}
                @if($editingVariant)
                    <div class="border border-gray-200 rounded-xl p-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Linked Products</h3>
                        <p class="text-xs text-gray-400 mb-3">Link this variant to the same product in a different color, so customers can switch between them.</p>

                        <div class="relative mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            </svg>
                            <input wire:model.live.debounce.300ms="linkProductSearch" type="text" placeholder="Search product to link…"
                                class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>

                        @if($linkProductSearch !== '')
                            <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-40 overflow-y-auto mb-3">
                                @forelse($linkableProducts as $lp)
                                    <button wire:click="linkToProduct({{ $lp->id }})" type="button"
                                        class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 transition text-left">
                                        <span class="text-sm text-gray-800">{{ $lp->name }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                        </svg>
                                    </button>
                                @empty
                                    <p class="px-3 py-2 text-xs text-gray-400">No matching products.</p>
                                @endforelse
                            </div>
                        @endif

                        @if($editingVariant->links->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($editingVariant->links as $link)
                                    <span class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        {{ $link->linkedProduct->name }}
                                        <button wire:click="unlinkProduct({{ $link->id }})" type="button" class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-gray-300 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0">
                <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="saveVariant" type="button" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Save Variant</button>
            </div>
        </div>
    </div>

    @script
    <script>
        let attrOrderSortable = null;
        let variantSortable = null;

        function initAttrOrderSortable() {
            if (attrOrderSortable) attrOrderSortable.destroy();
            const el = document.getElementById('product-attribute-list');
            if (!el) return;
            attrOrderSortable = new window.Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd() {
                    const orderedIds = Array.from(el.children).map(li => parseInt(li.dataset.id, 10)).filter(Boolean);
                    $wire.reorderAttributes(orderedIds);
                },
            });
        }

        function initVariantSortable() {
            if (variantSortable) variantSortable.destroy();
            const el = document.getElementById('product-variant-list');
            if (!el) return;
            variantSortable = new window.Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd() {
                    const orderedIds = Array.from(el.children).map(li => parseInt(li.dataset.id, 10)).filter(Boolean);
                    $wire.reorderVariants(orderedIds);
                },
            });
        }

        let valueSortables = [];
        function initValueSortables() {
            valueSortables.forEach(s => s.destroy());
            valueSortables = Array.from(document.querySelectorAll('.product-value-sortable')).map(el => new window.Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd() {
                    const orderedIds = Array.from(el.children).map(li => parseInt(li.dataset.id, 10)).filter(Boolean);
                    $wire.reorderValues(parseInt(el.dataset.productAttributeId, 10), orderedIds);
                },
            }));
        }

        initAttrOrderSortable();
        initVariantSortable();
        initValueSortables();
        Livewire.hook('morph.updated', ({ el }) => {
            if (el.id === 'product-attribute-list' || el.closest?.('#product-attribute-list')) {
                initAttrOrderSortable();
                initValueSortables();
            }
            if (el.id === 'product-variant-list' || el.closest?.('#product-variant-list')) {
                initVariantSortable();
            }
        });
    </script>
    @endscript

</div>
