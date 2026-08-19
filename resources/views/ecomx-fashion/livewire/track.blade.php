<div class="track">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);margin-bottom:12px"><a href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <span style="color:var(--pri)">Track order</span></nav>
    <h1 style="font-size:clamp(30px,3.6vw,44px);margin-bottom:6px">Track your order</h1>
    <p class="muted" style="font-size:13.5px;margin-bottom:24px">Enter your order ID and phone number to see live status.</p>

    <div class="track__card">
        <form wire:submit="track">
            <div class="field"><label>Order ID</label><input wire:model="orderId" placeholder="SF-100000" required></div>
            <div class="field"><label>Phone number</label><input wire:model="phone" inputmode="tel" placeholder="01XXXXXXXXX" required></div>
            <button type="submit" class="btn btn--primary btn--block">{{ $tracked ? 'Refresh status' : 'Track order' }}</button>
        </form>

        @if($tracked)
            <div style="margin-top:28px">
                <p style="font-size:12.5px;font-weight:600;margin-bottom:18px">Order {{ $orderId ?: 'SF-100000' }} · estimated by {{ now()->addDays(2)->format('D, j M') }}</p>
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
        <button class="btn btn--outline btn--pill btn--block" x-data @click="$store.ui.authOpen=true">Sign in with phone (OTP)</button>
    </div>
</div>
