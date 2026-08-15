self.addEventListener('push', function (event) {
    if (!event.data) {
        return;
    }

    let payload = {};
    try {
        payload = event.data.json();
    } catch (e) {
        payload = { title: 'Notification', body: event.data.text() };
    }

    event.waitUntil(
        self.registration.showNotification(payload.title || 'Notification', {
            body: payload.body || '',
            icon: payload.icon || '/favicon.ico',
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(clients.openWindow('/'));
});
