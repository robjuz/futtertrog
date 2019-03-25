const version = '20190325_4';

let staticCacheName = "futtertrog_" + version;
const filesToCache = [
    '/offline',
    '/css/dark.css',
    '/css/light.css',
    '/css/flatpickr.css',
    '/js/app.js',
    '/images/icons/icon-72x72.png',
    '/images/icons/icon-96x96.png',
    '/images/icons/icon-128x128.png',
    '/images/icons/icon-144x144.png',
    '/images/icons/icon-152x152.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-384x384.png',
    '/images/icons/icon-512x512.png',
];

// Cache on install
self.addEventListener("install", event => {
    event.waitUntil(
        caches.open(staticCacheName)
            .then(cache => cache.addAll(filesToCache))
            .catch(error => console.error(error))
    )
});

// Clear cache on activate
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => (cacheName.startsWith("futtertrog_")))
                    .filter(cacheName => (cacheName !== staticCacheName))
                    .map(cacheName => caches.delete(cacheName))
            );
        })
    );
});

// Serve from Cache
self.addEventListener("fetch", event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => response || fetch(event.request))
            .catch(() => caches.match('offline'))
    );
});

const WebPush = {
    init() {
        self.addEventListener('push', this.notificationPush.bind(this));
        self.addEventListener('notificationclick', this.notificationClick.bind(this));
        // self.addEventListener('notificationclose', this.notificationClose.bind(this))
    },

    /**
     * Handle notification push event.
     *
     * https://developer.mozilla.org/en-US/docs/Web/Events/push
     *
     * @param {NotificationEvent} event
     */
    notificationPush(event) {
        if (!(self.Notification && self.Notification.permission === 'granted')) {
            return
        }

        // https://developer.mozilla.org/en-US/docs/Web/API/PushMessageData
        if (event.data) {
            event.waitUntil(
                this.sendNotification(event.data.json())
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

        self.clients.openWindow(data.url || '/');
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
    //     data.append('endpoint', endpoint)
    //
    //     // Send a request to the server to mark the notification as read.
    //     fetch(`/notifications/${notification.data.id}/dismiss`, {
    //         method: 'POST',
    //         body: data
    //     })
    // }
};

WebPush.init();
