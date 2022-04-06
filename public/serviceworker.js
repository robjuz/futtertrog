const version = "2022_04_06_1";

let staticCacheName = "futtertrog_" + version;
const filesToCache = [
    "/offline",
    "/css/app.css",
    "/js/app.js",
    "/images/icons/icon-72x72.png",
    "/images/icons/icon-96x96.png",
    "/images/icons/icon-128x128.png",
    "/images/icons/icon-144x144.png",
    "/images/icons/icon-152x152.png",
    "/images/icons/icon-192x192.png",
    "/images/icons/icon-384x384.png",
    "/images/icons/icon-512x512.png",
    "/images/icons/splash-640x1136.png",
    "/images/icons/splash-750x1334.png",
    "/images/icons/splash-1242x2208.png",
    "/images/icons/splash-1125x2436.png",
    "/images/icons/splash-828x1792.png",
    "/images/icons/splash-1242x2688.png",
    "/images/icons/splash-1536x2048.png",
    "/images/icons/splash-1668x2224.png",
    "/images/icons/splash-1668x2388.png",
    "/images/icons/splash-2048x2732.png",
    "/images/landing-page.jpg",
    "/images/background.jpg",
    "/fonts/caveat-v7-latin-ext-regular.woff",
    "/fonts/caveat-v7-latin-ext-regular.woff2",
    "/fonts/livvic-v3-latin-ext-regular.woff",
    "/fonts/livvic-v3-latin-ext-regular.woff2",
];

// Cache on install
self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(staticCacheName)
            .then((cache) => cache.addAll(filesToCache))
            .then(() => self.skipWaiting())
            .catch((error) => console.error(error))
    )
});

// Clear cache on activate
self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => (cacheName.startsWith("futtertrog_")))
                        .filter((cacheName) => (cacheName !== staticCacheName))
                        .map((cacheName) => caches.delete(cacheName))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Serve from Cache
self.addEventListener("fetch", event => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => response || fetch(event.request))
            .catch(() => caches.match("offline"))
    );
});

addEventListener("message", messageEvent => {
    if (messageEvent.data === "skipWaiting") return skipWaiting();
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
