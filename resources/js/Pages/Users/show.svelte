<script>
    import { onMount } from 'svelte';
    import {blur} from 'svelte/transition';
    import axios from 'axios';
    import { Inertia } from '@inertiajs/inertia';
    import AsingRole from './asingRole.svelte';
    export let user;
    export let appUrl
    export let id = 0;
    export let users;
    export let audits;
    let error = null;
    let url = `${appUrl}/api/users/`;

    function goTo(route){
        Inertia.visit(route);
    }
</script>
{#if error}
	<p>{error}</p>
{/if}
<div class="breadcrumbs text-md mb-4">
	<ul>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={()=>goTo("/")}>Inicio</span></li>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={()=>goTo("/users")}>Usuarios</span></li>
	</ul>
</div>
{#if users}
    <div transition:blur>
        <h1 class="text-xl font-bold">Descripcion:</h1>
        <p class="text-1xl">{users.name}</p>
        <p class="text-1xl">{users.email}</p>
        <AsingRole userId={users.id} role={users.roles} />
    </div>
{/if}
{#if audits}
    <div transition:blur>
        <h1 class="text-xl font-bold mt-4">Detalles de cambios:</h1>
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
                        <td class="text-center">{audit.user?audit.user.name:''}</td>
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
