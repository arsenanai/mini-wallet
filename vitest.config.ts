import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['resources/js/tests/setup.ts'],
        include: ['resources/js/tests/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
        alias: {
            '@': resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(
                __dirname,
                './vendor/tightenco/ziggy/dist/vue.es.js',
            ),
        },
    },
});