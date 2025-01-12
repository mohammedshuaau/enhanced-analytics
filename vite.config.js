import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/consent-banner.js',
                'resources/js/enhanced-analytics.js'
            ],
            publicDirectory: 'resources/dist',
            buildDirectory: 'build'
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs']
                }
            }
        }
    }
});