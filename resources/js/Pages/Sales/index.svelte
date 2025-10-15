<script>
    import { onMount } from "svelte";
    import { inertia } from "@inertiajs/inertia-svelte";
    import axios from "axios";
    import { Pagination, DeleteModal, Modal } from "@components/utilities/";
    import { Alert, ErrorAlert } from "@components/Alerts/";
    import { SearchIcon, SortIcon } from "@components/Icons/";
    import Form from "./form.svelte";

    export let user;
    export let appUrl;
    let data = [];
    let error = null;
    let openAlert = false;
    let _new = false;
    let edit = false;
    let item = null;
    let search_param = "";
    let openDeleteModal = false;
    let alertMessage = "";
    let alertType = "";
    let id = 0;
    let orderBy = "person_id";
    let order = "asc";
    let total_pages;
    let total_items;
    let current_page = 1;
    let items_per_page = "10";
    let url = `${appUrl}/api/sales?`;

    function updateData() {
        fetchData();
        closeModal();
    }

    async function fetchData(page = current_page, rows = items_per_page) {
        let token = "";
        let config = {
            headers: {
                authorization: `token: ${token}`,
            },
        };
        axios
            .get(
                `${url}sort_by=${orderBy}&order=${order}&page=${page}&per_page=${rows}`,
                config,
            )
            .then((response) => {
                data = response.data.data;
                current_page = response.data.currentPage;
                total_items = response.data.per_page;
                total_pages = response.data.last_page;
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

    function ClearError() {
        error = null;
    }

    function deleteRecord() {
        let token = "";
        let config = {
            headers: {
                authorization: `token: ${token}`,
            },
        };
        axios.delete(`${appUrl}/api/sales/${id}`, config).then((res) => {
            let detail = {
                detail: {
                    type: "delete",
                    message: res.data.message,
                },
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
        fetchData();
    }

    function sortData(param) {
        orderBy = param;
        order = order == "asc" ? "desc" : "asc";
        fetchData(current_page, items_per_page);
    }

    function OpenDeleteModal(data) {
        id = data;
        openDeleteModal = true;
    }

    function handleRowsPerPage(event) {
        items_per_page = event.detail.value;
        fetchData(current_page, event.detail.value);
    }

    function handlePage(event) {
        current_page = event.detail.value;
        fetchData(event.detail.value, items_per_page);
    }

    function search(event) {
        search_param = event.target.value;
        url =
            search_param === ""
                ? `${appUrl}/api/sales?`
                : `${appUrl}/api/sales?person_id=${search_param}&`;
        fetchData(1, items_per_page);
    }

    onMount(async () => {
        fetchData();
    });
</script>

{#if error}
    <ErrorAlert message={error} on:clearError={ClearError} />
{/if}
<h3 class="mb-4 text-center text-2xl">Ventas</h3>
<div class="flex justify-center">
    <label class="input input-bordered flex items-center gap-2">
        <input
            type="text"
            class="grow"
            placeholder="Buscar"
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
            message="¿Estás seguro de que deseas eliminar esta venta? Esta acción actualizará el inventario."
        />
    </dialog>
{/if}
{#if _new}
    <Modal on:close={() => updateData()}>
        <Form
            {edit}
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
            on:message={OpenAlertMessage}
            on:close={() => updateData()}
        />
    </Modal>
{/if}
{#if data}
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th class="text-center text-lg">#</th>
                    <th class="text-center text-lg">
                        <div class="flex items-center justify-center">
                            Cliente
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
                            <button on:click={() => sortData("sale_status")}>
                                <SortIcon />
                            </button>
                        </div>
                    </th>
                    {#if user.permissions != undefined && user.permissions.includes("sales.create")}
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
                            >{item.person.person_fname +
                                " " +
                                item.person.person_lastname}</td
                        >
                        <td class="text-center">{item.sale_date}</td>
                        <td class="text-center"
                            >{item.sale_status || "Pendiente"}</td
                        >
                        {#if user.permissions != undefined && user.permissions.includes("sales.show")}
                            <td
                                ><button
                                    class="btn btn-info"
                                    use:inertia={{ href: `/sales/${item.id}` }}
                                    >Mostrar</button
                                ></td
                            >
                        {/if}
                        {#if user.permissions != undefined && user.permissions.includes("sales.update")}
                            <td
                                ><button
                                    class="btn btn-warning"
                                    on:click={() => openEditModal(item)}
                                    >Editar</button
                                ></td
                            >
                        {/if}
                        {#if user.permissions != undefined && user.permissions.includes("sales.destroy")}
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
