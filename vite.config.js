import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/enhanced-analytics.js',
                'resources/css/enhanced-analytics.css'
            ],
            publicDirectory: 'resources/dist',
        }),
    ],
})