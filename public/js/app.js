class ScrollIntoView extends HTMLElement {
    constructor() {
        super();
        this.current = this.querySelector('.selected');
        this.scrollIntoView();

        window.addEventListener('resize', this.scrollIntoView.bind(this));

    }

    scrollIntoView() {
        const halfWindow = window.innerWidth / 2;
        const currentElementWidth = this.current.getBoundingClientRect().width;
        this.current.parentElement.scrollLeft += this.current.getBoundingClientRect().left - halfWindow + currentElementWidth / 2;
    }

}

class ExpandableMenu {
    constructor(nav) {
        this.nav = nav;

        this.nav.classList.add('js');
        this.createToggleButton();
        this.updateLinks();
    }

    createToggleButton() {
        if (!!this.nav.getElementsByTagName('ul')[0]) {
            this.toggleButton = document.createElement('button');
            this.toggleButton.innerHTML = this.nav.getAttribute('data-button');
            this.toggleButton.setAttribute('aria-haspopup', 'true');
            this.toggleButton.setAttribute('aria-expanded', 'false');

            this.nav.insertAdjacentElement('afterbegin', this.toggleButton);
            this.toggleButton.addEventListener('click', this.onButtonClick.bind(this));
        }
    }

    updateLinks() {
        this.nav.querySelectorAll('a').forEach(function (link) {
            let href = link.getAttribute('href');
            link.setAttribute('href', href.split('#')[0]);
        });
    }

    onButtonClick() {
        if (this.isExpanded()) {
            this.toggleButton.setAttribute('aria-expanded', 'false');
        } else {
            this.toggleButton.setAttribute('aria-expanded', 'true');
        }
    }

    isExpanded() {
        return this.toggleButton.getAttribute('aria-expanded') === 'true';
    }

}

class PushNotifications {
    enable() {
        this.headers = {
            'X-CSRF-TOKEN': window.Futtertrog.csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };

        if ('serviceWorker' in navigator && 'PushManager' in window) {
            let _this = this;
            navigator.serviceWorker.getRegistration()
                .then(function (swReg) {
                    _this.askPermission()
                        .then(() => _this.subscribeUser(swReg))
                        .catch(() => _this.unsubscribeUser(swReg))
                })
                .catch(err => console.error('Service Worker Error', err));
        } else {
            console.warn('Push messaging is not supported');
        }
    }

    disable() {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            navigator.serviceWorker.getRegistration()
                .then( (swReg)  => this.unsubscribeUser(swReg))
                .catch(err => console.error('Service Worker Error', err));
        }
    }

    urlB64ToUint8Array(base64String) {
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

    updateSubscriptionOnServer(subscription) {

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
            headers: this.headers,
            body: JSON.stringify(data)
        });
    }

    removeSubscriptionOnSever(subscription) {
        return fetch('/subscriptions', {
            method: 'DELETE',
            credentials: 'same-origin',
            redirect: 'follow',
            headers: this.headers,
            body: JSON.stringify({endpoint: subscription.endpoint})
        });
    }

    subscribeUser(swRegistration) {
        swRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: this.urlB64ToUint8Array(window.Futtertrog.vapidPublicKey)
        })
            .then(subscription => this.updateSubscriptionOnServer(subscription))
            .catch(err => console.log('Failed to subscribe the user: ', err));
    }

    unsubscribeUser(swRegistration) {
        swRegistration.pushManager.getSubscription()
            .then((subscription) => {
                if (subscription) {
                    subscription.unsubscribe().then(() => this.removeSubscriptionOnSever(subscription));
                }
            })
            .catch(err => console.log('Error unsubscribing', err));
    }

    askPermission() {
        return new Promise((resolve, reject) => {
            if (window.Futtertrog.user === null) {
                return reject('No authenticated user');
            }

            Notification.requestPermission().then(permissionResult => {
                permissionResult === 'granted' ? resolve() : reject();
            });
        });
    }
}

window.Futtertrog.pushNotifications = new PushNotifications();

/* Initialising instances */
if (!!document.getElementById('main-navbar')) {
    new ExpandableMenu(document.getElementById('main-navbar'));
}

if (customElements && customElements.define) {
    customElements.define('scroll-into-view', ScrollIntoView);
}

/* disable submit button on form submit */
document.addEventListener('submit', function(e) {
    if (e.target.matches('form')) {
        let submitButton = e.target.querySelector('[type="submit"]');
        submitButton.disabled = true;
    }
});
