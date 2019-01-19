window.jQuery = window.$ = require('jquery');
require('jquery-ui/ui/widgets/datepicker');
require('jquery-ui/ui/i18n/datepicker-de');

let lang = document.documentElement.lang;

$('input[type=date]').each(function () {
  this.type = 'text';
  if (this.value) {
    this.value = new Date(this.value).toLocaleDateString(lang);
  }

  $(this).datepicker(
    {
      altField: '#' + this.id + '_raw',
      altFormat: 'yy-mm-dd'
    }
    // Object.assign({}, {
    //   altField: 'from_alt',
    //   altFormat: "DD, d MM, yy"
    // }, $.datepicker.regional[ lang ])
  );
});