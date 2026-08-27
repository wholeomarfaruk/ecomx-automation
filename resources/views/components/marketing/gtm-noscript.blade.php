@if (config('marketing.gtm.enabled') && config('marketing.gtm.container_id'))
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ config('marketing.gtm.container_id') }}"
            height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif
