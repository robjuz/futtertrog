// es modules are recommended, if available, especially for typescript
import flatpickr from "flatpickr";
import {German} from "flatpickr/dist/l10n/de.js"
import {english} from "flatpickr/dist/l10n/default.js"
const lang = {en : english, de: German};
flatpickr.localize(lang[document.documentElement.lang]);
flatpickr("input[type=date]");

window.toggleOrder = function(e) {
  var form = e.target;

  var submitButton = form.querySelector('.btn-submit');
  submitButton.attributes.disabled = true;

  var spinner = submitButton.querySelector('.spinner-border');
  spinner.classList.toggle('d-none');

  fetch(form.action, {
    method: 'POST',
    credentials: 'same-origin',
    redirect: 'follow',
    headers: {
      'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: new FormData(form)
  }).then(function(res) {
    return res.text();
  }).then(function(data) {
    var container = form.closest('.meal-container');
    var el = new DOMParser().parseFromString(data, 'text/html');

    container.innerHTML = el.querySelector('#' + container.id).innerHTML;
    document.getElementById('calendar').innerHTML = el.querySelector('#calendar').innerHTML;
  });

  e.preventDefault();
};

function urlBase64ToUint8Array (base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
      .replace(/\-/g, '+')
      .replace(/_/g, '/')

  const rawData = window.atob(base64)
  const outputArray = new Uint8Array(rawData.length)

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i)
  }

  return outputArray
}

navigator.serviceWorker.ready.then(registration => {
  const options = { userVisibleOnly: true };
  const vapidPublicKey = window.Laravel.vapidPublicKey;

  if (vapidPublicKey) {
    options.applicationServerKey = urlBase64ToUint8Array(vapidPublicKey)
  }

  console.log(options);

  registration.pushManager.subscribe(options)
      .then(subscription => {
        console.log(subscription);
        updateSubscription(subscription)
      })
      .catch(e => {
        if (Notification.permission === 'denied') {
          console.log('Permission for Notifications was denied')
        } else {
          console.log('Unable to subscribe to push.', e);
        }
      })
});

/**
 * Send a request to the server to update user's subscription.
 *
 * @param {PushSubscription} subscription
 */
function updateSubscription (subscription) {
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
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: data
  });

}