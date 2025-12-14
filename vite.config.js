import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import fs from 'fs';
import os from 'os';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/spa/app.js', 'resources/css/app.css'],
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
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js/spa'),
        },
    },
    server: {
        host: 'crm.marceli.to.test',
        cors: true,
        https: {
            key: fs.readFileSync(path.resolve(os.homedir(), 'Library/Application Support/Herd/config/valet/Certificates/crm.marceli.to.test.key')),
            cert: fs.readFileSync(path.resolve(os.homedir(), 'Library/Application Support/Herd/config/valet/Certificates/crm.marceli.to.test.crt')),
        },
        hmr: {
            host: 'crm.marceli.to.test',
        },
    },
});
