const mix = require('laravel-mix');

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

mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .js('resources/js/api-docs.js', 'public/js')
   .sass('resources/sass/api-docs.scss', 'public/css')
   .js('resources/js/docs.js', 'public/js')
   .sass('resources/sass/docs.scss', 'public/css')
   .copyDirectory('node_modules/prismjs/themes', 'public/css/prism-themes')
   .version();
