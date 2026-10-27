<script>
    import { blur } from 'svelte/transition';
    import { router } from '@inertiajs/svelte';

    export let refund;
    export let audits = [];

    function goTo(route) {
        router.visit(route);
    }
</script>

<div class="breadcrumbs text-md mb-4">
    <ul>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/')}>Inicio</span></li>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/refunds')}>Devoluciones</span></li>
    </ul>
</div>

{#if refund}
    <div transition:blur>
        <h1 class="text-xl font-bold">Cliente:</h1>
        <p class="text-1xl">{refund.sale?.person?.person_fname} {refund.sale?.person?.person_lastname}</p>
        <h1 class="text-xl font-bold mt-4">Número de factura:</h1>
        <p class="text-1xl">{refund.sale?.sale_number}</p>
        <h1 class="text-xl font-bold mt-4">Fecha:</h1>
        <p class="text-1xl">{refund.refund_date}</p>
        <h1 class="text-xl font-bold mt-4">Estado:</h1>
        <p class="text-1xl">{refund.refund_status}</p>
        <h1 class="text-xl font-bold mt-4">Observación:</h1>
        <p class="text-1xl">{refund.refund_obs}</p>
        <h1 class="text-xl font-bold mt-4">Detalles de la devolución:</h1>
        {#if refund.refundDetails?.length}
            <table class="table w-full">
                <thead>
                    <tr>
                        <th class="text-center text-lg">Producto</th>
                        <th class="text-center text-lg">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    {#each refund.refundDetails as detail}
                        <tr>
                            <td class="text-center">{detail.product?.product_name}</td>
                            <td class="text-center">{detail.quantity}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        {/if}
    </div>
{/if}

{#if audits.length}
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
