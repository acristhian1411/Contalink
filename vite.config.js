import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { svelte } from "@sveltejs/vite-plugin-svelte";
import path from 'path';


export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        svelte({}),
    ],
    resolve: {
        alias: {
            '@components': `${__dirname}/resources/js/components`,
            'axios': path.resolve(__dirname, 'node_modules', 'axios/dist/esm/axios.js'), 
        }
    },
});
