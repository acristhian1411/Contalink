<script>
    import { router } from '@inertiajs/svelte';
    import { Pagination, DeleteModal, Modal } from "@components/utilities/";
    import { Alert, ErrorAlert } from "@components/Alerts/";
    import { SearchIcon, SortIcon } from "@components/Icons/";
    import Form from "./form.svelte";

    export let user;
    export let items;
    export let filters = {};
    export let staticData;
    export let userContext;
    export let formConfig;

    let openAlert = false;
    let _new = false;
    let edit = false;
    let item = null;
    let openDeleteModal = false;
    let alertMessage = "";
    let alertType = "";
    let id = 0;

    $: data = items?.data ?? [];
    $: current_page = items?.current_page ?? 1;
    $: total_pages = items?.last_page ?? 1;
    $: items_per_page = items?.per_page ? String(items.per_page) : "10";
    $: orderBy = filters?.sort_by ?? "id";
    $: order = filters?.order ?? "asc";

    function refresh(params = {}, replace = true) {
        router.get('/purchases', { ...filters, ...params }, { preserveState: true, replace });
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
        router.delete(`/purchases/${id}`, {
            onSuccess: () => {
                OpenAlertMessage({ detail: { type: "delete", message: "Compra eliminada con éxito" } });
                openDeleteModal = false;
                router.reload({ only: ['items'] });
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

<h3 class="mb-4 text-center text-2xl">Compras</h3>
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
            message="¿Estás seguro de que deseas eliminar esta compra? Esta acción actualizará el inventario."
        />
    </dialog>
{/if}
{#if _new}
    <Modal on:close={() => updateData()}>
        <Form
            {edit}
            {staticData}
            {userContext}
            {formConfig}
            on:message={OpenAlertMessage}
            on:close={() => updateData()}
        />
    </Modal>
{/if}
{#if edit}
    <Modal on:close={() => updateData()}>
        <Form
            {edit}
            {item}
            {staticData}
            {userContext}
            {formConfig}
            on:message={OpenAlertMessage}
            on:close={() => updateData()}
        />
    </Modal>
{/if}
{#if data.length}
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th class="text-center text-lg">#</th>
                    <th class="text-center text-lg">
                        <div class="flex items-center justify-center">
                            Proveedor
                            <button on:click={() => sortData("person_id")}>
                                <SortIcon />
                            </button>
                        </div>
                    </th>
                    <th class="text-center text-lg">
                        <div class="flex items-center justify-center">
                            Fecha
                            <button on:click={() => sortData("purchase_date")}>
                                <SortIcon />
                            </button>
                        </div>
                    </th>
                    <th class="text-center text-lg">
                        <div class="flex items-center justify-center">
                            Estado
                            <button on:click={() => sortData("purchase_status")}>
                                <SortIcon />
                            </button>
                        </div>
                    </th>
                    {#if user.permissions != undefined && user.permissions.includes("purchases.create")}
                        <th
                            ><button
                                class="btn btn-primary"
                                on:click={() => (_new = true)}>Agregar</button
                            ></th
                        >
                    {/if}
                </tr>
            </thead>
            <tbody>
                {#each data as item, i (item.id)}
                    <tr class="hover">
                        <td>{i + 1}</td>
                        <td class="text-center"
                            >{item.person?.person_fname +
                                " " +
                                item.person?.person_lastname}</td
                        >
                        <td class="text-center">{item.purchase_date}</td>
                        <td class="text-center"
                            >{item.purchase_status == true ? "Procesado" : "Pendiente"}</td
                        >
                        {#if user.permissions != undefined && user.permissions.includes("purchases.show")}
                            <td
                                ><button
                                    class="btn btn-info"
                                    on:click={() => router.visit(`/purchases/${item.id}`)}
                                    >Mostrar</button
                                ></td
                            >
                        {/if}
                        {#if user.permissions != undefined && user.permissions.includes("purchases.update")}
                            <td
                                ><button
                                    class="btn btn-warning"
                                    on:click={() => openEditModal(item)}
                                    >Editar</button
                                ></td
                            >
                        {/if}
                        {#if user.permissions != undefined && user.permissions.includes("purchases.destroy")}
                            <td
                                ><button
                                    class="btn btn-secondary"
                                    on:click={() => OpenDeleteModal(item.id)}
                                    >Eliminar</button
                                ></td
                            >
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
{/if}
