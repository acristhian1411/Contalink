<script>
    import { router } from '@inertiajs/svelte';
    import { formatNumber } from '@components/utilities/NumberFormat';
    import { Textfield } from '@components/FormComponents';
    import { Pagination } from '@components/utilities/';

    export let till_id;
    export let history;

    $: items = history?.items?.data ?? [];
    $: totals = history?.totals ?? { incomes: 0, expenses: 0 };
    $: current_page = history?.items?.current_page ?? 1;
    $: total_pages = history?.items?.last_page ?? 1;
    $: items_per_page = history?.items?.per_page ? String(history.items.per_page) : '10';

    let fromDate = history?.filters?.from ?? '';
    let toDate = history?.filters?.to ?? '';
    let typeSelected = history?.filters?.type ?? 'ambos';
    let errors = null;

    function applyFilters(page = 1) {
        router.get(`/tills/${till_id}`, {
            from: fromDate,
            to: toDate,
            type: typeSelected || 'ambos',
            sort_by: 'id',
            order: 'asc',
            page: page,
            per_page: items_per_page,
        }, {
            only: ['history'],
            preserveState: true,
            preserveScroll: true,
            onError: (err) => {
                errors = Object.values(err).flat();
            },
        });
    }

    function handleRowsPerPage(event) {
        items_per_page = event.detail.value;
        applyFilters(1);
    }

    function handlePage(event) {
        applyFilters(event.detail.value);
    }
</script>

<h1 align="center" class='mb-3 mt-3 text-2xl'>Historial de caja</h1>

{#if errors}
    <p class="text-red-500">
        {#each errors as error}
            {error}
        {/each}
    </p>
{/if}

<div class="grid grid-cols-3 gap-4">
    <div>
        <Textfield label="Fecha de inicio" type="date" bind:value={fromDate} />
    </div>
    <div>
        <Textfield label="Fecha fin" type="date" bind:value={toDate} />
    </div>
    <div>
        <div class="flex mt-3">
            <div class="flex items-center me-4">
                <input id="inline-radio" type="radio" value="ingresos"
                bind:group={typeSelected} name="inline-radio-group" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="inline-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Ingresos</label>
            </div>
            <div class="flex items-center me-4">
                <input id="inline-2-radio" type="radio" value="egresos"
                bind:group={typeSelected} name="inline-radio-group" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="inline-2-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Egresos</label>
            </div>
            <div class="flex items-center me-4">
                <input id="inline-3-radio" type="radio" value="ambos"
                bind:group={typeSelected} name="inline-radio-group" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="inline-3-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Ambos</label>
            </div>
        </div>
    </div>
    <div>
        <button class="btn btn-primary" on:click={() => applyFilters(1)}>Filtrar</button>
    </div>
</div>

<table class="table w-full">
    <thead>
        <tr>
            <th class="text-center text-lg">#</th>
            <th class="text-center text-lg">Descripcion</th>
            <th class="text-center text-lg">Fecha</th>
            <th class="text-center text-lg">Tipo</th>
            <th class="text-center text-lg">Monto</th>
            <th class="text-center text-lg">Acciones</th>
        </tr>
    </thead>
    <tbody>
        {#each items as till, index}
            <tr class="hover">
                <td class="text-center">{(current_page - 1) * Number(items_per_page) + index + 1}</td>
                <td class="text-center">{till.td_desc}</td>
                <td class="text-center">{new Date(till.created_at).toLocaleString()}</td>
                <td class="text-center" class:text-green-600={till.td_type} class:text-red-600={!till.td_type}>{till.td_type == true ? 'Ingreso' : 'Egreso'}</td>
                <td class="text-center">{formatNumber(till.td_amount)}</td>
                <td class="text-center">
                    <button class="btn btn-error">Eliminar</button>
            </tr>
        {/each}
        <tr>
            <td colspan="4" class="text-left text-lg">Total ingresos</td>
            <td class="text-center">{formatNumber(totals.incomes)}</td>
        </tr>
        <tr>
            <td colspan="4" class="text-left text-lg">Total egresos</td>
            <td class="text-center">{formatNumber(totals.expenses)}</td>
        </tr>
        <tr>
            <td colspan="4" class="text-left text-lg">Total</td>
            <td class="text-center">{formatNumber(totals.incomes - totals.expenses)}</td>
        </tr>
    </tbody>
</table>
<Pagination
    {current_page}
    {total_pages}
    {items_per_page}
    on:page={handlePage}
    on:row={handleRowsPerPage}
/>
