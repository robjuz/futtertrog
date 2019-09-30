const mix = require('laravel-mix');
const WebpackShellPlugin = require('webpack-shell-plugin');

mix.webpackConfig({
    resolve: {
        extensions: ['.js', '.vue', '.json'],
        alias: {
              // Vendor
            vue$: 'vue/dist/vue.esm.js',
            '@': path.resolve(__dirname, 'resources/vue/'),
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

mix.js('resources/vue/index.js', 'public/js/vue.js');