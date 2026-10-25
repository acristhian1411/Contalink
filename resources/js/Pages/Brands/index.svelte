
<script>
	// @ts-nocheck
	import { onMount } from 'svelte';
	import { router } from '@inertiajs/svelte';
	// import { isLoggedIn, getToken} from '../../services/authservice'
	// import {goto} from '$app/navigation';
	import axios from 'axios';
	import {Pagination, DeleteModal, Modal} from '@components/utilities/';
	import {Alert, ErrorAlert} from '@components/Alerts/';
	import {SearchIcon, SortIcon} from '@components/Icons/';
	import Form from './form.svelte';
	// import { appUrl } from '$env/static/public';
	export let user
	export let data;
	let brands = [];
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
	let orderBy = 'brand_desc';
	let order = 'asc';
	let total_pages;
	let total_items;
	let current_page = 1;
	let items_per_page = '10';
	let url = `/brands?`;

	function updateData() {
		fetchData(current_page, items_per_page, orderBy, order);
		closeModal();
	}

	async function assignData(data) {
		brands = data.data;
		current_page = data.currentPage;
		total_items = data.per_page;
		total_pages = data.last_page;
	}

	function fetchData( page = current_page, rows = items_per_page, sort_by = orderBy, order = order) {
		axios.get(`${url}sort_by=${sort_by}&order=${order}&page=${page}&per_page=${rows}`).then((response) => {
			assignData(response.data);
		}).catch((err) => {
			error = err.request.response;
		});
	}

	function closeAlert() {
		openAlert = false;
	}

	function OpenAlertMessage(event) {
		console.log('details desde index',event.detail);
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
			headers: {},
		}
		axios.delete(`/brands/${id}`, config).then((res) => {
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
		fetchData(current_page,items_per_page,orderBy,order);
	}
	function sortData(param) {
		orderBy = param;
		if (order == 'asc') {
			order = 'desc';
		} else {
			order = 'asc';
		}
		fetchData(current_page, items_per_page, orderBy, order);
	}
	function OpenDeleteModal(data) {
		id = data;
		openDeleteModal = true;
	}
	function handleRowsPerPage(event) {
		items_per_page = event.detail.value;
		fetchData(current_page, event.detail.value,orderBy,order);
	}
	function handlePage(event) {
		current_page = event.detail.value;
		fetchData(event.detail.value, items_per_page,orderBy,order);
	}
	function search(event) {
		search_param = event.target.value;
		if (search_param == '') {
			url = `/brands?`;
		} else {
			url = `/brands?brand_name=${search_param}&`;
		}
		fetchData(1, items_per_page,orderBy,order);
	}
	onMount(async () => {
		assignData(data);
	});
</script>

{#if error}
	<ErrorAlert message={error} on:clearError={ClearError} />
{/if}
<h3 class="mb-4 text-center text-2xl">Marcas</h3>
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
					<th class="text-center text-lg">
						<div class="flex items-center">
							Nombre
							<button on:click={() => sortData('brand_name')}>
								<SortIcon/>
							</button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Descripcion
							<button on:click={() => sortData('brand_desc')}
								><SortIcon/></button
							>
						</div>
					</th>
					{#if user.permissions != undefined && user.permissions.includes('brands.create')}
						<th>
							<button class="btn btn-primary" on:click={() => (_new = true)}>Agregar</button>
						</th>
					{/if}
				</tr>
			</thead>
			<tbody>
				{#each brands as brand, i (brand.id)}
					<tr class="hover">
						<td>{brand.id}</td>
						<td class="text-center">{brand.brand_name}</td>
						<td class="text-center">{brand.brand_desc}</td>
						{#if user.permissions != undefined && user.permissions.includes('brands.show')}
							<td>
								<button class="btn btn-info" on:click={() => router.visit(`/brands/${brand.id}`)}>Mostrar</button>
							</td>
						{/if}
						{#if user.permissions != undefined && user.permissions.includes('brands.update')}
							<td>
								<button class="btn btn-warning" on:click={() => openEditModal(brand)}>Editar</button>
							</td>
						{/if}
						{#if user.permissions != undefined && user.permissions.includes('brands.destroy')}
							<td>
								<button class="btn btn-secondary" on:click={() => OpenDeleteModal(brand.id)}
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
