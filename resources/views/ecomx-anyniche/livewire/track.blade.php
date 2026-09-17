<div class="jtc-track">
    <div class="jtc-track__head">
        <h1>Track your order</h1>
        <p>Enter your Order ID and phone number to see the latest status.</p>
    </div>

    @if($trackError)
        <p class="jtc-track__error">{{ $trackError }}</p>
    @endif

    <div class="jtc-track-card">
        <h2>① Track by Order ID</h2>
        <form wire:submit="track" class="jtc-track-form">
            <label>Order ID
                <input type="text" wire:model="orderId" inputmode="numeric" placeholder="e.g. 482" required>
            </label>
            <label>Phone number
                <input type="tel" wire:model="phone" placeholder="01XXXXXXXXX" required>
            </label>
            <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block" style="padding:14px">Track order</button>
        </form>
    </div>

    <div class="jtc-track__divider"><span></span>or<span></span></div>

    @auth
        <div class="jtc-track-card jtc-track-card--muted">
            <h2>Already signed in</h2>
            <p>See the full status and history of every order you've placed.</p>

            @if($myOrders->isEmpty())
                <p class="muted" style="font-size:0.86rem">You haven't placed any orders yet.</p>
                <a href="{{ route('ecomx-anyniche.shop') }}" class="jtc-btn jtc-btn--outline jtc-btn--block" style="padding:14px" wire:navigate>
                    Start shopping
                </a>
            @else
                <div style="display:flex;flex-direction:column;gap:2px;text-align:left">
                    @foreach($myOrders as $order)
                        <button type="button" wire:click="viewOrder({{ $order->id }})"
                            style="display:flex;justify-content:space-between;align-items:center;gap:12px;width:100%;text-align:left;padding:14px 4px;border:none;background:none;cursor:pointer;border-bottom:1px solid rgba(0,0,0,.06)">
                            <div>
                                <p style="font-size:0.88rem;font-weight:600;margin:0">Order #{{ $order->id }}</p>
                                <p class="muted" style="font-size:0.8rem;margin:2px 0 0">{{ $order->placed_at?->format('d M, Y') ?? $order->created_at->format('d M, Y') }} · {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</p>
                            </div>
                            <div style="text-align:right">
                                <p style="font-size:0.88rem;font-weight:700;margin:0">৳{{ number_format($order->total_amount, 2) }}</p>
                                <p class="muted" style="font-size:0.76rem;margin:2px 0 0">{{ $order->status->label() }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="jtc-track-card jtc-track-card--muted">
            <h2>Can't find your Order ID?</h2>
            <p>Sign in with your phone number to see all of your orders.</p>
            <button type="button" class="jtc-btn jtc-btn--outline jtc-btn--block" style="padding:14px" @click="$store.ui.authOpen = true">
                Sign in with phone (OTP)
            </button>
        </div>
    @endauth
</div>
