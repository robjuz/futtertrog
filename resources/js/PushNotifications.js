/* eslint-env browser, es6 */
'use strict';

const headers = {
    'X-CSRF-TOKEN': window.Futtertrog.csrf,
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json'
};

function urlB64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function updateSubscriptionOnServer(subscription) {

    const key = subscription.getKey('p256dh');
    const token = subscription.getKey('auth');

    const data = {
        endpoint: subscription.endpoint,
        key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
        token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null
    };

    return fetch('/subscriptions', {
        method: 'POST',
        credentials: 'same-origin',
        redirect: 'follow',
        headers,
        body: JSON.stringify(data)
    });
}

function removeSubscriptionOnSever(subscription) {
    return fetch('/subscriptions', {
        method: 'DELETE',
        credentials: 'same-origin',
        redirect: 'follow',
        headers,
        body: JSON.stringify({endpoint: subscription.endpoint})
    });
}

function subscribeUser(swRegistration) {
    swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlB64ToUint8Array(window.Futtertrog.vapidPublicKey)
    })
        .then(subscription => updateSubscriptionOnServer(subscription))
        .catch(err => console.log('Failed to subscribe the user: ', err));
}

function unsubscribeUser(swRegistration) {
    swRegistration.pushManager.getSubscription()
        .then((subscription) => {
            if (subscription) {
                subscription.unsubscribe().then(() => removeSubscriptionOnSever(subscription));
            }
        })
        .catch(err => console.log('Error unsubscribing', err));
}

function askPermission() {
    return new Promise((resolve, reject) => {
        if (window.Futtertrog.user === null) {
            return reject('No authenticated user');
        }

        Notification.requestPermission().then(permissionResult => {
            permissionResult === 'granted' ? resolve() : reject();
        });
    });
}

export default function () {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.getRegistration()
            .then(function (swReg) {
                askPermission()
                    .then(() => subscribeUser(swReg))
                    .catch(() => unsubscribeUser(swReg))
            })
            .catch(err => console.error('Service Worker Error', err));
    } else {
        console.warn('Push messaging is not supported');
    }
}
