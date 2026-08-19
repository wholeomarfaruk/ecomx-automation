<div class="checkout" x-data="{ payment: '{{ $payment_method }}' }">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);margin-bottom:12px"><a href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <span style="color:var(--pri)">Checkout</span></nav>
    <h1 style="font-size:clamp(28px,3.4vw,40px);margin-bottom:6px">Checkout</h1>
    <p class="muted" style="font-size:13.5px;margin-bottom:28px">Complete your shipping and payment details to place the order.</p>

    @if($placed)
        <div class="checkout__success">
            <div class="checkout__success-icon">✓</div>
            <h2 style="font-family:'Playfair Display',serif;font-size:24px;margin-bottom:8px">Order placed — thank you!</h2>
            <p class="muted" style="font-size:13.5px;margin-bottom:20px">This is a demo checkout — no real order was created. A confirmation would normally be sent to {{ $phone ?: 'your phone' }}.</p>
            <a href="{{ route('ecomx-fashion.home') }}" class="btn btn--primary btn--pill">Continue shopping</a>
        </div>
    @else
    <div class="checkout__grid">
        <div class="checkout__form">
            <form wire:submit.prevent="placeOrder">
                <div class="field-grid">
                    <div class="field">
                        <label>Full name</label>
                        <input type="text" wire:model="name" placeholder="Enter your full name" required>
                        @error('name') <span class="field__error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label>Phone number</label>
                        <input type="text" inputmode="tel" wire:model="phone" placeholder="01XXXXXXXXX" required>
                        @error('phone') <span class="field__error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="field">
                    <label>Delivery address</label>
                    <textarea wire:model="address" rows="3" placeholder="House, road, area, city" required></textarea>
                    @error('address') <span class="field__error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label>Order note <span class="muted">(optional)</span></label>
                    <textarea wire:model="note" rows="2" placeholder="Delivery instructions, gift note, etc."></textarea>
                </div>

                <div class="field">
                    <label>Delivery area</label>
                    <select wire:model.live="delivery_area">
                        @foreach($deliveryAreas as $area)
                            <option value="{{ $area['id'] }}">{{ $area['name'] }} — ৳{{ $area['charge'] }}</option>
                        @endforeach
                    </select>
                    @error('delivery_area') <span class="field__error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label>Payment method</label>
                    <div class="pay-options">
                        <label class="pay-option" :class="payment==='cod' && 'is-on'">
                            <span>
                                <strong>Cash on Delivery</strong>
                                <small>Pay when the order is delivered to you.</small>
                            </span>
                            <input type="radio" wire:model="payment_method" value="cod" x-model="payment">
                        </label>
                        <label class="pay-option" :class="payment==='bkash' && 'is-on'">
                            <span>
                                <strong>bKash</strong>
                                <small>Send money first, then submit the transaction ID.</small>
                            </span>
                            <input type="radio" wire:model="payment_method" value="bkash" x-model="payment">
                        </label>
                    </div>
                    @error('payment_method') <span class="field__error">{{ $message }}</span> @enderror
                </div>

                <div class="bkash-box" x-show="payment==='bkash'" x-collapse>
                    <p class="bkash-box__title">bKash Payment Instructions</p>
                    <p style="font-size:12.5px;margin-bottom:8px">Send money to this bKash number: <strong>{{ $bkashNumber }}</strong></p>
                    <ol style="font-size:12.5px;line-height:1.7;padding-left:18px;margin-bottom:14px">
                        <li>এই নাম্বারে bKash থেকে Send Money করুন।</li>
                        <li>পেমেন্ট সম্পন্ন হলে নিচের ঘরে Transaction ID লিখুন।</li>
                        <li>আমরা পেমেন্ট যাচাই করে আপনার অর্ডার কনফার্ম করব।</li>
                    </ol>
                    <div class="field" style="margin-bottom:0">
                        <label>Transaction ID</label>
                        <input type="text" wire:model="transaction_id" placeholder="Enter your bKash transaction ID">
                        @error('transaction_id') <span class="field__error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn--primary btn--block" style="margin-top:20px">Place Order</button>
            </form>
        </div>

        <div class="checkout__summary-card">
                <h2 style="font-family:'Playfair Display',serif;font-size:19px;margin-bottom:16px">Order Summary</h2>

                @php
                    $summaryRegularTotal = 0;
                    $summarySavings = 0;
                @endphp

                @if ($cart->items->isEmpty())
                    <p class="muted" style="font-size:13px">Your cart is empty — nothing to check out yet.</p>
                @endif

                <div class="checkout__items">
                    @foreach ($cart->items as $item)
                        @php
                            $product = $item->product;
                            $variant = $item->variant;
                            $options = $variant?->options_map ?? [];
                            $comparePrice = $variant ? $variant->price : $product?->price;
                            $lineSavings = (((float) $comparePrice) - (float) $item->price) * $item->quantity;
                            $summaryRegularTotal += ((float) $comparePrice) * $item->quantity;
                            $summarySavings += max(0, $lineSavings);
                        @endphp
                        <div class="checkout__item" wire:key="checkout-item-{{ $item->id }}">
                            <div class="checkout__item-media">
                                <x-ux-img :id="$product?->featured_image" :w="160" :alt="$product?->name ?? ''" />
                            </div>
                            <div class="checkout__item-body">
                                <span class="checkout__item-name">{{ $product?->name ?? 'Deleted product' }}</span>
                                <span class="muted" style="font-size:11.5px">{{ implode(' · ', $options) }}{{ ! empty($options) ? ' · ' : '' }}Qty: {{ $item->quantity }}</span>
                                <span class="checkout__item-total">৳{{ number_format($item->price * $item->quantity) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="checkout__totals" x-data="{ charge: {{ Illuminate\Support\Js::from(collect($deliveryAreas)->pluck('charge', 'id')) }}, savingsOpen: false }">
                    <div class="checkout__totals-row">
                        <span>Subtotal</span>
                        <strong>৳{{ number_format($cart->subtotal) }}</strong>
                    </div>
                    <div class="checkout__totals-row">
                        <span>Delivery charge</span>
                        <strong x-text="'৳' + (charge['{{ $delivery_area }}'] ?? 0)"></strong>
                    </div>
                    @if ($summarySavings > 0)
                        <button type="button" class="checkout__totals-row checkout__totals-row--save checkout__totals-row--save-toggle" @click="savingsOpen=!savingsOpen" @click.outside="savingsOpen=false">
                            <span>💰 You're saving</span>
                            <strong>৳{{ number_format($summarySavings) }}</strong>
                        </button>
                        <div class="checkout__savings-tip" x-show="savingsOpen" x-cloak x-transition:enter.duration.150ms>
                            <div class="cart-savings__row"><span>🏷️ Regular price</span><span>৳{{ number_format($summaryRegularTotal) }}</span></div>
                            <div class="cart-savings__row"><span>💳 Discount price</span><span>৳{{ number_format($cart->subtotal) }}</span></div>
                            <div class="cart-savings__row cart-savings__row--total"><span>✨ Your savings</span><span>৳{{ number_format($summarySavings) }}</span></div>
                        </div>
                    @endif
                    <div class="checkout__totals-row checkout__totals-row--grand">
                        <span>Total</span>
                        <strong x-text="'৳' + ({{ (float) $cart->subtotal }} + (charge['{{ $delivery_area }}'] ?? 0)).toLocaleString()"></strong>
                    </div>
                </div>
        </div>
    </div>
    @endif
</div>
