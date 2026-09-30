export function pushMarketingEvent(payload) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    // GTM merges every push into one data model — objects key by key, arrays
    // index by index — so the previous event's leftovers (a second checkout
    // item, a purchase's order_id in meta.custom_data) would leak into this
    // one. Clearing the blocks first is Google's documented fix for
    // ecommerce, applied to every block these payloads carry.
    window.dataLayer.push({ ecommerce: null, marketing: null, meta: null, page: null, attribution: null });

    window.dataLayer.push(payload);
}
