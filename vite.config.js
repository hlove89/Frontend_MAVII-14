import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/welcome.css',
                'resources/css/login.css',
                'resources/css/dashboard.css',
                'resources/css/history.css',
                'resources/css/technicians.css',
                'resources/css/profile.css',
                'resources/css/notification.css',
                'resources/css/welcome.css',
                'resources/js/app.js',
                'resources/js/login.js',
                'resources/js/history.js',
                'resources/js/technicians.js',
                'resources/js/profile.js',
                'resources/js/notification.js',
                'resources/js/reset-password.js',
            ],
            refresh: true,
        }),
    ],
});