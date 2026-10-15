<div class="checkout" x-data="{
    payment: '{{ $payment_method }}',
    placing: false,
    step: 0,
    steps: ['Checking your items…', 'Confirming your order…', 'Almost done…'],
    timer: null,
    place() {
        this.placing = true;
        this.step = 0;
        clearInterval(this.timer);
        this.timer = setInterval(() => { if (this.step < this.steps.length - 1) this.step++ }, 1500);
        this.$wire.placeOrder()
            .then(placed => { if (!placed) this.stopPlacing() })
            .catch(() => this.stopPlacing());
    },
    stopPlacing() {
        clearInterval(this.timer);
        this.placing = false;
    },
}">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);margin-bottom:12px"><a
            href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <span
            style="color:var(--pri)">Checkout</span></nav>
    <h1 style="font-size:clamp(28px,3.4vw,40px);margin-bottom:6px">Checkout</h1>
    <p class="muted" style="font-size:13.5px;margin-bottom:28px">Complete your shipping and payment details to place the
        order.</p>

    @if ($placed)
        @php($siteName = \App\Models\Setting::get('site_name', 'Seldom Fashion') ?: 'Seldom Fashion')
        <div class="checkout__success">
            <div class="checkout__success-head">
                <div class="checkout__success-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12.5l4.5 4.5L19 7.5" />
                    </svg>
                </div>
                <div>
                    <p class="checkout__success-ref">Confirmation #{{ $orderId }}</p>
                    <h2 class="checkout__success-title">Thank you{{ $name ? ', ' . $name : '' }}!</h2>
                </div>
            </div>
            <div class="checkout__success-card">
                <h3>Your order is confirmed</h3>
                <p>Thank you for your order with {{ $siteName }}.</p>
                <p>Please keep the exact amount of cash ready at the time of delivery. Our delivery partner will contact
                    you{{ $phone ? ' on ' . $phone : '' }} before delivery.</p>
                <p class="checkout__success-note">
                    <strong>অর্ডার টি কনফার্ম হয়েছে</strong><br>
                    কোনো কারণে পার্সেল রিটার্ন অথবা বাতিল করলে, ডেলিভারি চার্জ দিয়ে রিটার্ন বা বাতিল করতে হবে।<br>
                    পণ্যে কোনো ধরনের সমস্যা হলে <strong>২৪ ঘণ্টার মধ্যে</strong> আমাদের জানালে আমরা দ্রুত সমাধান করে
                    দেবো।
                </p>
                <p>If you need any assistance, feel free to contact us via WhatsApp or Facebook.</p>
            </div>
            @if ($order = $this->placedOrder)
                @php
                    $sm = $order->shipping_meta ?? [];
                    $pay = $order->paymentSummary();
                    $onlinePayment = $order->payments->firstWhere('type', \App\Enums\Sales\OrderPaymentType::PAYMENT);
                @endphp
                <div class="checkout__success-card checkout__details">
                    <h3>Order details</h3>

                    <div class="checkout__details-part">
                        <h4>Products</h4>
                        @foreach ($order->items as $item)
                            <div class="checkout__details-item">
                                <img src="{{ $item->variant?->display_image ?? ($item->product?->featured_image ?? '') }}"
                                    alt="{{ $item->product_name }}" loading="lazy">
                                <div class="checkout__details-item-info">
                                    <div class="checkout__details-item-name">{{ $item->product_name }}</div>
                                    @if ($item->variant_name)
                                        <div class="checkout__details-muted">{{ $item->variant_name }}</div>
                                    @endif
                                    <div class="checkout__details-muted">৳{{ number_format($item->unit_price, 2) }} ×
                                        {{ (int) $item->quantity }}</div>
                                </div>
                                <div class="checkout__details-amount">৳{{ number_format($item->total_amount, 2) }}
                                </div>
                            </div>
                        @endforeach
                        <dl class="checkout__details-totals">
                            <div>
                                <dt>Subtotal</dt>
                                <dd>৳{{ number_format($order->subtotal, 2) }}</dd>
                            </div>
                            @if ((float) $order->discount_amount > 0)
                                <div>
                                    <dt>Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</dt>
                                    <dd>−৳{{ number_format($order->discount_amount, 2) }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>Delivery charge</dt>
                                <dd>{{ (float) $order->shipping_amount - (float) $order->shipping_discount > 0 ? '৳' . number_format($order->shipping_amount - $order->shipping_discount, 2) : 'Free' }}
                                </dd>
                            </div>
                            @foreach ($order->charges as $charge)
                                <div>
                                    <dt>{{ $charge->label }}</dt>
                                    <dd>৳{{ number_format($charge->amount, 2) }}</dd>
                                </div>
                            @endforeach
                            <div class="checkout__details-total">
                                <dt>Total</dt>
                                <dd>৳{{ number_format($order->total_amount, 2) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="checkout__details-part">
                        <h4>Payment</h4>
                        <dl class="checkout__details-rows">
                            <div>
                                <dt>Method</dt>
                                <dd>{{ $onlinePayment ? $onlinePayment->payment_method?->label() ?? 'Online payment' : 'Cash on delivery' }}
                                </dd>
                            </div>
                            @if ($onlinePayment?->transaction_id)
                                <div>
                                    <dt>Transaction ID</dt>
                                    <dd>{{ $onlinePayment->transaction_id }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>Status</dt>
                                <dd>{{ ['paid' => 'Paid', 'verifying' => 'Verifying payment', 'partial' => 'Partially paid', 'unpaid' => 'Pay on delivery'][$pay['state']] }}
                                </dd>
                            </div>
                            @if ($pay['paid'] > 0)
                                <div>
                                    <dt>Paid</dt>
                                    <dd>৳{{ number_format($pay['paid'], 2) }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>Amount due</dt>
                                <dd>৳{{ number_format($pay['due'], 2) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="checkout__details-part">
                        <h4>Shipping</h4>
                        <dl class="checkout__details-rows">
                            <div>
                                <dt>Name</dt>
                                <dd>{{ $order->shippingAddress?->name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt>Phone</dt>
                                <dd>{{ $order->shippingAddress?->phone ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt>Address</dt>
                                <dd>{{ $order->shippingAddress?->full_address ?: '—' }}</dd>
                            </div>
                            @if (!empty($sm['method']) || !empty($sm['zone']))
                                <div>
                                    <dt>Delivery</dt>
                                    <dd>{{ collect([$sm['zone'] ?? null, $sm['method'] ?? null])->filter()->implode(' · ') }}
                                    </dd>
                                </div>
                            @endif
                            @if (!empty($sm['delivery_time']))
                                <div>
                                    <dt>Estimated time</dt>
                                    <dd>{{ $sm['delivery_time'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
                <a href="{{ auth()->check() ? route('ecomx-fashion.track', ['order' => $orderId, 'phone' => $phone]) : route('ecomx-fashion.track', ['order_id' => $orderId, 'phone' => $phone]) }}"
                    class="btn btn--outline btn--pill">Track order</a>
                <a href="{{ route('ecomx-fashion.home') }}" class="btn btn--primary btn--pill">Continue shopping</a>
            </div>
        </div>
    @else
        <div class="checkout__grid">
            <div class="checkout__form">
                <form x-on:submit.prevent="place()">
                    @auth
                        @if ($this->savedAddresses->isNotEmpty())
                            <div class="field" style="margin-bottom:20px">
                                <label>Delivery address</label>

                                @error('selectedAddressId')
                                    <span class="field__error">{{ $message }}</span>
                                @enderror

                                <div class="address-list">
                                    @foreach ($this->savedAddresses as $savedAddress)
                                        <label
                                            class="address-card {{ $selectedAddressId === $savedAddress->id ? 'is-on' : '' }}"
                                            wire:key="address-{{ $savedAddress->id }}">
                                            <input type="radio" name="selectedAddressId" value="{{ $savedAddress->id }}"
                                                wire:click="selectAddress({{ $savedAddress->id }})"
                                                @checked($selectedAddressId === $savedAddress->id)>
                                            <div class="address-card__body">
                                                <div class="address-card__head">
                                                    <strong>{{ $savedAddress->name }}</strong>
                                                    @if ($savedAddress->address_type)
                                                        <span
                                                            class="address-card__tag">{{ $savedAddress->address_type }}</span>
                                                    @endif
                                                    @if ($savedAddress->is_default_shipping)
                                                        <span class="address-card__default">Default</span>
                                                    @endif
                                                </div>
                                                <span class="address-card__phone">{{ $savedAddress->phone }}</span>
                                                <span class="address-card__text">{{ $savedAddress->full_address }}</span>
                                            </div>
                                            <button type="button" class="address-card__edit"
                                                wire:click.stop.prevent="editAddress({{ $savedAddress->id }})"
                                                aria-label="Edit address">Edit</button>
                                        </label>
                                    @endforeach

                                    <button type="button" class="address-card address-card--add"
                                        wire:click="showAddAddressForm">
                                        <span class="address-card__plus">+</span>
                                        <span>Add new address</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endauth

                    {{-- Inline form: only for guests, or a logged-in customer with --}}
                    {{-- no saved addresses yet — nothing to pick from a modal in --}}
                    {{-- that case, so this collects the very first address. --}}
                    @if ($this->savedAddresses->isEmpty())
                        <div class="field-grid">
                            <div class="field">
                                <label>Full name</label>
                                <input type="text" wire:model="name" placeholder="Enter your full name" required>
                                @error('name')
                                    <span class="field__error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field">
                                <label>Phone number</label>
                                <input type="text" inputmode="tel" wire:model="phone" placeholder="01XXXXXXXXX"
                                    required>
                                @error('phone')
                                    <span class="field__error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="field">
                            <label>Delivery address</label>
                            <textarea wire:model="address" rows="3" placeholder="House, road, area, city" required></textarea>
                            @error('address')
                                <span class="field__error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <div class="field">
                        <label>Delivery area</label>
                        <select wire:model.live="delivery_area">
                            @foreach ($this->shippingZones as $zone)
                                @php $zoneCharge = $zoneCharges[$zone->code] ?? 0; @endphp
                                <option value="{{ $zone->code }}">{{ $zone->name }} —
                                    {{ $zoneCharge > 0 ? '৳' . number_format($zoneCharge) : 'Free' }}</option>
                            @endforeach
                        </select>
                        @error('delivery_area')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    @if (count($methodOptions) > 1)
                        <div class="field">
                            <label>Delivery option</label>
                            <div class="pay-options">
                                @foreach ($methodOptions as $option)
                                    <label
                                        class="pay-option {{ $shipping_method_id === $option['id'] ? 'is-on' : '' }}"
                                        wire:key="ship-method-{{ $option['id'] }}">
                                        <span>
                                            <strong>{{ $option['name'] }} —
                                                {{ $option['charge'] > 0 ? '৳' . number_format($option['charge']) : 'Free' }}</strong>
                                            @if ($option['delivery_time'])
                                                <small>{{ $option['delivery_time'] }}</small>
                                            @endif
                                        </span>
                                        <input type="radio" wire:model.live="shipping_method_id"
                                            value="{{ $option['id'] }}">
                                    </label>
                                @endforeach
                            </div>
                            @error('shipping_method_id')
                                <span class="field__error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <div class="field">
                        <label>Payment method</label>
                        <div class="pay-options">
                            <label class="pay-option" :class="payment === 'cod' && 'is-on'">
                                <span>
                                    <strong>Cash on Delivery</strong>
                                    <small>Pay when the order is delivered to you.</small>
                                </span>
                                <input type="radio" wire:model.live="payment_method" value="cod"
                                    x-model="payment">
                            </label>
                            <label class="pay-option" :class="payment === 'bkash' && 'is-on'">
                                <span>
                                    <strong>bKash</strong>
                                    <small>Send money first, then submit the transaction ID.</small>
                                </span>
                                <input type="radio" wire:model.live="payment_method" value="bkash"
                                    x-model="payment">
                            </label>
                        </div>
                        @error('payment_method')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="bkash-box" x-show="payment==='bkash'" x-collapse>
                        <p class="bkash-box__title">bKash Payment Instructions</p>
                        <p style="font-size:12.5px;margin-bottom:8px">Send money to this bKash number:
                            <strong>{{ $bkashNumber }}</strong></p>
                        <ol style="font-size:12.5px;line-height:1.7;padding-left:18px;margin-bottom:14px">
                            <li>এই নাম্বারে bKash থেকে Send Money করুন।</li>
                            <li>পেমেন্ট সম্পন্ন হলে নিচের ঘরে Transaction ID লিখুন।</li>
                            <li>আমরা পেমেন্ট যাচাই করে আপনার অর্ডার কনফার্ম করব।</li>
                        </ol>
                        <div class="field" style="margin-bottom:0">
                            <label>Transaction ID</label>
                            <input type="text" wire:model="transaction_id"
                                placeholder="Enter your bKash transaction ID">
                            @error('transaction_id')
                                <span class="field__error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn--primary btn--block" style="margin-top:20px"
                        x-bind:disabled="placing">Place Order</button>
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
                            $comparePrice = $product?->regularPrice($variant) ?? 0;
                            $lineSavings = (((float) $comparePrice) - (float) $item->price) * $item->quantity;
                            $summaryRegularTotal += ((float) $comparePrice) * $item->quantity;
                            $summarySavings += max(0, $lineSavings);
                        @endphp
                        <div class="checkout__item" wire:key="checkout-item-{{ $item->id }}">
                            <div class="checkout__item-media">
                                <x-ux-img :id="$item->display_image" :w="160" :alt="$product?->name ?? ''" />
                            </div>
                            <div class="checkout__item-body">
                                <span class="checkout__item-name">{{ $product?->name ?? 'Deleted product' }}</span>
                                <span class="muted"
                                    style="font-size:11.5px">{{ implode(' · ', $options) }}{{ !empty($options) ? ' · ' : '' }}Qty:
                                    {{ $item->quantity }}</span>
                                <span
                                    class="checkout__item-total">৳{{ number_format($item->price * $item->quantity) }}</span>
                                @if (($offers['lines'][$item->id] ?? 0) > 0)
                                    <span style="font-size:11.5px;color:var(--ac2);font-weight:600">🎁 Offer
                                        −৳{{ number_format($offers['lines'][$item->id]) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="checkout__totals" x-data="{ savingsOpen: false }">
                    <div class="checkout__totals-row">
                        <span>Subtotal</span>
                        <strong>৳{{ number_format($cart->subtotal) }}</strong>
                    </div>
                    <div class="checkout__totals-row">
                        <span>Delivery charge</span>
                        <strong>{{ $deliveryCharge > 0 ? '৳' . number_format($deliveryCharge) : 'Free' }}</strong>
                    </div>
                    @if ($shippingQuote && $shippingQuote->remainingForFree > 0)
                        <p class="muted" style="font-size:12px;margin:-2px 0 6px">Add
                            ৳{{ number_format($shippingQuote->remainingForFree) }} more for free delivery.</p>
                    @endif
                    @foreach ($offers['applied'] as $applied)
                        <div class="checkout__totals-row checkout__totals-row--save"
                            wire:key="checkout-offer-{{ $applied['promotion_id'] }}">
                            <span>🎁 {{ $applied['name'] }}</span>
                            <strong>−৳{{ number_format($applied['discount'] + $applied['shipping_discount']) }}</strong>
                        </div>
                    @endforeach
                    @if ($summarySavings > 0)
                        <button type="button"
                            class="checkout__totals-row checkout__totals-row--save checkout__totals-row--save-toggle"
                            @click="savingsOpen=!savingsOpen" @click.outside="savingsOpen=false">
                            <span>💰 You're saving</span>
                            <strong>৳{{ number_format($summarySavings) }}</strong>
                        </button>
                        <div class="checkout__savings-tip" x-show="savingsOpen" x-cloak
                            x-transition:enter.duration.150ms>
                            <div class="cart-savings__row"><span>🏷️ Regular
                                    price</span><span>৳{{ number_format($summaryRegularTotal) }}</span></div>
                            <div class="cart-savings__row"><span>💳 Discount
                                    price</span><span>৳{{ number_format($cart->subtotal) }}</span></div>
                            <div class="cart-savings__row cart-savings__row--total"><span>✨ Your
                                    savings</span><span>৳{{ number_format($summarySavings) }}</span></div>
                        </div>
                    @endif
                    <div class="checkout__totals-row checkout__totals-row--grand">
                        <span>Total</span>
                        <strong>৳{{ number_format(max(0, (float) $cart->subtotal - $offers['discount']) + max(0, $deliveryCharge - $offers['shipping_discount'])) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @auth
            @if ($showNewAddressForm && $this->savedAddresses->isNotEmpty())
                <div class="modal" @click.self="$wire.closeAddAddressForm()">
                    <div class="modal__box modal__box--md">
                        <div class="modal__head">
                            <p class="modal__title" style="font-size:22px">
                                {{ $editingAddressId ? 'Edit address' : 'Add new address' }}</p>
                            <button type="button" class="modal__close" wire:click="closeAddAddressForm"
                                aria-label="Close">✕</button>
                        </div>

                        <form wire:submit.prevent="saveNewAddress">
                            <div class="field-grid">
                                <div class="field">
                                    <label>Full name</label>
                                    <input type="text" wire:model="name" placeholder="Enter your full name" required>
                                    @error('name')
                                        <span class="field__error">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="field">
                                    <label>Phone number</label>
                                    <input type="text" inputmode="tel" wire:model="phone" placeholder="01XXXXXXXXX"
                                        required>
                                    @error('phone')
                                        <span class="field__error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="field">
                                <label>Delivery address</label>
                                <textarea wire:model="address" rows="3" placeholder="House, road, area, city" required></textarea>
                                @error('address')
                                    <span class="field__error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="field">
                                <label>Address label <span class="muted">(optional)</span></label>
                                <input type="text" wire:model="address_type" placeholder="Home, Office, etc.">
                            </div>

                            <label class="address-form__default-toggle">
                                <input type="checkbox" wire:model="setAsDefault">
                                <span>Set as my default address</span>
                            </label>

                            <button type="submit" class="btn btn--primary btn--block" wire:loading.attr="disabled"
                                wire:target="saveNewAddress">{{ $editingAddressId ? 'Save Changes' : 'Save Address' }}</button>
                        </form>
                    </div>
                </div>
            @endif
        @endauth
    @endif

    {{-- Up from the Place Order click until the order-received page loads. --}}
    <div class="checkout__placing" x-show="placing" x-transition.opacity x-cloak role="status" aria-live="polite">
        <div class="checkout__placing-card">
            <div class="checkout__placing-spinner"></div>
            <h3 class="checkout__placing-title">Placing your order</h3>
            <p class="checkout__placing-step" x-text="steps[step]"></p>
            <p class="checkout__placing-note">Please don't close or refresh this page.</p>
        </div>
    </div>

    <x-marketing.events :events="$marketingEvents" />
</div>
