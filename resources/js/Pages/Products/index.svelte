
<script>
	// @ts-nocheck
	import { onMount } from 'svelte';
	import { inertia } from '@inertiajs/inertia-svelte';
	// import { isLoggedIn, getToken} from '../../services/authservice'
	// import {goto} from '$app/navigation';
	import axios from 'axios';
	import {Pagination, DeleteModal, Modal} from '@components/utilities/';
	import {Alert, ErrorAlert} from '@components/Alerts/';
	import {SearchIcon, SortIcon} from '@components/Icons/';
	import Form from './form.svelte';
	// import { appUrl } from '$env/static/public';
	export let user
    export let appUrl
	export let data;
	let products = [];
	let error = null;
	let openAlert = false;
	let _new = false;
	let edit = false;
	let item = null;
	let search_param = '';
	let openDeleteModal = false;
	let alertMessage = '';
	let alertType = '';
	let id = 0;
	let orderBy = 'p_type_desc';
	let order = 'asc';
	let total_pages;
	let total_items;
	let current_page = 1;
	let items_per_page = '10';
	let url = `${appUrl}/products/?`;

	function updateData() {
		fetchData(current_page, items_per_page, orderBy, order);
		closeModal();
	}

	async function assignData(data) {
		products = data.data;
		current_page = data.current_page;
		total_items = data.per_page;
		total_pages = data.last_page;
	}

	function fetchData(page = current_page, rows = items_per_page, orderBy = orderBy, order = order) {
		axios.get(`${url}sort_by=${orderBy}&order=${order}&page=${page}&per_page=${rows}`)
			.then((response) => {
				assignData(response.data);
			})
			.catch((err) => {
				error = err.request.response;
			});
	}

	function closeAlert() {
		openAlert = false;
	}

	function OpenAlertMessage(event) {
		openAlert = true;
		alertType = event.detail.type;
		alertMessage = event.detail.message;
	}

	function ClearError(){
		error = null;
	}

	function deleteRecord() {
		let token = '';
		let config = {
			headers: {
				authorization: `token: ${token}`,
			},
		}
		axios.delete(`${appUrl}/products/${id}`, config).then((res) => {
			let detail = {
				detail: {
					type: 'delete',
					message: res.data.message
				}
			};
			OpenAlertMessage(detail);
			closeDeleteModal();
		});
	}
	function openEditModal(data) {
		edit = true;
		item = data;
		_new = false;
	}
	function closeModal() {
		edit = false;
		_new = false;
	}
	function openNewModal() {
		edit = false;
		item = null;
		_new = true;
	}
	function closeDeleteModal() {
		openDeleteModal = false;
		fetchData(current_page, items_per_page, orderBy, order);
	}
	function sortData(param) {
		orderBy = param;
		if (order == 'asc') {
			order = 'desc';
		} else {
			order = 'asc';
		}
		fetchData(current_page, items_per_page);
	}
	function OpenDeleteModal(data) {
		id = data;
		openDeleteModal = true;
	}
	function handleRowsPerPage(event) {
		items_per_page = event.detail.value;
		fetchData(current_page, event.detail.value, orderBy, order);
	}
	function handlePage(event) {
		current_page = event.detail.value;
		fetchData(event.detail.value, items_per_page, orderBy, order);
	}
	function search(event) {
		search_param = event.target.value;
		if (search_param == '') {
			url = `${appUrl}/products?`;
		} else {
			url = `${appUrl}/products?product_name=${search_param}&product_barcode=${search_param}&`;
		}
		fetchData(1, items_per_page, orderBy, order);
	}
	onMount(async () => {
		// if(!isLoggedIn()){
		// 	goto('/login');
		// }
		fetchData(current_page, items_per_page, orderBy, order);
	});
</script>

{#if error}
	<ErrorAlert message={error} on:clearError={ClearError} />
{/if}
<h3 class="mb-4 text-center text-2xl">Productos</h3>
<div class="flex justify-center">
	<label class="input input-bordered flex items-center gap-2">
		<input type="text" class="grow" placeholder="Search" on:change={search} />
		<SearchIcon />
	</label>
</div>
{#if openAlert}
	<Alert {alertMessage} {alertType} on:close={closeAlert} />
{/if}
{#if openDeleteModal}
	<dialog class="modal modal-open">
		<DeleteModal on:close={closeDeleteModal} on:confirm={deleteRecord} />
	</dialog>
{/if}
{#if _new == true}
	<Modal on:close={() => updateData()}>
		<Form {edit} on:message={OpenAlertMessage} on:close={() => updateData()} />
	</Modal>
{/if}
{#if edit == true}
	<Modal on:close={() => updateData()}>
		<Form {edit} {item} on:message={OpenAlertMessage} on:close={() => updateData()} />
	</Modal>
{/if}
{#if data}
	<div class="overflow-x-auto">
		<table class="table w-full">
			<thead>
				<tr>
					<th class="text-center text-lg">
						
						<div class="flex items-center">
							id
							<button on:click={() => sortData('id')}
								><SortIcon/></button
							>
						</div>
					</th>
					<th class="text-center text-lg" on:click={() => sortData('product_name')}>
						<div class="flex items-center justify-center">
							Nombre
							<button><SortIcon/></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Descripcion
							<button on:click={() => sortData('product_desc')}
								><SortIcon/></button
							>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Categoría
							<button on:click={() => sortData('category_id')}
								><SortIcon/></button
							>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Marca
							<button on:click={() => sortData('brand_id')}
								><SortIcon/></button
							>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Cantidad
							<button on:click={() => sortData('product_quantity')}
								><SortIcon/></button
							>
						</div>
					</th>
					{#if user.permissions != undefined && user.permissions.includes('products.create')}
						<th>
							<button class="btn btn-primary" on:click={() => (_new = true)}>Agregar</button>
						</th>
					{/if}
				</tr>
			</thead>
			<tbody>
				{#each products as product, i (product.id)}
					<tr class="hover">
						<td>{product.id}</td>
						<td class="text-center">{product.product_name}</td>
						<td class="text-center">{product.product_desc}</td>
						<td class="text-center">{product.cat_desc}</td>
						<td class="text-center">{product.brand_name}</td>
						<td class="text-center">{product.product_quantity}</td>
						{#if user.permissions != undefined && user.permissions.includes('products.show')}
							<td>
								<button class="btn btn-info" use:inertia={{ href: `/products/${product.id}` }}>Mostrar</button>
							</td>
						{/if}
						{#if user.permissions != undefined && user.permissions.includes('products.update')}
							<td>
								<button class="btn btn-warning" on:click={() => openEditModal(product)}>Editar</button>
							</td>
						{/if}
						{#if user.permissions != undefined && user.permissions.includes('products.destroy')}
							<td>
								<button class="btn btn-secondary" on:click={() => OpenDeleteModal(product.id)}
									>Eliminar</button
								>
							</td>
						{/if}
					</tr>
				{/each}
			</tbody>
		</table>
		<Pagination
			current_page={current_page?current_page:1}
			{total_pages}
			{items_per_page}
			on:page={handlePage}
			on:row={handleRowsPerPage}
		/>
	</div>
{/if}
