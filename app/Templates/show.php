<?php

function generateShowComponent($modelName, $fields) {
    // Convertimos el nombre del modelo a minúsculas para usar en la URL y permisos
    $modelNameLower = strtolower($modelName);

    // Obtenemos el primer campo para usar en la descripción si es necesario
    $descriptionField = $fields[0];

    // Iniciamos el contenido del archivo
    $svelteTemplate = "
<script>
    import { onMount } from 'svelte';
    import axios from 'axios';
    import { blur } from 'svelte/transition';
    import { router } from '@inertiajs/svelte';

    export let id = 0;
    
    let item = {};
    let audits = [];
    let error = null;
    let url = `\/api/{$modelNameLower}s/`;

    async function fetchData() {
        axios.get(`${url}${id}`).then((response) => {
            item = response.data.data;
            audits = response.data.audits;
        }).catch((err) => {
            error = err.request.response;
        });
    }

    onMount(async () => {
        fetchData();
    });

    function goTo(route){
        router.visit(route);
    }
</script>

{#if error}
    <p>{error}</p>
{/if}

<div class=\"breadcrumbs text-md mb-4\">
    <ul>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class=\"cursor-pointer\" on:click={() => goTo('/')}>Inicio</span></li>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class=\"cursor-pointer\" on:click={() => goTo('/{$modelNameLower}s')}>Tipos de {$modelName}</span></li>
    </ul>
</div>

{#if item}
    <div transition:blur>
        <h1 class=\"text-xl font-bold\">Descripción:</h1>
        <p class=\"text-1xl\">{item.{$descriptionField}}</p>
    </div>
{/if}

{#if audits}
    <div transition:blur>
        <h1 class=\"text-xl font-bold mt-4\">Historial de cambios:</h1>
        <table class=\"table w-full\">
            <thead>
                <tr>
                    <th class=\"text-center text-lg\">Usuario</th>
                    <th class=\"text-center text-lg\">Evento</th>
                    <th class=\"text-center text-lg\">Fecha</th>
                    <th class=\"text-center text-lg\">Detalles</th>
                </tr>
            </thead>
            <tbody>
                {#each audits as audit}
                    <tr>
                        <td class=\"text-center\">{audit.user ? audit.user.name : ''}</td>
                        <td class=\"text-center\">{audit.event}</td>
                        <td class=\"text-center\">{new Date(audit.created_at).toLocaleString()}</td>
                        <td class=\"text-center\">
                            <strong>Valores antiguos:</strong> {JSON.stringify(audit.old_values)}<br>
                            <strong>Valores nuevos:</strong> {JSON.stringify(audit.new_values)}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}";

    return $svelteTemplate;
}
