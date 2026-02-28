import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/scripts.js', 'resources/js/apps.js', 'resources/js/charts.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/.env'],
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-core';
                        }
                        if (id.includes('chart.js')) {
                            return 'vendor-charts';
                        }
                        return 'vendor';
                    }
                }
            }
        },
        chunkSizeWarningLimit: 1000,
    }
});