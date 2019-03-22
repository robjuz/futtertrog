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

Notification.requestPermission();
