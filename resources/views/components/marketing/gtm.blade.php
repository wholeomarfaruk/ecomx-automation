@if (config('marketing.gtm.enabled') && config('marketing.gtm.container_id'))
    {{-- GTM only. No Meta Pixel / GA4 / TikTok script here — those load as
         tags inside the GTM container, mapped from the universal dataLayer
         payload this page pushes (see events.blade.php). --}}
    <script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', @js(config('marketing.gtm.container_id')));
    </script>
@endif
