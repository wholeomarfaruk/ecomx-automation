<script>
    (function () {
        var COOKIE = 'device_fingerprint';
        var TEN_YEARS = 60 * 60 * 24 * 365 * 10;

        function readCookie(name) {
            var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]+)'));
            return match ? decodeURIComponent(match[1]) : null;
        }

        function writeCookie(name, value) {
            document.cookie = name + '=' + encodeURIComponent(value) + '; max-age=' + TEN_YEARS + '; path=/; SameSite=Lax';
        }

        // Self-healing: cookie and localStorage should always agree. If one
        // was cleared (browser setting, private mode, manual clear) but the
        // other survives, restore it so the visitor keeps the same identity
        // instead of silently starting a new "device" server-side.
        var cookieValue = readCookie(COOKIE);
        var storedValue = null;
        try { storedValue = localStorage.getItem(COOKIE); } catch (e) {}

        if (cookieValue && cookieValue !== storedValue) {
            try { localStorage.setItem(COOKIE, cookieValue); } catch (e) {}
        } else if (!cookieValue && storedValue) {
            writeCookie(COOKIE, storedValue);
        }

        // Client-side signals DeviceTracker can't read from headers (screen
        // size/density, timezone) — sent at most once/day per device so it
        // never adds meaningful weight to normal page loads.
        var SIGNAL_KEY = 'device_signal_sent_at';
        var ONE_DAY_MS = 24 * 60 * 60 * 1000;
        var lastSent = 0;
        try { lastSent = parseInt(localStorage.getItem(SIGNAL_KEY) || '0', 10); } catch (e) {}

        if (Date.now() - lastSent > ONE_DAY_MS) {
            var payload = {
                screen_resolution: window.screen ? (window.screen.width + 'x' + window.screen.height) : null,
                screen_density: window.devicePixelRatio ? String(window.devicePixelRatio) : null,
                timezone: Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : null,
            };

            var csrf = document.querySelector('meta[name="csrf-token"]');

            fetch('{{ route('device-signal') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
                keepalive: true,
            }).then(function () {
                try { localStorage.setItem(SIGNAL_KEY, String(Date.now())); } catch (e) {}
            }).catch(function () {});
        }
    })();
</script>
