@php
    $isCancelled = $order && in_array($order->status, [
        \App\Enums\Sales\OrderStatus::CANCELLED,
        \App\Enums\Sales\OrderStatus::RETURNED,
        \App\Enums\Sales\OrderStatus::PARTIALLY_RETURNED,
        \App\Enums\Sales\OrderStatus::REFUNDED,
    ], true);
@endphp

<div class="jtc-track">
    @if($order)
        <div class="jtc-track__head">
            <h1>Order #{{ $order->id }}</h1>
            <p>Placed on {{ $order->placed_at?->format('d M Y, h:i A') ?? $order->created_at->format('d M Y, h:i A') }}</p>
        </div>

        <div class="jtc-track-summary">
            <div class="jtc-track-summary__head">
                <div>
                    <div class="jtc-track-summary__id">Order #{{ $order->id }}</div>
                    <div class="jtc-track-summary__date">{{ $order->placed_at?->format('d M Y, h:i A') ?? $order->created_at->format('d M Y, h:i A') }}</div>
                </div>
                <span class="jtc-order-status jtc-order-status--{{ $order->status->value }}">{{ $order->status->label() }}</span>
            </div>

            @if($isCancelled)
                <div class="jtc-track-timeline jtc-track-timeline--cancelled">
                    <div class="jtc-track-step is-cancelled">
                        <span class="jtc-track-step__dot">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </span>
                        <span class="jtc-track-step__label">{{ $order->status->label() }}</span>
                    </div>
                </div>
            @else
                <div class="jtc-track-timeline">
                    @foreach($steps as $i => $st)
                        <div class="jtc-track-step {{ $st['done'] ? 'is-done' : ($st['current'] ? 'is-current' : '') }}">
                            <span class="jtc-track-step__dot">
                                @if($st['done'])
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            <span class="jtc-track-step__label">{{ $st['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="jtc-track-summary__grid">
                <div>
                    <div class="jtc-track-summary__label">Recipient</div>
                    <div class="jtc-track-summary__value">{{ $order->shippingAddress->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="jtc-track-summary__label">Phone</div>
                    <div class="jtc-track-summary__value">{{ $order->shippingAddress->phone ?? '—' }}</div>
                </div>
                <div>
                    <div class="jtc-track-summary__label">Delivery address</div>
                    <div class="jtc-track-summary__value">{{ $order->shippingAddress->full_address ?? '—' }}</div>
                </div>
                <div>
                    <div class="jtc-track-summary__label">Total</div>
                    <div class="jtc-track-summary__value">৳{{ number_format($order->total_amount, 2) }}</div>
                </div>
            </div>

            @if($order->courier_tracking_number)
                <div style="padding:12px 14px;background:rgba(0,0,0,.03);border-radius:10px;margin-top:16px">
                    <p style="font-size:0.76rem;color:#6b7a73;margin:0 0 2px">Courier tracking</p>
                    <p style="font-size:0.86rem;font-weight:600;margin:0">{{ $order->courier_provider ?? 'Courier' }} · {{ $order->courier_tracking_number }}</p>
                </div>
            @endif

            @if($order->status === \App\Enums\Sales\OrderStatus::PENDING)
                <div style="margin-top:16px">
                    <button type="button" wire:click="openConfirmModal" class="jtc-btn jtc-btn--primary jtc-btn--block" style="padding:12px">
                        Confirm Order
                    </button>
                </div>
            @endif
        </div>

        <div class="jtc-order-card">
            <div class="jtc-order-card__head">
                <div class="jtc-order-card__id">Items</div>
            </div>
            <div class="jtc-order-card__items">
                @foreach($order->items as $item)
                    <div class="jtc-order-item">
                        <div class="jtc-order-item__thumb">
                            <img src="{{ $item->product?->featured_image ?? '' }}" alt="">
                        </div>
                        <div class="jtc-order-item__name">
                            {{ $item->product_name }}
                            @if($item->variant_name)
                                <span class="muted"> · {{ $item->variant_name }}</span>
                            @endif
                        </div>
                        <div class="jtc-order-item__qty">× {{ (int) $item->quantity }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="text-align:center;margin-top:24px">
            <a href="{{ route('ecomx-anyniche.track') }}" class="jtc-form__link" wire:navigate>Track another order</a>
        </div>

        {{-- Confirm / cancel order modal --}}
        @auth
            <div x-data @click.self="$wire.closeConfirmModal()" class="modal" x-show="@js($confirmModal)" x-cloak>
                <div class="modal__box modal__box--md">
                    <div class="modal__head">
                        <p class="modal__title">Confirm Order</p>
                        <button class="modal__close" wire:click="closeConfirmModal" aria-label="Close">✕</button>
                    </div>

                    <p class="muted" style="font-size:12.5px;margin-bottom:18px">
                        Order #{{ $order->id }} · ৳{{ number_format($order->total_amount, 2) }}
                    </p>

                    @if(! $this->smsGatewayReady())
                        <div style="padding:14px;background:rgba(0,0,0,.03);border-radius:10px;margin-bottom:16px">
                            <p style="font-size:13px;font-weight:600;margin:0 0 6px">Our SMS gateway is temporarily unavailable</p>
                            <p class="muted" style="font-size:12.5px;margin:0 0 10px">Please call us to confirm or cancel your order.</p>
                            @if(\App\Support\ContactInfo::telHref())
                                <a href="{{ \App\Support\ContactInfo::telHref() }}" class="jtc-btn jtc-btn--primary jtc-btn--block">Call {{ $companyPhone }}</a>
                            @endif
                        </div>
                        <button type="button" wire:click="cancelOrder" wire:confirm="Are you sure you want to cancel this order?" class="jtc-btn jtc-btn--outline jtc-btn--block">
                            Cancel Order
                        </button>
                    @elseif(! $otpSent)
                        <p style="font-size:13px;margin-bottom:16px">We'll text a verification code to <strong>{{ auth()->user()->phone }}</strong> to confirm this order.</p>
                        @if($otpError && $otpError !== 'gateway_unavailable')
                            <p style="color:#c0392b;font-size:12.5px;margin-bottom:12px">{{ $otpError }}</p>
                        @endif
                        <button type="button" wire:click="sendOtp" class="jtc-btn jtc-btn--primary jtc-btn--block" style="margin-bottom:10px">Send OTP</button>
                        <button type="button" wire:click="cancelOrder" wire:confirm="Are you sure you want to cancel this order?" class="jtc-btn jtc-btn--outline jtc-btn--block">
                            Cancel Order
                        </button>
                    @else
                        <form wire:submit.prevent="verifyOtpAndConfirm">
                            <div class="field">
                                <label>Enter the 6-digit code</label>
                                <input wire:model="otpCode" inputmode="numeric" maxlength="6" placeholder="••••••" autofocus>
                            </div>
                            @if($otpError)
                                <p style="color:#c0392b;font-size:12.5px;margin-bottom:12px">{{ $otpError }}</p>
                            @endif
                            <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block" style="margin-bottom:10px">Verify &amp; Confirm Order</button>
                        </form>
                        <button type="button" wire:click="sendOtp" style="border:none;background:none;font-size:12px;color:var(--ac2);font-weight:600;cursor:pointer;display:block;width:100%;text-align:center;margin-bottom:10px">Resend code</button>
                        <button type="button" wire:click="cancelOrder" wire:confirm="Are you sure you want to cancel this order?" class="jtc-btn jtc-btn--outline jtc-btn--block">
                            Cancel Order
                        </button>
                    @endif
                </div>
            </div>
        @endauth
    @endif
</div>
