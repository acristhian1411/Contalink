<script>
	import { router } from '@inertiajs/svelte';
	import { Pagination, DeleteModal } from '@components/utilities/';
	import { Alert } from '@components/Alerts/';
	import { SearchIcon, SortIcon } from '@components/Icons/';

	export let user;
	export let items;
	export let filters = {};

	let openAlert = false;
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
		router.get('/refunds', { ...filters, ...params }, { preserveState: true, replace });
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
		router.delete(`/refunds/${id}`, {
			onSuccess: () => {
				OpenAlertMessage({ detail: { type: 'delete', message: 'Devolución eliminada con éxito' } });
				openDeleteModal = false;
			},
		});
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

<h3 class="mb-4 text-center text-2xl">Devoluciones</h3>
<div class="flex justify-center gap-4">
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
	{#if user?.permissions?.includes('refunds.create')}
		<button class="btn btn-primary" on:click={() => router.visit('/refunds/create')}>Nueva Devolución</button>
	{/if}
</div>
{#if openAlert}
	<Alert {alertMessage} {alertType} on:close={closeAlert} />
{/if}
{#if openDeleteModal}
	<dialog class="modal modal-open">
		<DeleteModal
			on:close={closeDeleteModal}
			on:confirm={deleteRecord}
			message="¿Estás seguro de que deseas eliminar esta devolución? Esta acción revertirá el inventario."
		/>
	</dialog>
{/if}
{#if data.length}
	<div class="overflow-x-auto">
		<table class="table w-full">
			<thead>
				<tr>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							#
							<button on:click={() => sortData('id')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">Cliente</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Fecha
							<button on:click={() => sortData('refund_date')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Estado
							<button on:click={() => sortData('refund_status')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">Observación</th>
				</tr>
			</thead>
			<tbody>
				{#each data as refund (refund.id)}
					<tr class="hover">
						<td>{refund.id}</td>
						<td class="text-center">{refund.sale?.person?.person_fname} {refund.sale?.person?.person_lastname}</td>
						<td class="text-center">{refund.refund_date}</td>
						<td class="text-center">{refund.refund_status}</td>
						<td class="text-center">{refund.refund_obs}</td>
						<td>
							{#if user?.permissions?.includes('refunds.show')}
								<button class="btn btn-info" on:click={() => router.visit(`/refunds/${refund.id}`)}>Mostrar</button>
							{/if}
							{#if user?.permissions?.includes('refunds.destroy')}
								<button class="btn btn-secondary" on:click={() => OpenDeleteModal(refund.id)}>Eliminar</button>
							{/if}
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
