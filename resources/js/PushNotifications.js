/* eslint-env browser, es6 */
'use strict';

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

    fetch('/subscriptions', {
        method: 'POST',
        credentials: 'same-origin',
        redirect: 'follow',
        headers: {
            'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    });
}

function removeSubscriptionOnSever(subscription) {
    fetch('/subscriptions', {
        method: 'DELETE',
        credentials: 'same-origin',
        redirect: 'follow',
        headers: {
            'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
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
                subscription.unsubscribe()
                    .then(() => removeSubscriptionOnSever(subscription));
            }
        })
        .catch(function (error) {
            console.log('Error unsubscribing', error);
        });
}

function askPermission() {
    return new Promise(function (resolve, reject) {

        if (window.Futtertrog.user === null) {
            throw new Error('No authenticated user');
        }

        const permissionResult = Notification.requestPermission(function (result) {
            resolve(result);
        });

        if (permissionResult) {
            permissionResult.then(resolve, reject);
        }
    })
        .then(function (permissionResult) {
            if (permissionResult !== 'granted') {
                throw new Error('We weren\'t granted permission.');
            }
        });
}

export default function () {

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('serviceworker.js')
            .then(function (swReg) {
                askPermission()
                    .then(() => subscribeUser(swReg))
                    .catch(() => unsubscribeUser(swReg))
            })
            .catch(function (error) {
                console.error('Service Worker Error', error);
            });
    } else {
        console.warn('Push messaging is not supported');
    }
}
