import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/enhanced-analytics.css'
            ],
            //publicDirectory: 'resources/dist',
            //buildDirectory: 'build'
        }),
    ]
});
