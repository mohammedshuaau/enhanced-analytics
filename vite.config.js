import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    build: {
        //manifest: true,
        outDir: 'public/dist'
    },
    plugins: [
        laravel({
            input: [
                'resources/css/enhanced-analytics.css',
                'resources/js/enhanced-analytics.js'
            ],
            refresh: true,
            publicDirectory: 'public',
            buildDirectory: 'dist'
        })
    ]
});
