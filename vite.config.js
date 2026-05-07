import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                admin: 'assets/js/admin.js',
            },
            output: {
                entryFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
});
