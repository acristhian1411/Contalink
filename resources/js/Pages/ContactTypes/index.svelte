
<script>
    import { onMount } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import axios from 'axios';
    import {Pagination, DeleteModal, Modal} from '@components/utilities/';
    import {Alert, ErrorAlert} from '@components/Alerts/';
    import {SearchIcon, SortIcon} from '@components/Icons/';
    import Form from './form.svelte';
    
    export let user;
    export let data;
    let contacttypes = [];
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
    let orderBy = 'cont_type_desc';
    let order = 'asc';
    let total_pages;
    let total_items;
    let current_page = 1;
    let items_per_page = '10';
    let url = `/contact-types?`;

    function updateData() {
        fetchData(current_page, items_per_page, orderBy, order);
        closeModal();
    }

    async function assignData(data) {
        contacttypes = data.data;
        current_page = data.currentPage;
        total_items = data.per_page;
        total_pages = data.last_page;
    }

    async function fetchData(page = current_page, rows = items_per_page, sort_by = orderBy, order = order) {
        axios.get(`${url}sort_by=${sort_by}&order=${order}&page=${page}&per_page=${rows}`).then((response) => {
            assignData(response.data);
        }).catch((err) => {
            error = err.request.response.message;
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
            headers: {},
        };
        axios.delete(`/contact-types/${id}`, config).then((res) => {
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
        fetchData();
    }

    function sortData(param) {
        orderBy = param;
        order = (order == 'asc') ? 'desc' : 'asc';
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
        url = search_param === '' ? `/contact-types?` : `/contact-types?cont_type_desc=${search_param}&`;
        fetchData(1, items_per_page, orderBy, order);
    }

    onMount(async () => {
        assignData(data);
    });
</script>
<svelte:head>
    <title>Tipos de Contactos</title>
</svelte:head>
{#if error}
    <ErrorAlert message={error} on:clearError={ClearError} />
{/if}
<h3 class="mb-4 text-center text-2xl">Tipo de Contactos</h3>
<div class="flex justify-center">
    <label class="input input-bordered flex items-center gap-2">
        <input type="text" class="grow" placeholder="Buscar" on:change={search} />
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
{#if _new}
    <Modal on:close={() => updateData()}>
        <Form {edit} on:message={OpenAlertMessage} on:close={() => updateData()} />
    </Modal>
{/if}
{#if edit}
    <Modal on:close={() => updateData()}>
        <Form {edit} {item} on:message={OpenAlertMessage} on:close={() => updateData()} />
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
                            Descripción
                            <button on:click={() => sortData('cont_type_desc')}>
                                <SortIcon />
                            </button>
                        </div>
                    </th>
                    {#if user.permissions != undefined && user.permissions.includes('contacttypes.create')}
                        <th><button class="btn btn-primary" on:click={() => (_new = true)}>Agregar</button></th>
                    {/if}
                </tr>
            </thead>
            <tbody>
                {#each contacttypes as contacttype, i (contacttype.id)}
                    <tr class="hover">
                        <td>{i+1}</td>
                        <td class="text-center">{contacttype.cont_type_desc}</td>
                        {#if user.permissions != undefined && user.permissions.includes('contacttypes.show')}
                            <td><button class="btn btn-info" on:click={() => router.visit(`/contact-types/${contacttype.id}`)}>Mostrar</button></td>
                        {/if}
                        {#if user.permissions != undefined && user.permissions.includes('contacttypes.update')}
                            <td><button class="btn btn-warning" on:click={() => openEditModal(contacttype)}>Editar</button></td>
                        {/if}
                        {#if user.permissions != undefined && user.permissions.includes('contacttypes.destroy')}
                            <td><button class="btn btn-secondary" on:click={() => OpenDeleteModal(contacttype.id)}>Eliminar</button></td>
                        {/if}
                    </tr>
                {/each}
            </tbody>
        </table>
        <Pagination
            current_page={current_page}
            {total_pages}
            {items_per_page}
            on:page={handlePage}
            on:row={handleRowsPerPage}
        />
    </div>
{/if}