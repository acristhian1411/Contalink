
<script>
	// @ts-nocheck
	import { onMount } from 'svelte';
	import { inertia } from '@inertiajs/inertia-svelte';
	import axios from 'axios';
	import {Pagination, DeleteModal, Modal} from '@components/utilities/';
	import {Alert, ErrorAlert} from '@components/Alerts/';
	import {SearchIcon, SortIcon} from '@components/Icons/';
	import Form from './form.svelte';
	export let user
    export let appUrl
	export let data = [];
	let tilltypes = [];
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
	let url = `${appUrl}/till-types?`;
	function updateData() {
		fetchData(current_page, items_per_page,orderBy,order );
		closeModal();
	}

	function assignData(data) {
		tilltypes = data.data;
		current_page = data.current_page;
		total_items = data.per_page;
		total_pages = data.last_page;
	}
	async function fetchData(
        page = current_page,
        rows = items_per_page,
        sort = orderBy,
        order = order,
    ) {
        let ur = `${url}page=${page}&per_page=${rows}&order=${order}&sort_by=${orderBy}`;
        axios
            .get(ur)
            .then((response) => {
                assignData(response.data);
            })
            .catch((err) => {
                console.log("error", err);
                // let detail = {
                //     detail: {
                //         type: "delete",
                //         message: err.response.data.message,
                //     },
                // };
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
		axios.delete(`${appUrl}/till-types/${id}`, config).then((res) => {
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
		fetchData(current_page, items_per_page,orderBy,order );
	}
	function sortData(param) {
		orderBy = param;
		if (order == 'asc') {
			order = 'desc';
		} else {
			order = 'asc';
		}
		axios.get('/till-types?page='+current_page+'&per_page='+items_per_page+'&order='+order+'&sort_by='+orderBy).then((response) => {
			assignData(response.data)
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		})
	}
	function OpenDeleteModal(data) {
		id = data;
		openDeleteModal = true;
	}
	function handleRowsPerPage(event) {
		items_per_page = event.detail.value;
		axios.get('/till-types?page='+current_page+'&per_page='+items_per_page+'&order='+order+'&sort_by='+orderBy).then((response) => {
			assignData(response.data)
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}
	function handlePage(event) {
		current_page = event.detail.value;
		axios.get('/till-types?page='+current_page+'&per_page='+items_per_page+'&order='+order+'&sort_by='+orderBy).then((response) => {
			assignData(response.data)
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}
	function search(event) {
		search_param = event.target.value;
		if (search_param == '') {
			url = `/till-types?`;
		} else {
			url = `/till-types?till_type_desc=${search_param}&`;
		}
		axios.get(url).then((response) => {
			assignData(response.data);
		}).catch((err) => {
			if(err.response.data.message){
				let detail = {
					detail: {
						type: 'delete',
						message: err.response.data.message
					}
				};
			}
		});
	}
	onMount(async () => {
		assignData(data);
	});
</script>
<svelte:head>
	<title>Tipos de cajas</title>
</svelte:head>
{#if error}
	<ErrorAlert message={error} on:clearError={ClearError} />
{/if}
<h3 class="mb-4 text-center text-2xl">Tipos de Caja</h3>
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
					<th class="text-center text-lg" on:click={() => sortData('till_type_desc')}>
						<div class="flex items-center justify-center">
							Descripcion
							<button><SortIcon/></button>
						</div>
					</th>
					{#if user.permissions != undefined && user.permissions.includes('tilltypes.create')}
						<th>
							<button class="btn btn-primary" on:click={() => (_new = true)}>Agregar</button>
						</th>
					{/if}
				</tr>
			</thead>
			<tbody>
				{#each tilltypes as tilltype, i (tilltype.id)}
					<tr class="hover">
						<td>{tilltype.id}</td>
						<td class="text-center">{tilltype.till_type_desc}</td>
						{#if user.permissions != undefined && user.permissions.includes('tilltypes.show')}
							<td>
								<button class="btn btn-info" use:inertia={{ href: `/till-types/${tilltype.id}` }}>
									Mostrar
								</button>
							</td>
						{/if}
						{#if user.permissions != undefined && user.permissions.includes('tilltypes.update')}
							<td>
								<button class="btn btn-warning" on:click={() => openEditModal(tilltype)}>
									Editar
								</button>
							</td>
						{/if}
						{#if user.permissions != undefined && user.permissions.includes('tilltypes.destroy')}
							<td>
								<button class="btn btn-secondary" on:click={() => OpenDeleteModal(tilltype.id)}>
									Eliminar
								</button>
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
