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
          'alert-warning'
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
