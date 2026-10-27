<script>
	import { router } from '@inertiajs/svelte';
	import { Pagination, DeleteModal, Modal } from '@components/utilities/';
	import { Alert } from '@components/Alerts/';
	import { SearchIcon, SortIcon } from '@components/Icons/';
	import Form from './form.svelte';

	export let items;
	export let filters = {};
	export let tillTypes = [];

	let openAlert = false;
	let _new = false;
	let edit = false;
	let item = null;
	let openDeleteModal = false;
	let alertMessage = '';
	let alertType = '';
	let id = 0;

	$: data = items?.data ?? [];
	$: current_page = items?.current_page ?? 1;
	$: total_pages = items?.last_page ?? 1;
	$: items_per_page = items?.per_page ? String(items.per_page) : '10';
	$: orderBy = filters?.sort_by ?? 'id';
	$: order = filters?.order ?? 'asc';

	function refresh(params = {}, replace = true) {
		router.get('/tills', { ...filters, ...params }, { preserveState: true, replace });
	}

	function updateData() {
		router.reload({ only: ['items'] });
		closeModal();
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
		router.delete(`/tills/${id}`, {
			onSuccess: () => {
				OpenAlertMessage({ detail: { type: 'delete', message: 'Caja eliminada con éxito' } });
				openDeleteModal = false;
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
	}

	function sortData(param) {
		const nextOrder = orderBy === param && order === 'asc' ? 'desc' : 'asc';
		refresh({ sort_by: param, order: nextOrder });
	}

	function OpenDeleteModal(data) {
		id = data;
		openDeleteModal = true;
	}

	function handleRowsPerPage(event) {
		refresh({ per_page: event.detail.value, page: 1 });
	}

	function handlePage(event) {
		refresh({ page: event.detail.value }, false);
	}

	function search(event) {
		refresh({ search: event.target.value, page: 1 });
	}
</script>

<h3 class="mb-4 text-center text-2xl">Cajas</h3>
<div class="flex justify-center">
	<label class="input input-bordered flex items-center gap-2">
		<input
			type="text"
			class="grow"
			placeholder="Buscar"
			value={filters.search ?? ''}
			on:change={search}
		/>
		<SearchIcon />
	</label>
</div>
{#if openAlert}
	<Alert {alertMessage} {alertType} on:close={closeAlert} />
{/if}
{#if openDeleteModal}
	<dialog class="modal modal-open">
		<DeleteModal
			on:close={closeDeleteModal}
			on:confirm={deleteRecord}
			message="¿Estás seguro de que deseas eliminar esta caja?"
		/>
	</dialog>
{/if}
{#if _new == true}
	<Modal on:close={() => updateData()}>
		<Form {edit} {tillTypes} on:message={OpenAlertMessage} on:close={() => updateData()} />
	</Modal>
{/if}
{#if edit == true}
	<Modal on:close={() => updateData()}>
		<Form {edit} {item} {tillTypes} on:message={OpenAlertMessage} on:close={() => updateData()} />
	</Modal>
{/if}
{#if data.length}
	<div class="overflow-x-auto">
		<table class="table w-full">
			<thead>
				<tr>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							id
							<button on:click={() => sortData('id')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Descripción
							<button on:click={() => sortData('till_name')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Tipo
							<button on:click={() => sortData('t_type_id')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Estado
							<button on:click={() => sortData('till_status')}><SortIcon /></button>
						</div>
					</th>
					<th><button class="btn btn-primary" on:click={() => (_new = true)}>Agregar</button></th>
				</tr>
			</thead>
			<tbody>
				{#each data as till (till.id)}
					<tr class="hover">
						<td>{till.id}</td>
						<td class="text-center">{till.till_name}</td>
						<td class="text-center">{till.till_type_desc}</td>
						<td class="text-center">{till.till_status == true ? 'Abierto' : 'Cerrado'}</td>
						<td>
							<button class="btn btn-info" on:click={() => router.visit(`/tills/${till.id}`)}>Mostrar</button>
						</td>
						<td>
							<button class="btn btn-warning" on:click={() => openEditModal(till)}>Editar</button>
						</td>
						<td>
							<button class="btn btn-secondary" on:click={() => OpenDeleteModal(till.id)}>Eliminar</button>
						</td>
					</tr>
				{/each}
			</tbody>
		</table>
		<Pagination
			{current_page}
			{total_pages}
			{items_per_page}
			on:page={handlePage}
			on:row={handleRowsPerPage}
		/>
	</div>
{/if}
