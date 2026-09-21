@php
    // Admin-editable, see App\Support\EcomxFashion\MenuRegistry — was three
    // hand-maintained ['Title', [[label, route], ...]] arrays.
    $footerColumns = [
        'Shop' => \App\Support\EcomxFashion\MenuRegistry::items('footer-shop'),
        'About' => \App\Support\EcomxFashion\MenuRegistry::items('footer-about'),
        'Help' => \App\Support\EcomxFashion\MenuRegistry::items('footer-help'),
    ];
@endphp
<footer class="footer">
    <div class="container">
        <div class="newsletter" x-data="{ email:'', sent:false }">
            <div style="max-width:440px">
                <p class="kicker" style="color:var(--ac3)">The Seldom Letter</p>
                <h3>Rarely in your inbox. Always worth opening.</h3>
                <p style="color:rgba(var(--sec-rgb),.65);font-size:13.5px;margin:10px 0 0">New collections, private previews and styling notes — twice a month, nothing more.</p>
            </div>
            <form @submit.prevent="sent=true" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;min-width:280px;max-width:480px">
                <input type="email" x-model="email" required placeholder="Email address" aria-label="Email address"
                    style="flex:1;min-width:200px;padding:14px 18px;border-radius:10px;border:1px solid rgba(var(--sec-rgb),.25);background:rgba(var(--sec-rgb),.06);color:var(--sec);font-size:13.5px;outline:none">
                <button type="submit" class="btn btn--accent" x-text="sent ? 'Subscribed ✓' : 'Subscribe'"></button>
            </form>
        </div>

        <div class="footer__cols">
            <div style="min-width:200px">
                <p class="brand__name" style="margin-bottom:12px">SELDOM <span style="color:var(--ac2)">FASHION</span></p>
                <p class="muted" style="font-size:12.5px;line-height:1.7;max-width:240px">Considered clothing, made rarely and made well. Designed in Dhaka, Bangladesh.</p>
            </div>
            @foreach ($footerColumns as $title => $links)
                <div style="display:flex;flex-direction:column;gap:11px">
                    <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">{{ $title }}</span>
                    @foreach ($links as $link)
                        <a href="{{ $link['url'] }}"{!! $link['new_tab'] ? ' target="_blank" rel="noopener"' : '' !!} style="font-size:13px;color:rgba(var(--pri-rgb),.72)">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="footer__bottom">
            <span class="muted" style="font-size:12px">© {{ date('Y') }} Seldom Fashion · {{ config('ecomx-fashion.domain') }}</span>
            <div style="display:flex;gap:8px" aria-label="Payment methods">
                @foreach(['BKASH','NAGAD','VISA','MC','COD'] as $p)<span class="pay-chip">{{ $p }}</span>@endforeach
            </div>
        </div>
    </div>
</footer>
