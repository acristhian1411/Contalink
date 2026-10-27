<script>
    import { blur } from 'svelte/transition';
    export let till = {};
    export let audits = [];
</script>

{#if till}
        <div transition:blur>
            <h1 class="text-xl font-bold">Descripcion:</h1>
            <p class="text-1xl">{till.till_name}</p>
        </div>
    {/if}
    {#if audits}
        <div transition:blur>
            <h1 class="text-xl font-bold mt-4">Detalles de cambios:</h1>
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