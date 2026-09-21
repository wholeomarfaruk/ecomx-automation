@php
    $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';
    $footerDescription = \App\Models\Setting::get('site_tagline', null)
        ?: 'Quality products, competitive prices, and fast delivery — placeholder copy, edit in Site Settings.';

    $footerLogoUrl = null;
    try {
        if ($logoId = \App\Models\Setting::get('site_logo_symbol')) {
            $footerLogoUrl = file_path($logoId);
        }
    } catch (\Throwable $e) {
        $footerLogoUrl = null;
    }
    $footerLogoUrl ??= asset('logo/ecomx-square-logo.png');

    // Admin-editable, see App\Support\EcomxAnyniche\MenuRegistry — was two hardcoded <ul> lists.
    $footerLinks = \App\Support\EcomxAnyniche\MenuRegistry::items('footer-links');
    $footerLegal = \App\Support\EcomxAnyniche\MenuRegistry::items('footer-legal');
@endphp
<footer class="jtc-footer">
    <div class="jtc-footer__inner">
        <div class="jtc-footer__cols">
            <div>
                <a href="{{ route('ecomx-anyniche.home') }}" class="jtc-footer__brand">
                    <span class="jtc-footer__mark"><img src="{{ $footerLogoUrl }}" alt="{{ $siteName }}"></span>
                    <span>
                        <span class="jtc-footer__name">{{ $siteName }}</span>
                    </span>
                </a>
                <div class="jtc-footer__blurb">
                    {{ $footerDescription }}
                </div>
            </div>
            <div>
                <h4>Useful links</h4>
                <ul>
                    @foreach ($footerLinks as $link)
                        <li><a href="{{ $link['url'] }}"{!! $link['new_tab'] ? ' target="_blank" rel="noopener"' : '' !!}>{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4>Legal</h4>
                <ul>
                    @foreach ($footerLegal as $link)
                        <li><a href="{{ $link['url'] }}"{!! $link['new_tab'] ? ' target="_blank" rel="noopener"' : '' !!}>{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4>Stay in the loop</h4>
                <p class="jtc-footer__blurb" style="max-width:none">Get early access to deals and new arrivals.</p>
                <form class="jtc-footer__news" @submit.prevent>
                    <input type="email" placeholder="Your email" aria-label="Email">
                    <button aria-label="Subscribe">
                        <x-anyniche::icons.arrow />
                    </button>
                </form>

                <div class="jtc-footer__pays">
                    <span class="jtc-footer__pay">VISA</span>
                    <span class="jtc-footer__pay">Mastercard</span>
                    <span class="jtc-footer__pay">bKash</span>
                    <span class="jtc-footer__pay">Nagad</span>
                    <span class="jtc-footer__pay">COD</span>
                </div>
            </div>
        </div>
        <div class="jtc-footer__bottom">
            <span>© {{ date('Y') }} {{ $siteName }}. All rights reserved.</span>
        </div>
    </div>
</footer>
