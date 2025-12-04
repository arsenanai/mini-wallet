import { defineConfig } from 'vitest/config';
import { resolve } from 'path';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: 'resources/js/tests/setup.ts',
        include: ['resources/js/tests/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
        server: {
            // Force Vitest to process ziggy-js through the transform pipeline.
            deps: { inline: ['ziggy-js'] },
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, './resources/js'),
        },
    },
});