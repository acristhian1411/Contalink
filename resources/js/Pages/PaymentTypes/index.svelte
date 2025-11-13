<script>
    // @ts-nocheck
    import { onMount } from "svelte";
    import { inertia } from "@inertiajs/inertia-svelte";
    import axios from "axios";
    import { Pagination, DeleteModal, Modal } from "@components/utilities/";
    import { Alert, ErrorAlert } from "@components/Alerts/";
    import { SearchIcon, SortIcon } from "@components/Icons/";
    import Form from "./form.svelte";
    export let user;
    export let appUrl;
    export let data;
    let payment_types = [];
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
    let orderBy = "p_type_desc";
    let order = "asc";
    let total_pages;
    let total_items;
    let current_page = 1;
    let items_per_page = "10";
    let url = `/payment-types`;

    function updateData() {
        fetchData(current_page, items_per_page, orderBy, order);
        closeModal();
    }

    function assignData(data) {
        payment_types = data.data;
        current_page = data.currentPage;
        total_items = data.per_page;
        total_pages = data.last_page;
    }

    async function fetchData(
        page = current_page,
        rows = items_per_page,
        sort = orderBy,
        order = order,
    ) {
        let ur = `${url}?page=${page}&per_page=${rows}&sort=${sort}&order=${order}`;
        axios
            .get(url)
            .then((response) => {
                assignData(response.data);
            })
            .catch((err) => {
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response.data.message,
                    },
                };
            });
    }

    function closeAlert() {
        openAlert = false;
    }

    function OpenAlertMessage(event) {
        console.log("details desde index", event.detail);
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
        axios.delete(`/payment-types/${id}`, config).then((res) => {
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
        if (order == "asc") {
            order = "desc";
        } else {
            order = "asc";
        }
        fetchData(current_page, items_per_page, orderBy, order);
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
        if (search_param == "") {
            url = `${appUrl}/payment-types?`;
        } else {
            url = `${appUrl}/payment-types?payment_type_desc=${search_param}&`;
        }
        fetchData(1, items_per_page, orderBy, order);
    }
    onMount(async () => {
        assignData(data);
    });
</script>

<svelte:head>
    <title>Tipos de pago</title>
</svelte:head>
{#if error}
    <ErrorAlert message={error} on:clearError={ClearError} />
{/if}
<h3 class="mb-4 text-center text-2xl">Tipos de pago</h3>
<div class="flex justify-center">
    <label class="input input-bordered flex items-center gap-2">
        <input
            type="text"
            class="grow"
            placeholder="Search"
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
        <DeleteModal on:close={closeDeleteModal} on:confirm={deleteRecord} />
    </dialog>
{/if}
{#if _new == true}
    <Modal on:close={() => updateData()}>
        <Form
            {edit}
            on:message={OpenAlertMessage}
            on:close={() => updateData()}
        />
    </Modal>
{/if}
{#if edit == true}
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
                    <th class="text-center text-lg">
                        <div class="flex items-center">
                            id
                            <button on:click={() => sortData("id")}
                                ><SortIcon /></button
                            >
                        </div>
                    </th>
                    <th class="text-center text-lg">
                        <div class="flex items-center justify-center">
                            Descripcion
                            <button on:click={() => sortData("paymentTypeDesc")}
                                ><SortIcon /></button
                            >
                        </div>
                    </th>
                    {#if user.permissions != undefined && user.permissions.includes("paymenttypes.create")}
                        <th>
                            <button
                                class="btn btn-primary"
                                on:click={() => (_new = true)}>Agregar</button
                            >
                        </th>
                    {/if}
                </tr>
            </thead>
            <tbody>
                {#each payment_types as payment_type, i (payment_type.id)}
                    <tr class="hover">
                        <td>{payment_type.id}</td>
                        <td class="text-center"
                            >{payment_type.paymentTypeDesc}</td
                        >
                        {#if user.permissions != undefined && user.permissions.includes("paymenttypes.show")}
                            <td>
                                <button
                                    class="btn btn-info"
                                    use:inertia={{
                                        href: `/payment-types/${payment_type.id}`,
                                    }}>Mostrar</button
                                >
                            </td>
                        {/if}
                        {#if user.permissions != undefined && user.permissions.includes("paymenttypes.update")}
                            <td>
                                <button
                                    class="btn btn-warning"
                                    on:click={() => openEditModal(payment_type)}
                                    >Editar</button
                                >
                            </td>
                        {/if}
                        {#if user.permissions != undefined && user.permissions.includes("paymenttypes.destroy")}
                            <td>
                                <button
                                    class="btn btn-secondary"
                                    on:click={() =>
                                        OpenDeleteModal(payment_type.id)}
                                    >Eliminar</button
                                >
                            </td>
                        {/if}
                    </tr>
                {/each}
            </tbody>
        </table>
        <Pagination
            current_page={current_page ? current_page : 1}
            {total_pages}
            {items_per_page}
            on:page={handlePage}
            on:row={handleRowsPerPage}
        />
    </div>
{/if}
