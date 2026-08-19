import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/sass/app.scss','resources/sass/admin.scss','resources/css/app.css','resources/css/admin.css','resources/js/admin.js', 'resources/js/app.js', 'resources/ecomx-fashion/scss/app.scss', 'resources/ecomx-fashion/js/app.js'],
            refresh: true,
        }),
        tailwindcss()
    ],
});
