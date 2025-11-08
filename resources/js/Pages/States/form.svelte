<script>
    // @ts-nocheck
    // import axios from "axios";
    // import {getToken} from '../../services/authservice'
    import { onMount } from "svelte";
    import { createEventDispatcher } from "svelte";
    import { Textfield, Autocomplete } from "@components/FormComponents";
    const dispatch = createEventDispatcher();
    let id = 0;
    let state_name = "";
    let country_id = "";
    export let edit;
    export let item;
    let errors = null;
    let countries = [];
    let country_selected;
    let token = "";
    let searchTerm = "";
    let showDropdown = false;
    let loading = false;
    let filteredCountries = [];
    let config = {
        headers: {
            authorization: `token: ${token}`,
        },
    };
    function close() {
        dispatch("close");
    }

    function Countries() {
        return countries.map((country) => ({
            label: country.country_name,
            value: country.id,
        }));
    }

    function handleSelectChange(details) {
        country_selected = details;
        searchTerm = details.label;
    }

    function getCountries() {
        axios
            .get(`/countries/search/`, { withCredentials: true })
            .then((response) => {
                countries = response.data.data;
            })
            .catch((err) => {
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response?.data?.message
                            ? err.response.data.message
                            : err.message,
                    },
                };
            });
    }

    function OpenAlertMessage(event) {
        dispatch("message", event.detail);
    }

    onMount(() => {
        getCountries();
        if (edit == true) {
            id = item.id;
            state_name = item.state_name;
            country_selected = {
                label: item.country_name,
                value: item.country_id,
            };
            searchTerm = item.country_name;
        }
    });
    // http://127.0.0.1:5173/tilltypes
    function handleCreateObject() {
        axios
            .post(
                `/states`,
                {
                    state_name,
                    country_id: country_selected.value,
                },
                config,
            )
            .then((res) => {
                let detail = {
                    detail: {
                        type: "success",
                        message: res.data.message,
                    },
                };
                OpenAlertMessage(detail);
                close();
            })
            .catch((err) => {
                errors = err.response.data.details
                    ? err.response.data.details
                    : null;
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response.data.message,
                    },
                };
                OpenAlertMessage(detail);
            });
    }
    function handleUpdateObject() {
        axios
            .put(
                `/states/${id}`,
                {
                    state_name,
                    country_id: country_selected.value,
                },
                config,
            )
            .then((res) => {
                let detail = {
                    detail: {
                        type: "success",
                        message: res.data.message,
                    },
                };
                OpenAlertMessage(detail);
                close();
            })
            .catch((err) => {
                errors = err.response.data.details
                    ? err.response.data.details
                    : null;
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response.data.message,
                    },
                };
                OpenAlertMessage(detail);
            });
    }
</script>

{#if edit == true}
    <h3 class="mb-4 text-center text-2xl">Actualizar Departamento</h3>
{:else}
    <h3 class="mb-4 text-center text-2xl">Crear Departamento</h3>
{/if}
<!-- <form on:submit={edit == true ? handleUpdateObject : handleCreateObject}> -->
<Textfield
    label="Descripción"
    bind:value={state_name}
    errors={errors?.country_id ? { message: errors.country_id[0] } : null}
/>
<Autocomplete
    {errors}
    label="Pais"
    bind:item_selected={country_selected}
    items={countries.map((x) => ({ label: x.country_name, value: x.id }))}
    {searchTerm}
    {showDropdown}
    {loading}
    filterdItem={Countries()}
/>
<button
    class="btn btn-primary"
    on:click={edit == true ? handleUpdateObject() : handleCreateObject()}
>
    Guardar
</button>
<button class="btn btn-secondary" on:click={close}> Cancelar </button>
<!-- </form> -->
