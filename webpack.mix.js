const mix = require('laravel-mix');

if (mix.inProduction()) {
  mix.version();

  mix.options({
    purifyCss: {
      purifyOptions: {
        purifyCss: true,
        whitelist: [
          'pagination',
          'page-item',
          'page-link',
          'alert-warning',
          'row-0',
          'row-1',
          'row-2',
          'row-3',
          'row-4',
          'row-5',
          'monday',
          'tuesday',
          'wednesday',
          'thursday',
          'friday',
          'saturday',
          'sunday'
        ]
      }
    }
  });
}

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js/app.js');
mix.sass('resources/sass/app.scss', 'public/css/sass.css');
mix.styles([
  'node_modules/flatpickr/dist/flatpickr.css',
  'public/css/sass.css',
], 'public/css/app.css');
