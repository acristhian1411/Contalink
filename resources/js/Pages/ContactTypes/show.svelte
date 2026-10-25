
<script>
    import { onMount } from 'svelte';
    import axios from 'axios';
    import { blur } from 'svelte/transition';
    import { router } from '@inertiajs/svelte';

    export const id = 0;
    
    export let contacttype;
    export let audits;
    let error = null;
    let url = `/contact-types/`;

    function goTo(route){
        router.visit(route);
    }
</script>

{#if error}
    <p>{error}</p>
{/if}

<svelte:head>
    <title>{contacttype?.cont_type_desc}</title>
</svelte:head>
<div class="breadcrumbs text-md mb-4">
    <ul>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/')}>Inicio</span></li>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/contact-types')}>Tipos de Contactos</span></li>
    </ul>
</div>

{#if contacttype}
    <div transition:blur>
        <h1 class="text-xl font-bold">Descripción:</h1>
        <p class="text-1xl">{contacttype.cont_type_desc}</p>
    </div>
{/if}

{#if audits}
    <div transition:blur>
        <h1 class="text-xl font-bold mt-4">Historial de cambios:</h1>
        <table class="table w-full">
            <thead>
                <tr>
                    <th class="text-center text-lg">Usuario</th>
                    <th class="text-center text-lg">Evento</th>
                    <th class="text-center text-lg">Fecha</th>
                    <th class="text-center text-lg">Detalles</th>
                </tr>
            </thead>
            <tbody>
                {#each audits as audit}
                    <tr>
                        <td class="text-center">{audit.user ? audit.user.name : ''}</td>
                        <td class="text-center">{audit.event}</td>
                        <td class="text-center">{new Date(audit.created_at).toLocaleString()}</td>
                        <td class="text-center">
                            <strong>Valores antiguos:</strong> {JSON.stringify(audit.old_values)}<br>
                            <strong>Valores nuevos:</strong> {JSON.stringify(audit.new_values)}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}