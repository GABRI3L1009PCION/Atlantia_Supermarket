import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

function normalizeLaravelManifest() {
    return {
        name: 'normalize-laravel-manifest',
        closeBundle() {
            const manifestPath = resolve('public/build/manifest.json');

            if (!existsSync(manifestPath)) {
                return;
            }

            const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
            const normalized = {};

            for (const [key, value] of Object.entries(manifest)) {
                const normalizedKey = key.replaceAll('\\', '/');

                if (normalizedKey.endsWith('/resources/css/app.css')) {
                    normalized['resources/css/app.css'] = {
                        ...value,
                        src: 'resources/css/app.css',
                    };
                    continue;
                }

                if (normalizedKey.endsWith('/resources/js/app.js')) {
                    normalized['resources/js/app.js'] = {
                        ...value,
                        src: 'resources/js/app.js',
                    };
                    continue;
                }

                normalized[key] = value;
            }

            writeFileSync(manifestPath, `${JSON.stringify(normalized, null, 2)}\n`);
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        normalizeLaravelManifest(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
