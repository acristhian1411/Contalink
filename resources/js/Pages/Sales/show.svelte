
<script>
    import { onMount } from 'svelte';
    import axios from 'axios';
    import { blur } from 'svelte/transition';
    import { router } from '@inertiajs/svelte';

    export let id ;
    
    let item = {};
    let audits = [];
    let error = null;
    let url = `/api/sales/`;

    async function fetchData() {
        axios.get(`${url}${id}`).then((response) => {
            item = response.data.data;
            audits = response.data.audits;
        }).catch((err) => {
            error = JSON.parse(err.request.response);
        });
    }

    onMount(async () => {
        console.log(id);
        fetchData();
    });

    function goTo(route){
        router.visit(route);
    }
</script>

{#if error}
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <strong class="font-bold">Error!</strong>
        <span class="block sm:inline">{error.message}</span>
    </div>
{/if}

<div class="breadcrumbs text-md mb-4">
    <ul>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/')}>Inicio</span></li>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/sales')}>Ventas</span></li>
    </ul>
</div>

{#if item}
    <div transition:blur>
        <h1 class="text-xl font-bold">Cliente:</h1>
        <p class="text-1xl">{item.person?.person_fname} {item.person?.person_lastname}</p>
        <h1 class="text-xl font-bold">Fecha:</h1>
        <p class="text-1xl">{item.sale_date}</p>
        <h1 class="text-xl font-bold">Numero de factura:</h1>
        <p class="text-1xl">{item.sale_number}</p>
        <h1 class="text-xl font-bold">Caja:</h1>
        <p class="text-1xl">{item.tills_details?.[0]?.till?.till_name}</p>
        <h1 class="text-xl font-bold">Forma de pago:</h1>
        <p class="text-1xl">{item.tills_details?.[0]?.tillproofPayments?.proofPayments?.paymentType?.payment_type_desc}</p>
        <h1 class="text-xl font-bold">Detalles de la venta:</h1>
        {#if item.sales_details}
            <table class="table w-full">
                <thead>
                    <tr>
                        <th class="text-center text-lg">Producto</th>
                        <th class="text-center text-lg">Cantidad</th>
                        <th class="text-center text-lg">Precio</th>
                    </tr>
                </thead>
                <tbody>
                    {#each item.sales_details as detail}
                        <tr>
                            <td class="text-center">{detail.product?.product_name}</td>
                            <td class="text-center">{detail.sd_qty}</td>
                            <td class="text-center">{detail.sd_amount}</td>
                        </tr>
                    {/each}
                    <tr>
                        <td class="text-center">Total</td>
                        <td class="text-center">{item.sales_details.reduce((acc, curr) => acc + (curr.sd_amount * curr.sd_qty), 0)}</td>
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