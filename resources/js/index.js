import flatpickr from "flatpickr";
import {German} from "flatpickr/dist/l10n/de.js"
import {english} from "flatpickr/dist/l10n/default.js"
const lang = {en : english, de: German};
flatpickr.localize(lang[document.documentElement.lang]);
flatpickr("input[type=date]");

import PushNotifications from './PushNotifications';

PushNotifications();

window.toggleOrder = function (e) {
    let form = e.target;

    let submitButton = form.querySelector('.btn-submit');
    submitButton.attributes.disabled = true;

    let spinner = submitButton.querySelector('.spinner-border');
    spinner.classList.remove('d-none');

    fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        redirect: 'follow',
        headers: {
            'X-CSRF-TOKEN': window.Futtertrog.csrf,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(form)
    })
        .then(res => res.text())
        .then(data => {
                let container = form.closest('.meal-container');
                let el = new DOMParser().parseFromString(data, 'text/html');

                container.innerHTML = el.querySelector('#' + container.id).innerHTML;
                document.getElementById('calendar').innerHTML = el.querySelector('#calendar').innerHTML;
            }
        );

    e.preventDefault();
};