{{-- Pencil-icon "edit variant" modal for one cart line — reuses the theme's
     .modal / .swatch-sq / .size-grid / .qty CSS (see product.blade.php for
     the same picker pattern on the PDP). Only rendered/mounted when the
     product actually has variants (see cart-manager.blade.php). --}}
<div x-data="{ open:false }" @close-edit-cart-item-{{ $cartItemId }}.window="open=false" style="display:inline-flex">
    <button type="button" class="cart-item__edit" @click="open=true" aria-label="Edit variant">
        <x-icon name="pencil" />
    </button>

    <template x-if="open">
        <div class="modal" @click.self="open=false">
            <div class="modal__box modal__box--md edit-variant-modal">
                <div class="modal__head">
                    <p class="modal__title" style="font-size:20px">Edit item</p>
                    <button type="button" class="modal__close" @click="open=false">✕</button>
                </div>

                <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid rgba(var(--pri-rgb),.08)">
                    <div style="flex:none;width:56px;height:70px;border-radius:8px;overflow:hidden;background:rgba(var(--pri-rgb),.05)">
                        <x-ux-img :id="$productImage" :w="150" :alt="$productName" />
                    </div>
                    <span style="font-size:14px;font-weight:600;line-height:1.3;color:var(--pri)">{{ $productName }}</span>
                </div>

                @if ($hasVariants)
                    @if (! empty($colors))
                        <div style="margin-bottom:16px">
                            <div style="font-size:12.5px;font-weight:600;margin-bottom:10px">Colour — {{ $selectedColor ?: 'Choose one' }}</div>
                            <div style="display:flex;gap:10px;flex-wrap:wrap">
                                @foreach ($colors as $c)
                                    <button type="button" class="swatch-sq {{ $selectedColor === $c['name'] ? 'is-on' : '' }}" style="background:{{ $c['hex'] }}" wire:click="pickColor('{{ $c['name'] }}')" aria-label="{{ $c['name'] }}">
                                        @if ($selectedColor === $c['name'])
                                            <span style="position:absolute;right:3px;bottom:3px;width:16px;height:16px;border-radius:999px;background:var(--ac);color:#fff;font-size:9px;display:flex;align-items:center;justify-content:center">✓</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($sizes))
                        <div style="margin-bottom:16px">
                            <div style="font-size:12.5px;font-weight:600;margin-bottom:10px">Size — {{ $selectedSize ?: 'Choose one' }}</div>
                            <div class="size-grid">
                                @foreach ($sizes as $z)
                                    @php
                                        $colorKey = ! empty($colors) ? ($selectedColor ?: '*') : '*';
                                        $stock = $variantMatrix[$colorKey . '|' . $z]['stock'] ?? null;
                                        $outOfStock = $stock !== null && $stock <= 0;
                                    @endphp
                                    <button type="button" class="size-btn size-btn--outline {{ $selectedSize === $z ? 'is-on' : '' }}" wire:click="pickSize('{{ $z }}')" @if ($outOfStock) disabled @endif>
                                        {{ $z }}
                                        @if ($selectedSize === $z)<span class="size-btn__check">✓</span>@endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                <div style="margin-bottom:20px">
                    <div style="font-size:12.5px;font-weight:600;margin-bottom:10px">Quantity</div>
                    <div class="qty">
                        <button type="button" wire:click="$set('qty', {{ max(1, $qty - 1) }})" aria-label="Decrease quantity">−</button>
                        <span>{{ $qty }}</span>
                        <button type="button" wire:click="$set('qty', {{ $qty + 1 }})" aria-label="Increase quantity">+</button>
                    </div>
                </div>

                <button type="button" class="btn btn--primary btn--block" wire:click="save">Save changes</button>
            </div>
        </div>
    </template>
</div>
