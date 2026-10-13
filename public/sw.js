/*
 * Warrgyizmorsch HRM — push notification service worker.
 *
 * Pushes from the server are empty "wake-ups". When one arrives we fetch the real messages
 * from /push/pending with the user's session and show them as system notifications, so they
 * appear even when no HRM tab is open. The operating system plays its notification sound.
 */
const PENDING_URL = new URL('push/pending', self.registration.scope).href;
const ICON_URL = new URL('favicon.ico', self.registration.scope).href;
const HOME_URL = new URL('dashboard', self.registration.scope).href;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    event.waitUntil(showPending());
});

async function showPending() {
    let messages = [];
    try {
        const res = await fetch(PENDING_URL, {
            credentials: 'include',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const type = res.headers.get('content-type') || '';
        if (res.ok && type.includes('application/json')) {
            messages = (await res.json()).messages || [];
        }
    } catch (e) {
        // offline or logged out — fall through to the generic notice
    }

    // Tell open HRM tabs to refresh their bell right away.
    const tabs = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    tabs.forEach((tab) => tab.postMessage({ type: 'hrm-push' }));

    if (!messages.length) {
        // A push must always show something (browser rule). Usually this means the session
        // expired, or another device already picked the message up.
        const existing = await self.registration.getNotifications({ tag: 'hrm-generic' });
        if (existing.length) return;
        return self.registration.showNotification('Warrgyizmorsch HRM', {
            body: 'You have new notifications. Open the HRM to see them.',
            icon: ICON_URL,
            tag: 'hrm-generic',
            data: { url: HOME_URL },
        });
    }

    const shown = messages.slice(0, 4).map((m) =>
        self.registration.showNotification(m.title, {
            body: m.body || '',
            icon: ICON_URL,
            badge: ICON_URL,
            tag: m.tag || ('hrm-msg-' + m.id),
            renotify: true,
            silent: false,
            requireInteraction: /action required|approval/i.test(m.title), // approvals stay until clicked
            data: { url: m.url || HOME_URL },
        })
    );
    if (messages.length > 4) {
        shown.push(self.registration.showNotification((messages.length - 4) + ' more HRM notifications', {
            icon: ICON_URL, tag: 'hrm-more', data: { url: HOME_URL },
        }));
    }
    return Promise.all(shown);
}

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || HOME_URL;

    event.waitUntil((async () => {
        const tabs = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        // Re-use an open HRM tab if there is one.
        for (const tab of tabs) {
            if (tab.url.startsWith(self.registration.scope) && 'focus' in tab) {
                await tab.focus();
                if ('navigate' in tab) {
                    return tab.navigate(target);
                }
                return;
            }
        }
        return self.clients.openWindow(target);
    })());
});

// The browser replaced the subscription (keys rotated, expired): the page re-registers on next visit.
self.addEventListener('pushsubscriptionchange', () => {});
