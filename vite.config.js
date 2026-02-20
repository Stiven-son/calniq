import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/widget/widget.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        rollupOptions: {
            output: {
                // Для виджета — предсказуемые имена файлов
                entryFileNames: (chunkInfo) => {
                    if (chunkInfo.name === 'widget') {
                        return 'bookingstack.js';
                    }
                    return 'assets/[name]-[hash].js';
                },
            },
        },
    },
});