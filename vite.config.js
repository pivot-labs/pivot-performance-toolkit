import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

function stripEsmExportFromAdminBundle() {
    return {
        name: 'strip-esm-export-from-admin-bundle',
        closeBundle() {
            const outputFile = path.resolve(process.cwd(), 'dist/admin-js.js');

            if (!fs.existsSync(outputFile)) {
                return;
            }

            const source = fs.readFileSync(outputFile, 'utf8');
            const rewritten = source.replace(/export default e\(\);?\s*$/, 'e();\n');

            if (rewritten !== source) {
                fs.writeFileSync(outputFile, rewritten, 'utf8');
            }
        },
    };
}

export default defineConfig({
    plugins: [tailwindcss(), stripEsmExportFromAdminBundle()],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                admin: 'src/css/admin.css',
                'admin-js': 'src/js/admin.js',
            },
            output: {
                entryFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
});
