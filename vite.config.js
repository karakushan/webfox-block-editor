import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    root: new URL('.', import.meta.url).pathname,
    define: {
        process: JSON.stringify({ env: { NODE_ENV: 'production' } }),
    },
    plugins: [vue()],
    build: {
        lib: {
            entry: 'resources/js/block-editor.js',
            formats: ['iife'],
            name: 'WebfoxBlockBuilder',
            fileName: () => 'block-editor.js',
        },
        outDir: 'resources/dist',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                assetFileNames: 'block-editor.[ext]',
            },
        },
    },
});
