const mix = require('laravel-mix');

if (mix.inProduction()) {
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
mix.version();
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

mix.sass('resources/sass/app.scss', 'public/css');
mix.js('resources/js/app.js', 'public/js');
