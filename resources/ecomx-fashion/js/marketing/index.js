import { pushMarketingEvent } from './dataLayer';
import { hasDispatched, markDispatched } from './eventQueue';

/**
 * Reads server-rendered pending events off window.__marketingEvents (set by
 * the events.blade.php component, one array entry per canonical event
 * recorded during this request) and pushes each into dataLayer — skipping
 * any event_id already dispatched earlier in this browser session.
 */
export function flushPendingMarketingEvents() {
    const events = Array.isArray(window.__marketingEvents) ? window.__marketingEvents : [];

    events.forEach((payload) => {
        const eventId = payload?.marketing?.event_id;

        if (hasDispatched(eventId)) {
            return;
        }

        pushMarketingEvent(payload);
        markDispatched(eventId);
    });

    window.__marketingEvents = [];
}

export { pushMarketingEvent } from './dataLayer';
