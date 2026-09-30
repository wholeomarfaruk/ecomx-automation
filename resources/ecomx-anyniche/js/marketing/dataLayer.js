export function pushMarketingEvent(payload) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    // GTM merges every push into one data model, arrays index by index — a
    // purchase with 1 item right after a begin_checkout with 2 would still
    // read as 2 items. Clearing ecommerce first is Google's documented fix.
    if (payload.ecommerce) {
        window.dataLayer.push({ ecommerce: null });
    }

    window.dataLayer.push(payload);
}
