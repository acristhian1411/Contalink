<script>
	import { router } from '@inertiajs/svelte';
	import { Pagination, DeleteModal, Modal } from '@components/utilities/';
	import { Alert } from '@components/Alerts/';
	import { SearchIcon, SortIcon } from '@components/Icons/';
	import Form from './form.svelte';

	export let user;
	export let items;
	export let filters = {};
	export let staticData = null;

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
		router.get('/products', { ...filters, ...params }, { preserveState: true, replace });
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
		router.delete(`/products/${id}`, {
			onSuccess: () => {
				OpenAlertMessage({ detail: { type: 'delete', message: 'Producto eliminado con éxito' } });
				openDeleteModal = false;
			},
			onError: () => {
				openDeleteModal = false;
			},
		});
	}

	function openEditModal(row) {
		edit = true;
		item = row;
		_new = false;
	}

	function closeModal() {
		edit = false;
		_new = false;
	}

	function closeDeleteModal() {
		openDeleteModal = false;
	}

	function sortData(param) {
		const nextOrder = orderBy === param && order === 'asc' ? 'desc' : 'asc';
		refresh({ sort_by: param, order: nextOrder });
	}

	function OpenDeleteModal(rowId) {
		id = rowId;
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

<h3 class="mb-4 text-center text-2xl">Productos</h3>
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
	{#if user?.permissions?.includes('products.create')}
		<button class="btn btn-primary" on:click={() => (_new = true)}>Agregar</button>
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
			message="¿Estás seguro de que deseas eliminar este producto?"
		/>
	</dialog>
{/if}
{#if _new}
	<Modal on:close={() => updateData()}>
		<Form {staticData} on:message={OpenAlertMessage} on:close={() => updateData()} />
	</Modal>
{/if}
{#if edit}
	<Modal on:close={() => updateData()}>
		<Form {edit} {item} {staticData} on:message={OpenAlertMessage} on:close={() => updateData()} />
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
							Nombre
							<button on:click={() => sortData('product_name')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Descripción
							<button on:click={() => sortData('product_desc')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Categoría
							<button on:click={() => sortData('category_id')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Marca
							<button on:click={() => sortData('brand_id')}><SortIcon /></button>
						</div>
					</th>
					<th class="text-center text-lg">
						<div class="flex items-center justify-center">
							Cantidad
							<button on:click={() => sortData('product_quantity')}><SortIcon /></button>
						</div>
					</th>
				</tr>
			</thead>
			<tbody>
				{#each data as product (product.id)}
					<tr class="hover">
						<td>{product.id}</td>
						<td class="text-center">{product.product_name}</td>
						<td class="text-center">{product.product_desc}</td>
						<td class="text-center">{product.cat_desc}</td>
						<td class="text-center">{product.brand_name}</td>
						<td class="text-center">{product.product_quantity}</td>
						{#if user?.permissions?.includes('products.show')}
							<td>
								<button class="btn btn-info" on:click={() => router.visit(`/products/${product.id}`)}>Mostrar</button>
							</td>
						{/if}
						{#if user?.permissions?.includes('products.update')}
							<td>
								<button class="btn btn-warning" on:click={() => openEditModal(product)}>Editar</button>
							</td>
						{/if}
						{#if user?.permissions?.includes('products.destroy')}
							<td>
								<button class="btn btn-secondary" on:click={() => OpenDeleteModal(product.id)}>Eliminar</button>
							</td>
						{/if}
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
{:else}
	<p class="text-center text-gray-500 mt-8">No hay registros</p>
{/if}
