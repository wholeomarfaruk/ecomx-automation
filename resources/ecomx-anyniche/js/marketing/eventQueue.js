const STORAGE_PREFIX = 'marketing_event_';

/**
 * Browser-side dedup only — a convenience against page reloads/double
 * mounts within one tab. Not the source of truth: the database's
 * marketing_events.event_id unique constraint is what actually prevents a
 * duplicate canonical event from existing. This just avoids firing the same
 * event_id into dataLayer twice from the same browser session.
 */
export function hasDispatched(eventId) {
    if (!eventId) {
        return false;
    }

    try {
        return sessionStorage.getItem(STORAGE_PREFIX + eventId) === '1';
    } catch {
        return false;
    }
}

export function markDispatched(eventId) {
    if (!eventId) {
        return;
    }

    try {
        sessionStorage.setItem(STORAGE_PREFIX + eventId, '1');
    } catch {
        // sessionStorage unavailable (private mode, disabled storage) —
        // dedup just doesn't apply this visit, not fatal.
    }
}
