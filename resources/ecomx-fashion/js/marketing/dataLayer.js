export function pushMarketingEvent(payload) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(payload);
}
