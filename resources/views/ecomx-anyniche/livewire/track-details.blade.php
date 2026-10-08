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

            {{-- Payment status — see Order::paymentSummary() --}}
            @php
                $pay = $order->paymentSummary();
                $payBadge = [
                    'paid'      => ['Paid', '#1e7e4f', 'rgba(30,126,79,.1)'],
                    'partial'   => ['Partially paid', '#b85c00', 'rgba(184,92,0,.1)'],
                    'verifying' => ['Verifying payment', '#9a6b00', 'rgba(154,107,0,.1)'],
                    'unpaid'    => ['Unpaid', '#6b7a73', 'rgba(0,0,0,.05)'],
                ][$pay['state']];
            @endphp
            <div style="border:1px solid rgba(0,0,0,.08);border-radius:10px;padding:12px 14px;margin-top:16px">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px">
                    <div class="jtc-track-summary__label" style="margin:0">Payment</div>
                    <span style="font-size:0.74rem;font-weight:600;padding:3px 10px;border-radius:999px;color:{{ $payBadge[1] }};background:{{ $payBadge[2] }}">{{ $payBadge[0] }}</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;font-size:0.84rem">
                    <div style="display:flex;justify-content:space-between"><span style="color:#6b7a73">Total</span><span>৳{{ number_format($pay['total'], 2) }}</span></div>
                    <div style="display:flex;justify-content:space-between"><span style="color:#6b7a73">Paid</span><span style="color:#1e7e4f">৳{{ number_format($pay['paid'], 2) }}</span></div>
                    <div style="display:flex;justify-content:space-between;font-weight:700"><span>Due</span><span>৳{{ number_format($pay['due'], 2) }}</span></div>
                </div>

                @if($isOwner)
                    @if($order->payments->isNotEmpty())
                        <div style="border-top:1px solid rgba(0,0,0,.06);margin-top:12px;padding-top:10px;display:flex;flex-direction:column;gap:10px">
                            @foreach($order->payments->sortBy('id') as $p)
                                @php $isRefund = $p->type === \App\Enums\Sales\OrderPaymentType::REFUND; @endphp
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;font-size:0.84rem">
                                    <div>
                                        <div style="font-weight:600">{{ $p->payment_method?->label() ?? 'Payment' }}{{ $isRefund ? ' · Refund' : '' }}</div>
                                        @if($p->transaction_id)
                                            <div style="font-size:0.76rem;color:#6b7a73;margin-top:2px">TrxID <span style="font-family:monospace">{{ $p->transaction_id }}</span></div>
                                        @endif
                                        <div style="font-size:0.76rem;color:#6b7a73;margin-top:2px">{{ ($p->paid_at ?? $p->created_at)->format('d M Y, h:i A') }}</div>
                                    </div>
                                    <div style="text-align:right;white-space:nowrap">
                                        <div style="font-weight:600;{{ $isRefund ? 'color:#a93226' : '' }}">{{ $isRefund ? '−' : '' }}৳{{ number_format($p->amount, 2) }}</div>
                                        <div style="font-size:0.72rem;font-weight:600;margin-top:2px;color:{{ ['paid' => '#1e7e4f', 'pending' => '#9a6b00', 'failed' => '#a93226', 'refunded' => '#a93226'][$p->status->value] ?? '#6b7a73' }}">
                                            {{ $p->status === \App\Enums\Sales\PaymentStatus::PENDING ? 'Verifying' : ($p->status === \App\Enums\Sales\PaymentStatus::FAILED ? 'Not verified' : $p->status->label()) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($pay['pending']->isNotEmpty())
                        <div style="margin-top:10px;padding:9px 12px;border-radius:8px;background:rgba(154,107,0,.08);font-size:0.78rem;color:#7a5500">
                            We've received your payment and are verifying it. Your due will update once it's confirmed.
                        </div>
                    @endif
                    @if($pay['rejected']->isNotEmpty())
                        <div style="margin-top:10px;padding:9px 12px;border-radius:8px;background:rgba(192,57,43,.07);font-size:0.78rem;color:#a93226">
                            We couldn't verify a payment on this order. Please contact us.
                        </div>
                    @endif
                @else
                    @foreach($pay['pending'] as $p)
                        <div style="margin-top:10px;padding:9px 12px;border-radius:8px;background:rgba(154,107,0,.08);font-size:0.78rem;color:#7a5500">
                            <strong>{{ $p->payment_method?->label() ?? 'Payment' }} ৳{{ number_format($p->amount, 2) }}</strong>
                            @if($p->transaction_id) · TrxID {{ $p->transaction_id }} @endif
                            <br>Received — we're verifying it. Your due will update once it's confirmed.
                        </div>
                    @endforeach

                    @foreach($pay['rejected'] as $p)
                        <div style="margin-top:10px;padding:9px 12px;border-radius:8px;background:rgba(192,57,43,.07);font-size:0.78rem;color:#a93226">
                            <strong>{{ $p->payment_method?->label() ?? 'Payment' }} ৳{{ number_format($p->amount, 2) }}</strong>
                            @if($p->transaction_id) · TrxID {{ $p->transaction_id }} @endif
                            <br>We couldn't verify this payment. Please contact us.
                        </div>
                    @endforeach
                @endif
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
                            <img src="{{ $item->variant?->display_image ?? $item->product?->featured_image ?? '' }}" alt="{{ $item->product_name }}" loading="lazy">
                        </div>
                        <div class="jtc-order-item__name">
                            @if($item->product?->url)
                                <a href="{{ $item->product->url }}" style="color:inherit;text-decoration:underline">{{ $item->product_name }}</a>
                            @else
                                {{ $item->product_name }}
                            @endif
                            @if($item->variant_name)
                                <span class="muted"> · {{ $item->variant_name }}</span>
                            @endif
                        </div>
                        <div class="jtc-order-item__qty">
                            ৳{{ number_format($item->unit_price, 2) }} × {{ (int) $item->quantity }}
                            <div style="font-weight:600;color:inherit">৳{{ number_format($item->total_amount, 2) }}</div>
                        </div>
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
