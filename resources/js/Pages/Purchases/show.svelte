<script>
    import { blur } from 'svelte/transition';
    import { router } from '@inertiajs/svelte';

    export let purchase;
    export let audits = [];

    let item = purchase;

    function goTo(route){
        router.visit(route);
    }
</script>

<div class="breadcrumbs text-md mb-4">
    <ul>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/')}>Inicio</span></li>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/purchases')}>Compras</span></li>
    </ul>
</div>

{#if item}
    <div transition:blur>
        <h1 class="text-xl font-bold">Proveedor:</h1>
        <p class="text-1xl">{item.person?.person_fname} {item.person?.person_lastname}</p>
        <h1 class="text-xl font-bold">Fecha:</h1>
        <p class="text-1xl">{item.purchase_date}</p>
        <h1 class="text-xl font-bold">Numero de factura:</h1>
        <p class="text-1xl">{item.purchase_number}</p>
        <h1 class="text-xl font-bold">Detalles de la compra:</h1>
        {#if item.purchaseDetails}
            <table class="table w-full">
                <thead>
                    <tr>
                        <th class="text-center text-lg">Producto</th>
                        <th class="text-center text-lg">Cantidad</th>
                        <th class="text-center text-lg">Precio</th>
                    </tr>
                </thead>
                <tbody>
                    {#each item.purchaseDetails as detail}
                        <tr>
                            <td class="text-center">{detail.product?.product_name}</td>
                            <td class="text-center">{detail.pd_qty} {detail.unit_name ?? ''}</td>
                            <td class="text-center">{detail.pd_amount}</td>
                        </tr>
                    {/each}
                    <tr>
                        <td class="text-center">Total</td>
                        <td class="text-center" colspan="2">{item.purchaseDetails.reduce((acc, curr) => acc + (curr.pd_amount * curr.pd_qty), 0)}</td>
                    </tr>
                </tbody>
            </table>
        {/if}
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
