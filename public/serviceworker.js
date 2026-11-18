/* Replaces the legacy cache-first worker at the same URL and scope.
 * Vite owns asset versioning. No HTML, Inertia, JSON or assets enter Cache Storage.
 * Keep this worker deployed for existing registrations and notification links.
 */
self.addEventListener('install', event => event.waitUntil(self.skipWaiting()));
self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(names => Promise.all(
        names.filter(name => name.startsWith('futtertrog_')).map(name => caches.delete(name))
    )).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin || event.request.mode !== 'navigate'
        || !/^\/(?:$|meals(?:\/\d+)?$|my\/(?:orders|balance)$|profile(?:\/[^/]+)?$|settings$)/.test(url.pathname)) return;
    event.respondWith(fetch(event.request).catch(() => new Response(`<!doctype html>
<html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Offline · Futtertrog</title><style>body{font:1.1rem/1.6 system-ui,sans-serif;max-width:40rem;margin:10vh auto;padding:1.5rem;color:#1c1917;background:#fafaf9}a{color:#115e59}</style>
<main><h1>Sie sind offline</h1><p>Diese Seite benötigt eine Verbindung. Bestellungen werden nicht gesendet oder vorgemerkt. Es liegt keine neue Serverbestätigung vor.</p><a href="${url.pathname}">Erneut versuchen</a></main></html>`,
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' } }
    )));
});

const WebPush = {
    init() {
        self.addEventListener("push", this.notificationPush.bind(this));
        self.addEventListener("notificationclick", this.notificationClick.bind(this));
        // self.addEventListener("notificationclose", this.notificationClose.bind(this))
    },

    /**
     * Handle notification push event.
     *
     * https://developer.mozilla.org/en-US/docs/Web/Events/push
     *
     * @param {NotificationEvent} event
     */
    notificationPush(event) {
        if (!(self.Notification && self.Notification.permission === "granted")) {
            return
        }

        // https://developer.mozilla.org/en-US/docs/Web/API/PushMessageData
        if (event.data) {
            event.waitUntil(
                this.sendNotification(event.data)
            );
        }
    },

    /**
     * Handle notification click event.
     *
     * https://developer.mozilla.org/en-US/docs/Web/Events/notificationclick
     *
     * @param {NotificationEvent} event
     */
    notificationClick(event) {
        let data = event.notification.data;

        self.clients.openWindow(data.url || "/");
    },

    /**
     * Handle notification close event (Chrome 50+, Firefox 55+).
     *
     * https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerGlobalScope/onnotificationclose
     *
     * @param {NotificationEvent} event
     */
    // notificationClose(event) {
    //     self.registration.pushManager.getSubscription().then(subscription => {
    //         if (subscription) {
    //             this.dismissNotification(event, subscription)
    //         }
    //     })
    // },

    /**
     * Send notification to the user.
     *
     * https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
     *
     * @param {PushMessageData|Object} data
     */
    sendNotification(data) {
        data = data.json();
        return self.registration.showNotification(data.title, data);
    },

    /**
     * Send request to server to dismiss a notification.
     *
     * @param  {NotificationEvent} event
     * @param  {String} subscription.endpoint
     * @return {Response}
     */
    // dismissNotification({notification}, {endpoint}) {
    //     if (!notification.data || !notification.data.id) {
    //         return
    //     }
    //
    //     const data = new FormData()
    //     data.append("endpoint", endpoint)
    //
    //     // Send a request to the server to mark the notification as read.
    //     fetch(`/notifications/${notification.data.id}/dismiss`, {
    //         method: "POST",
    //         body: data
    //     })
    // }
};

WebPush.init();
