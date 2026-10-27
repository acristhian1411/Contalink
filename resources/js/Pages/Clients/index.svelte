
<script>
	// @ts-nocheck
	import { router } from '@inertiajs/svelte';
	import {Pagination, DeleteModal, Modal} from '@components/utilities/';
	import {Alert} from '@components/Alerts/';
	import {SearchIcon, SortIcon} from '@components/Icons/';
	import Form from './form.svelte';
	export let items;
	export let filters = {};
	export let user;
	export let staticData;
	let openAlert = false;
	let _new = false;
	let edit = false;
	let item = null;
	let search_param = filters.search ?? '';
	let openDeleteModal = false;
	let alertMessage = '';
	let alertType = '';
	let id = 0;
	let orderBy = filters.sort_by ?? 'person_fname';
	let order = filters.order ?? 'asc';
	let items_per_page = String(items.per_page);

	function updateData() {
		router.reload({ only: ['items', 'filters'] });
		closeModal();
	}

	function visit(params = {}) {
		const query = {
			search: search_param || undefined,
			sort_by: orderBy,
			order,
			per_page: items_per_page,
			page: items.current_page,
			...params,
		};

		router.get(window.location.pathname, query, {
			preserveState: true,
			preserveScroll: true,
			replace: true,
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

	function deleteRecord() {
		router.delete(`${window.location.pathname}/${id}`, {
			onSuccess: () => {
				OpenAlertMessage({ detail: { type: 'delete', message: 'Registro eliminado correctamente' } });
				closeDeleteModal();
			},
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
		router.reload({ only: ['items', 'filters'] });
	}

	function sortData(param) {
		orderBy = param;
		order = order === 'asc' ? 'desc' : 'asc';
		visit({ page: 1, sort_by: orderBy, order });
	}

	function OpenDeleteModal(data) {
		id = data;
		openDeleteModal = true;
	}

	function handleRowsPerPage(event) {
		items_per_page = event.detail.value;
		visit({ page: 1, per_page: items_per_page });
	}

	function handlePage(event) {
		visit({ page: event.detail.value });
	}

	function search() {
		visit({ page: 1 });
	}
</script>

<h3 class="mb-4 text-center text-2xl">Clientes</h3>
<div class="flex justify-center">
	<label class="input input-bordered flex items-center gap-2">
		<input type="text" class="grow" placeholder="Search" bind:value={search_param} on:change={search} />
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
		<Form {edit} {staticData} on:message={OpenAlertMessage} on:close={() => updateData()} />
	</Modal>
{/if}
{#if edit == true}
	<Modal on:close={() => updateData()}>
		<Form {edit} {item} {staticData} on:message={OpenAlertMessage} on:close={() => updateData()} />
	</Modal>
{/if}
{#if items}
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
					<th class="text-center text-lg" >
						<div class="flex items-center justify-center">
							Nombre
							<button on:click={() => sortData('person_idnumber')}>
								<SortIcon/>
							</button> 
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Descripcion
							<button on:click={() => sortData('person_fname')}
								><SortIcon/></button
							>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Codigo
							<button on:click={() => sortData('person_lastname')}
								><SortIcon/></button
							>
						</div>
					</th>
					{#if user.permissions != undefined && user.permissions.includes('clients.create')}
						<th>
							<button class="btn btn-primary" on:click={() => (_new = true)}>
								Agregar
							</button>
						</th>
					{/if}
				</tr>
			</thead>
			<tbody>
				{#each items.data as person (person.id)}
					<tr class="hover">
						<td>{person.id}</td>
						<td class="text-center">{person.person_idnumber}</td>
						<td class="text-center">{person.person_fname}</td>
						<td class="text-center">{person.person_lastname}</td>
						{#if user.permissions != undefined && user.permissions.includes('clients.show')}
							<td>
								<button class="btn btn-info" on:click={() => router.visit(`/persons/clients/${person.id}`)}>
									Mostrar
								</button>
							</td>
						{/if}
						{#if user.permissions != undefined && user.permissions.includes('clients.update')}
							<td>
								<button class="btn btn-warning" on:click={() => openEditModal(person)}>
									Editar
								</button>
							</td>
						{/if}
						{#if user.permissions != undefined && user.permissions.includes('clients.destroy')}
							<td>
								<button class="btn btn-secondary" on:click={() => OpenDeleteModal(person.id)}>
									Eliminar
								</button>
							</td>	
						{/if}
					</tr>
				{/each}
			</tbody>
		</table>
		<Pagination
			current_page={items.current_page}
			total_pages={items.last_page}
			{items_per_page}
			on:page={handlePage}
			on:row={handleRowsPerPage}
		/>
	</div>
{/if}
