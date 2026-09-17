{{-- Pencil-icon "edit variant" modal for one cart line. Restyled onto the
     theme's real .jtc-modal / .jtc-btn system (see auth-modal.blade.php for
     the same pattern) — swatch/size pickers use plain inline styles since
     this theme doesn't ship dedicated swatch/size-grid components. Only
     rendered/mounted when the product actually has variants (see
     cart-manager.blade.php). --}}
<div x-data="{ open:false }" @close-edit-cart-item-{{ $cartItemId }}.window="open=false" style="display:inline-flex">
    <button type="button" class="jtc-cart__edit" @click="open=true" aria-label="Edit variant">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
    </button>

    <template x-if="open">
        <div class="jtc-modal-scrim is-open" @click.self="open=false">
            <div class="jtc-modal">
                <div class="jtc-modal__head">
                    <button type="button" class="jtc-modal__close" aria-label="Close" @click="open=false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
                    </button>
                    <h3>Edit item</h3>
                </div>

                <div class="jtc-form">
                    <div style="display:flex;gap:12px;align-items:center;padding-bottom:16px;border-bottom:1px solid #e6eae7">
                        <img src="{{ $productImage ? file_path($productImage) : '' }}" alt="{{ $productName }}" style="flex:none;width:56px;height:70px;border-radius:7px;object-fit:cover;background:#f2f5f4">
                        <span style="font-size:14px;font-weight:600;line-height:1.3">{{ $productName }}</span>
                    </div>

                    @if ($hasVariants)
                        @if (! empty($colors))
                            <div>
                                <div style="font-size:12.5px;font-weight:600;margin-bottom:10px">Colour — {{ $selectedColor ?: 'Choose one' }}</div>
                                <div style="display:flex;gap:10px;flex-wrap:wrap">
                                    @foreach ($colors as $c)
                                        <button type="button" wire:click="pickColor('{{ $c['name'] }}')" aria-label="{{ $c['name'] }}"
                                            style="position:relative;width:32px;height:32px;border-radius:7px;background:{{ $c['hex'] }};border:2px solid {{ $selectedColor === $c['name'] ? '#1B7FC4' : 'transparent' }};cursor:pointer;padding:0">
                                            @if ($selectedColor === $c['name'])
                                                <span style="position:absolute;right:-3px;bottom:-3px;width:16px;height:16px;border-radius:999px;background:#1B7FC4;color:#fff;font-size:9px;display:flex;align-items:center;justify-content:center">✓</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($sizes))
                            <div>
                                <div style="font-size:12.5px;font-weight:600;margin-bottom:10px">Size — {{ $selectedSize ?: 'Choose one' }}</div>
                                <div style="display:flex;gap:8px;flex-wrap:wrap">
                                    @foreach ($sizes as $z)
                                        @php
                                            $colorKey = ! empty($colors) ? ($selectedColor ?: '*') : '*';
                                            $stock = $variantMatrix[$colorKey . '|' . $z]['stock'] ?? null;
                                            $outOfStock = $stock !== null && $stock <= 0;
                                        @endphp
                                        <button type="button" class="jtc-btn {{ $selectedSize === $z ? 'jtc-btn--primary' : 'jtc-btn--outline' }} jtc-form__channel-toggle"
                                            wire:click="pickSize('{{ $z }}')" @if ($outOfStock) disabled style="opacity:.4;cursor:not-allowed" @endif>
                                            {{ $z }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    <div>
                        <div style="font-size:12.5px;font-weight:600;margin-bottom:10px">Quantity</div>
                        <div class="jtc-cart__qty" style="display:inline-flex">
                            <button type="button" wire:click="$set('qty', {{ max(1, $qty - 1) }})" aria-label="Decrease quantity">−</button>
                            <span>{{ $qty }}</span>
                            <button type="button" wire:click="$set('qty', {{ $qty + 1 }})" aria-label="Increase quantity">+</button>
                        </div>
                    </div>

                    <button type="button" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit" wire:click="save">Save changes</button>
                </div>
            </div>
        </div>
    </template>
</div>
