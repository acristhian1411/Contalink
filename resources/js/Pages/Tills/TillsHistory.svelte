<script>
    import { onMount } from 'svelte';
    import axios from 'axios';
    import {formatNumber} from '@components/utilities/NumberFormat';
    import {Textfield, Autocomplete} from '@components/FormComponents';
	import {Pagination, DeleteModal, Modal} from '@components/utilities/';
    import { } from 'os';

    let date = new Date();
    let month = date.getMonth() + 1;
    month = month < 10 ? '0' + month : month;
    let fullDate = date.getFullYear() + '-' + month + '-' + date.getDate();

    export let till_id
    let tills = [];
    let fromDate = fullDate;
    let toDate = fullDate;
    let typeSelected = '';
    let errors = null;
    let orderBy = 'id';
	let order = 'asc';
	let total_pages;
	let total_items;
	let current_page = 1;
	let items_per_page = '10';

    function getHistory(id,from = '',to = ''){
        axios.get(`/api/tilldetails/${id}/history`,{
            params: {
                from: from,
                to: to,
                type: typeSelected,
                order: order,
                sort_by: orderBy,
                page: current_page,
                per_page: items_per_page
            }
        }).then((response) => {
            tills = response.data.data;
            current_page = response.data.currentPage;
            total_items = response.data.per_page;
            total_pages = response.data.last_page;
        }).catch((err) => {
            console.log(err);
        });
    }
    function handleRowsPerPage(event) {
		items_per_page = event.detail.value;
		getHistory(till_id,fromDate,toDate);
	}
	function handlePage(event) {
		current_page = event.detail.value;
		getHistory(till_id,fromDate,toDate);
	}
    onMount(async () => {
        console.log('fecha actual: ',fullDate);
        getHistory(till_id,fromDate,toDate);
    });

</script>

<h1 align="center" class='mb-3 mt-3 text-2xl'>Historial de caja</h1>

<div class="grid grid-cols-3 gap-4">
    <div>
        <Textfield 
            label="Fecha de inicio" 
            type="date"
            bind:value={fromDate}
            error={errors && errors.fromDate}
        />
    </div>
    <div>
        <Textfield 
            label="Fecha fin" 
            type="date"
            bind:value={toDate}
            error={errors && errors.toDate}
        />
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
        <button class="btn btn-primary" on:click={getHistory(till_id,fromDate,toDate)}>Filtrar</button>
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
        {#each tills as till, index}
            <tr class="hover">
                <td class="text-center">{index+1}</td>
                <td class="text-center">{till.td_desc}</td>
                <td class="text-center">{new Date(till.created_at).toLocaleString()}</td>
                <td class="text-center" class:text-green-600={till.td_type} class:text-red-600={!till.td_type}>{till.td_type == true ? 'Ingreso' : 'Egreso'}</td>
                <td class="text-center">{formatNumber(till.td_amount)}</td>
                <!-- //TODO agregar función para poder eliminar un registro del historial de caja -->
                <td class="text-center">
                    <button class="btn btn-error" 
                    >Eliminar</button>
            </tr>
        {/each}
        <tr>
            <td colspan="4" class="text-left text-lg">Total ingresos</td>
            <td class="text-center">{formatNumber(tills.filter((x)=>x.td_type == true).reduce((acc, curr) => 
            acc + curr.td_amount
            , 0))}</td>
        </tr>
        <tr>
            <td colspan="4" class="text-left text-lg">Total egresos</td>
            <td class="text-center">{formatNumber(tills.filter((x)=>x.td_type == false).reduce((acc, curr) => 
            acc + curr.td_amount
            , 0))}</td>
        </tr>
        <tr>
            <td colspan="4" class="text-left text-lg">Total</td>
            <td class="text-center">{formatNumber(
                tills.filter((x) => x.td_type == true).reduce((acc, curr) => acc + curr.td_amount, 0) -
                tills.filter((x) => x.td_type == false).reduce((acc, curr) => acc + curr.td_amount, 0)
            )}</td>
        </tr>
    </tbody>
</table>
<Pagination
			current_page={current_page?current_page:1}
			{total_pages}
			{items_per_page}
			on:page={handlePage}
			on:row={handleRowsPerPage}
		/>
<!-- //TODO agregar paginación -->