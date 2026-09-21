import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js','resources/css/bootstrap/bootstrap.min.css', 'resources/js/bootstrap/bootstrap.bundle.min.js'],
            refresh: true,
        }),
    ],
});
