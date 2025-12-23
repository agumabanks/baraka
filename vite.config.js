import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/css/branch.css',
                'resources/css/client.css',
                'resources/js/branch.js',
                'resources/css/settings.css',
                'resources/js/settings.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
                'resources/css/landing.css',
            ],
            refresh: true,
        }),
        react(),
    ],
});
