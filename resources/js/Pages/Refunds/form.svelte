<script>
    import { router, useForm } from '@inertiajs/svelte';
    import axios from 'axios';
    import { SearchIcon } from '@components/Icons/';
    import { formatNumber } from '@components/utilities/NumberFormat.js';
    import { Textfield } from '@components/FormComponents';

    let sale_number = '';
    let observation = '';
    let sale = null;
    let saleDetails = [];
    let errors = null;

    let today = new Date().toISOString().slice(0, 10);

    let form = useForm({
        sale_id: null,
        refund_date: today,
        refund_obs: '',
        refund_details: [],
    });

    function searchSale() {
        if (sale_number == '') {
            return;
        }
        axios.get(`/sales/search/${sale_number}`).then((response) => {
            sale = response.data.data;
            saleDetails = sale.sales_details.map((x) => ({ ...x, sd_qty_devuelto: 0 }));
        }).catch((err) => {
            errors = [err.response?.data?.message || 'No se encontró la factura'];
        });
    }

    function markReturn(item) {
        let newItem = saleDetails;
        let idx = newItem.findIndex((x) => x.id === item.id);
        newItem[idx].sd_qty = parseInt(newItem[idx].sd_qty) - 1;
        newItem[idx].sd_qty_devuelto = parseInt(newItem[idx].sd_qty_devuelto || 0) + 1;
        saleDetails = newItem;
    }

    function handleSubmit() {
        form.sale_id = sale?.id ?? null;
        form.refund_obs = observation;
        form.refund_details = (saleDetails || [])
            .filter((x) => x.sd_qty_devuelto > 0)
            .map((x) => ({ product_id: x.product_id, sd_qty_devuelto: x.sd_qty_devuelto }));

        form.post('/refunds', {
            onSuccess: () => router.visit('/refunds'),
            onError: (err) => {
                errors = Object.values(err).flat();
            },
        });
    }
</script>

<h3 class="mb-4 text-center text-2xl">Nueva Devolución</h3>

{#if errors}
    <div class="mt-4 mb-4">
        <ul class="list-disc list-inside text-red-500">
            {#each errors as error}
                <li>{error}</li>
            {/each}
        </ul>
    </div>
{/if}

<form on:submit|preventDefault={handleSubmit}>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-3">
            <Textfield label="Num. Factura" type="text" bind:value={sale_number} />
        </div>
        <div class="col-span-1 flex items-end mb-2">
            <button class="btn btn-primary" type="button" on:click={searchSale}>
                <SearchIcon />
            </button>
        </div>
        {#if sale}
            <div class="col-span-4 flex items-center mb-2">
                {sale.person?.person_fname} {sale.person?.person_lastname}
            </div>
        {/if}
        <div class="col-span-4">
            <Textfield
                label="Fecha"
                type="date"
                bind:value={form.refund_date}
                errors={form.errors.refund_date ? { message: form.errors.refund_date } : null}
            />
        </div>
    </div>

    <div class="grid grid-cols-12 mt-4 gap-4">
        <div class="col-span-12">
            <Textfield
                label="Observación"
                type="text"
                bind:value={observation}
                errors={form.errors.refund_obs ? { message: form.errors.refund_obs } : null}
            />
        </div>
    </div>

    {#if sale}
        <table class="table w-full mt-4">
            <thead>
                <tr>
                    <th class="text-center text-lg">Cant</th>
                    <th class="text-center text-lg">Producto</th>
                    <th class="text-center text-lg">Iva</th>
                    <th class="text-center text-lg">P. Unitario</th>
                    <th class="text-center text-lg">Total</th>
                    <th class="text-center text-lg"></th>
                </tr>
            </thead>
            <tbody>
                {#each saleDetails as item (item.id)}
                    <tr class="hover">
                        <td class="text-center">{item.sd_qty}</td>
                        <td class="text-center">{item.product.product_name}</td>
                        <td class="text-center">{item.product.iva_type?.iva_type_percent}</td>
                        <td class="text-center">{item.product.product_selling_price}</td>
                        <td class="text-center">{formatNumber(parseInt(item.product.product_selling_price) * item.sd_qty)}</td>
                        {#if item.sd_qty > 0}
                            <td class="text-center">
                                <button type="button" class="btn btn-secondary" on:click={() => markReturn(item)}>Devolver</button>
                            </td>
                        {/if}
                    </tr>
                {/each}
                {#if saleDetails.length > 0}
                    <tr>
                        <td colspan="4" class="text-center">Total</td>
                        <td class="text-center">
                            {formatNumber(saleDetails.reduce((acc, curr) => acc + (curr.product.product_selling_price * curr.sd_qty), 0))}
                        </td>
                    </tr>
                {/if}
            </tbody>
        </table>
        <button class="btn btn-primary" type="submit" disabled={form.processing}>
            {form.processing ? 'Guardando...' : 'Guardar'}
        </button>
        <button class="btn btn-secondary" type="button" on:click={() => router.visit('/refunds')}>Cancelar</button>
    {/if}
</form>
