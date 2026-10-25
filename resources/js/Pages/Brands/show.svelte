<script>
    import {blur} from 'svelte/transition';
    import { router } from '@inertiajs/svelte';
    export const id = 0;
    export let brand;
    export let audits;
    let error = null;
    let url = `/brands/`;

    function goTo(route){
        router.visit(route);
    }
</script>
<svelte:head>
    <title>{brand?.brand_name}</title>
</svelte:head>
{#if error}
	<p>{error}</p>
{/if}
<div class="breadcrumbs text-md mb-4">
	<ul>
		<!-- svelte-ignore a11y-click-events-have-key-events -->
		<li><span class="cursor-pointer" on:click={()=>goTo("/")}>Inicio</span></li>
		<!-- svelte-ignore a11y-click-events-have-key-events -->
		<li><span class="cursor-pointer" on:click={()=>goTo("/brands")}>Marcas</span></li>
	</ul>
</div>
{#if brand}
    <div transition:blur>
        <h1 class="text-xl font-bold">Nombre:</h1>
        <p class="text-1xl">{brand.brand_name}</p>
        <h1 class="text-xl font-bold">Descripcion:</h1>
        <p class="text-1xl">{brand.brand_desc}</p>
    </div>
{/if}
{#if audits}
    <div transition:blur>
        <h1 class="text-xl font-bold mt-4">Historial de cambios:</h1>
        <table class="table w-full">
            <thead>
                <tr>
                    <!-- <th>Id</th> -->
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
