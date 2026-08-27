<div class="track">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);margin-bottom:12px"><a href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <span style="color:var(--pri)">{{ auth()->check() ? 'My Orders' : 'Track order' }}</span></nav>

    @auth
        <h1 style="font-size:clamp(30px,3.6vw,44px);margin-bottom:6px">My Orders</h1>
        <p class="muted" style="font-size:13.5px;margin-bottom:24px">Every order you've placed, and its live status.</p>

        @if($tracked && $trackedOrder)
            {{-- Single order detail --}}
            <div class="track__card">
                <button type="button" wire:click="backToList" style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--ac2);font-weight:600;border:none;background:none;cursor:pointer;padding:0;margin-bottom:18px">
                    ← Back to all orders
                </button>

                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:20px">
                    <div>
                        <p style="font-size:16px;font-weight:700;margin:0">Order #{{ $trackedOrder->id }}</p>
                        <p class="muted" style="font-size:12.5px;margin:2px 0 0">Placed {{ $trackedOrder->placed_at?->format('d M, Y · h:i A') ?? $trackedOrder->created_at->format('d M, Y · h:i A') }}</p>
                    </div>
                    <p style="font-size:16px;font-weight:700;margin:0">৳{{ number_format($trackedOrder->total_amount, 2) }}</p>
                </div>

                <div style="display:flex;flex-direction:column;gap:0;margin-bottom:24px">
                    @foreach($steps as $i => $st)
                        <div style="display:flex;gap:14px">
                            <div style="display:flex;flex-direction:column;align-items:center">
                                <span style="width:26px;height:26px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:12px;background:{{ $st['done'] ? 'var(--pri)' : 'transparent' }};color:{{ $st['done'] ? 'var(--sec)' : 'rgba(var(--pri-rgb),.4)' }};border:1.5px solid {{ $st['done'] ? 'var(--pri)' : 'rgba(var(--pri-rgb),.2)' }}">{{ $st['done'] ? '✓' : $i+1 }}</span>
                                @if(!$loop->last)<span style="width:1.5px;flex:1;min-height:26px;background:{{ $st['done'] ? 'var(--pri)' : 'rgba(var(--pri-rgb),.15)' }}"></span>@endif
                            </div>
                            <div style="padding-bottom:18px">
                                <p style="font-size:13.5px;font-weight:600">{{ $st['label'] }}</p>
                                <p class="muted" style="font-size:12px">{{ $st['sub'] }}</p>
                                @if($st['label'] === 'Pending' && $trackedOrder->status->value === 'pending')
                                    <button type="button" wire:click="openConfirmModal" class="btn btn--primary btn--pill" style="margin-top:10px;padding:8px 18px;font-size:12.5px">
                                        Confirm Order
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($trackedOrder->courier_tracking_number)
                    <div style="padding:12px 14px;background:rgba(var(--pri-rgb),.03);border-radius:10px;margin-bottom:20px">
                        <p style="font-size:12px;color:rgba(var(--pri-rgb),.5);margin:0 0 2px">Courier tracking</p>
                        <p style="font-size:13px;font-weight:600;margin:0">{{ $trackedOrder->courier_provider ?? 'Courier' }} · {{ $trackedOrder->courier_tracking_number }}</p>
                    </div>
                @endif

                <div style="border-top:1px solid rgba(var(--pri-rgb),.08);padding-top:16px">
                    <p style="font-size:13px;font-weight:600;margin-bottom:10px">Items</p>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        @foreach($trackedOrder->items as $item)
                            <div style="display:flex;justify-content:space-between;font-size:13px">
                                <span>
                                    @if($item->product)
                                        <a href="{{ route('ecomx-fashion.product', $item->product->slug) }}" style="color:var(--pri);text-decoration:underline" wire:navigate>{{ $item->product_name }}</a>
                                    @else
                                        {{ $item->product_name }}
                                    @endif
                                    {{ $item->variant_name ? ' · ' . $item->variant_name : '' }} <span class="muted">× {{ (int) $item->quantity }}</span>
                                </span>
                                <span style="font-weight:600">৳{{ number_format($item->total_amount, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($trackedOrder->shippingAddress)
                    <div style="border-top:1px solid rgba(var(--pri-rgb),.08);padding-top:16px;margin-top:16px">
                        <p style="font-size:13px;font-weight:600;margin-bottom:6px">Delivery address</p>
                        <p style="font-size:12.5px;color:rgba(var(--pri-rgb),.7);margin:0">{{ $trackedOrder->shippingAddress->name }} · {{ $trackedOrder->shippingAddress->phone }}</p>
                        <p style="font-size:12.5px;color:rgba(var(--pri-rgb),.7);margin:2px 0 0">{{ $trackedOrder->shippingAddress->full_address }}</p>
                    </div>
                @endif
            </div>
        @else
            {{-- Order list --}}
            <div class="track__card">
                @if($myOrders->isEmpty())
                    <p class="muted" style="font-size:13.5px;text-align:center;padding:20px 0">You haven't placed any orders yet.</p>
                    <a href="{{ route('ecomx-fashion.shop') }}" class="btn btn--primary btn--pill" style="display:block;text-align:center;margin-top:10px" wire:navigate>Start shopping</a>
                @else
                    <div style="display:flex;flex-direction:column;gap:2px">
                        @foreach($myOrders as $order)
                            <button type="button" wire:click="viewOrder({{ $order->id }})"
                                style="display:flex;justify-content:space-between;align-items:center;gap:12px;width:100%;text-align:left;padding:14px 4px;border:none;background:none;cursor:pointer;border-bottom:1px solid rgba(var(--pri-rgb),.06)">
                                <div>
                                    <p style="font-size:13.5px;font-weight:600;margin:0">Order #{{ $order->id }}</p>
                                    <p class="muted" style="font-size:12px;margin:2px 0 0">{{ $order->placed_at?->format('d M, Y') ?? $order->created_at->format('d M, Y') }} · {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</p>
                                </div>
                                <div style="text-align:right">
                                    <p style="font-size:13.5px;font-weight:700;margin:0">৳{{ number_format($order->total_amount, 2) }}</p>
                                    <p class="muted" style="font-size:11.5px;margin:2px 0 0">{{ $order->status->label() }}</p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @else
        <h1 style="font-size:clamp(30px,3.6vw,44px);margin-bottom:6px">Track your order</h1>
        <p class="muted" style="font-size:13.5px;margin-bottom:24px">Enter your order ID and phone number to see live status.</p>

        <div class="track__card">
            <form wire:submit="track">
                <div class="field"><label>Order ID</label><input wire:model="orderId" placeholder="e.g. 1024" required></div>
                <div class="field"><label>Phone number</label><input wire:model="phone" inputmode="tel" placeholder="01XXXXXXXXX" required></div>
                @if($trackError)
                    <p style="color:#c0392b;font-size:12.5px;margin-bottom:12px">{{ $trackError }}</p>
                @endif
                <button type="submit" class="btn btn--primary btn--block">{{ $tracked ? 'Refresh status' : 'Track order' }}</button>
            </form>

            @if($tracked && $trackedOrder)
                <div style="margin-top:28px">
                    <p style="font-size:12.5px;font-weight:600;margin-bottom:18px">Order #{{ $trackedOrder->id }} · ৳{{ number_format($trackedOrder->total_amount, 2) }}</p>
                    <div style="display:flex;flex-direction:column;gap:0">
                        @foreach($steps as $i => $st)
                            <div style="display:flex;gap:14px">
                                <div style="display:flex;flex-direction:column;align-items:center">
                                    <span style="width:26px;height:26px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:12px;background:{{ $st['done'] ? 'var(--pri)' : 'transparent' }};color:{{ $st['done'] ? 'var(--sec)' : 'rgba(var(--pri-rgb),.4)' }};border:1.5px solid {{ $st['done'] ? 'var(--pri)' : 'rgba(var(--pri-rgb),.2)' }}">{{ $st['done'] ? '✓' : $i+1 }}</span>
                                    @if(!$loop->last)<span style="width:1.5px;flex:1;min-height:26px;background:{{ $st['done'] ? 'var(--pri)' : 'rgba(var(--pri-rgb),.15)' }}"></span>@endif
                                </div>
                                <div style="padding-bottom:18px"><p style="font-size:13.5px;font-weight:600">{{ $st['label'] }}</p><p class="muted" style="font-size:12px">{{ $st['sub'] }}</p></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="track__divider"><span></span><span class="muted" style="font-size:12px">or</span><span></span></div>

        <div class="track__otp">
            <p style="font-size:15px;font-weight:600;margin-bottom:6px">Sign in to see all your orders</p>
            <p class="muted" style="font-size:12.5px;margin-bottom:18px">Track everything in one place with a Seldom account.</p>
            <button class="btn btn--outline btn--pill btn--block" x-data @click="$store.ui.authOpen=true">Sign in</button>
        </div>
    @endauth

    {{-- Confirm / cancel order modal --}}
    @auth
        <div x-data @click.self="$wire.closeConfirmModal()" class="modal" x-show="@js($confirmModal)" x-cloak>
            <div class="modal__box modal__box--md">
                <div class="modal__head">
                    <p class="modal__title">Confirm Order</p>
                    <button class="modal__close" wire:click="closeConfirmModal" aria-label="Close">✕</button>
                </div>

                @if($trackedOrder)
                    <p class="muted" style="font-size:12.5px;margin-bottom:18px">
                        Order #{{ $trackedOrder->id }} · ৳{{ number_format($trackedOrder->total_amount, 2) }}
                    </p>

                    @if(! $this->smsGatewayReady())
                        <div style="padding:14px;background:rgba(var(--pri-rgb),.03);border-radius:10px;margin-bottom:16px">
                            <p style="font-size:13px;font-weight:600;margin:0 0 6px">Our SMS gateway is temporarily unavailable</p>
                            <p class="muted" style="font-size:12.5px;margin:0 0 10px">Please call us to confirm or cancel your order.</p>
                            <a href="tel:{{ $companyPhone }}" class="btn btn--primary btn--pill btn--block">Call {{ $companyPhone }}</a>
                        </div>
                        <button type="button" wire:click="cancelOrder" wire:confirm="Are you sure you want to cancel this order?" class="btn btn--outline btn--pill btn--block">
                            Cancel Order
                        </button>
                    @elseif(! $otpSent)
                        <p style="font-size:13px;margin-bottom:16px">We'll text a verification code to <strong>{{ auth()->user()->phone }}</strong> to confirm this order.</p>
                        @if($otpError && $otpError !== 'gateway_unavailable')
                            <p style="color:#c0392b;font-size:12.5px;margin-bottom:12px">{{ $otpError }}</p>
                        @endif
                        <button type="button" wire:click="sendOtp" class="btn btn--primary btn--pill btn--block" style="margin-bottom:10px">Send OTP</button>
                        <button type="button" wire:click="cancelOrder" wire:confirm="Are you sure you want to cancel this order?" class="btn btn--outline btn--pill btn--block">
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
                            <button type="submit" class="btn btn--primary btn--pill btn--block" style="margin-bottom:10px">Verify &amp; Confirm Order</button>
                        </form>
                        <button type="button" wire:click="sendOtp" style="border:none;background:none;font-size:12px;color:var(--ac2);font-weight:600;cursor:pointer;display:block;width:100%;text-align:center;margin-bottom:10px">Resend code</button>
                        <button type="button" wire:click="cancelOrder" wire:confirm="Are you sure you want to cancel this order?" class="btn btn--outline btn--pill btn--block">
                            Cancel Order
                        </button>
                    @endif
                @endif
            </div>
        </div>
    @endauth
</div>
