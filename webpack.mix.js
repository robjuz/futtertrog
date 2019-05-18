const mix = require('laravel-mix');
const WebpackShellPlugin = require('webpack-shell-plugin');

mix.webpackConfig({
    resolve: {
        extensions: ['.js', '.vue', '.json'],
        alias: {
              // Vendor
            vue$: 'vue/dist/vue.esm.js',
            '@': path.resolve(__dirname, 'resources/js/'),
        },
    },
    plugins:
    [
        new WebpackShellPlugin({onBuildStart:['php artisan lang:js --no-lib --quiet'], onBuildEnd:[]})
    ]
});

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

mix.js('resources/js/index.js', 'public/js/index.js');
mix.js('resources/js/app.js', 'public/js/app.js');

mix.sass('resources/sass/light.scss', 'public/css/light.css');
mix.sass('resources/sass/dark.scss', 'public/css/dark.css');
mix.sass('resources/sass/app.scss', 'public/css/app.css');
