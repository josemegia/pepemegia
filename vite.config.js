import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import path from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/flyer.js'
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
	    'flatpickr-theme': path.resolve(__dirname, 'node_modules/flatpickr/dist/themes/dark.css'),
        },
    },
})

