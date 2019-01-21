require('jquery-ui/ui/widgets/datepicker');
require('jquery-ui/ui/i18n/datepicker-de');

let lang = document.documentElement.lang;

$('input[type=date]').each(function () {
  this.type = 'text';
  $(this).parent().append('<input type="hidden" name="' + this.name+'" id="' + this.id + '_raw">');
  this.name = '';
  if (this.value) {
    this.value = new Date(this.value).toLocaleDateString(lang);
  }

  $(this).datepicker(
    Object.assign({}, {
      altField: '#' + this.id + '_raw',
      altFormat: 'yy-mm-dd'
    }, $.datepicker.regional[ lang ])
  );
});

window.order = function(e, type) {
  let form = $(e.target);

 form.find(':submit').attr('disabled', true).find('.spinner-border').toggleClass('d-none');

  $.ajax({
    type,
    url: form.attr('action'),
    data: form.serialize(),
    success: function(data)
    {
      form.closest('.meal-container').html(data);
    }
  });

  e.preventDefault();
};