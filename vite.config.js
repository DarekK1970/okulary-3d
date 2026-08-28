import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/mobile.css',
                'resources/css/auth.css',
                'resources/css/admin.css',
                'resources/css/admin-cms.css',
                'resources/css/admin-multilang.css',
                'resources/css/admin-media.css',
                'resources/css/admin-shop.css',
                'resources/css/admin-orders.css',
                'resources/css/admin-settings.css',
                'resources/css/article.css',
                'resources/css/cart.css',
                'resources/css/shop.css',
                'resources/js/app.js',
                'resources/js/admin-cms.js',
                'resources/js/admin-multilang.js',
                'resources/js/admin-media.js',
                'resources/js/admin-shop.js',
                'resources/js/cart.js',
            ],
            refresh: true,
        }),
    ],
});
