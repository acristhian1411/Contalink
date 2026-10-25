import './bootstrap';
import { createInertiaApp } from '@inertiajs/svelte';
import Layout from './components/Commons/Layout.svelte';

createInertiaApp({
    title: (title) => `Company Name - ${title}`,
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true })
        let page = pages[`./Pages/${name}.svelte`]
        return { default: page.default, layout: page.layout || Layout }
    },
})
