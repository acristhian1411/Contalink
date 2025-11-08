<script>
    // @ts-nocheck
    import axios from "axios";
    // import {getToken} from '../../services/authservice'
    import { onMount } from "svelte";
    import { createEventDispatcher } from "svelte";
    import { Textfield } from "@components/FormComponents";

    const dispatch = createEventDispatcher();
    let id = 0;
    let city_name = "";
    let city_code = "";
    let country_id = "";
    export let edit;
    export let item;
    let errors = null;
    let states = [];
    let state_selected;
    let token = "";
    let config = {
        headers: {
            authorization: `token: ${token}`,
        },
    };
    function close() {
        dispatch("close");
    }

    function getStates() {
        axios
            .get(`/states/search`)
            .then((response) => {
                states = response.data.data;
                // if (edit == true) {
                // 	state_selected = states.find(state => state.id === item.state_id);
                // }
            })
            .catch((err) => {
                // errors = err.response.data.details ? err.response.data.details : null;
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response.data.message,
                    },
                };
            });
    }

    function OpenAlertMessage(event) {
        dispatch("message", event.detail);
    }

    onMount(() => {
        getStates();
        if (edit == true) {
            id = item.id;
            city_name = item.city_name;
            city_code = item.city_code;
            country_id = item.country_id;
        }
    });
    // http://127.0.0.1:5173/tilltypes
    function handleCreateObject() {
        axios
            .post(
                `/cities`,
                {
                    city_name,
                    city_code,
                    state_id: state_selected?.id ? state_selected.id : null,
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
                `/cities/${id}`,
                {
                    city_name,
                    city_code,
                    state_id: state_selected?.id ? state_selected.id : null,
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
    <h3 class="mb-4 text-center text-2xl">Actualizar Ciudad</h3>
{:else}
    <h3 class="mb-4 text-center text-2xl">Crear Ciudad</h3>
{/if}
<!-- <form> -->
<Textfield
    label="Descripción"
    bind:value={city_name}
    errors={errors?.city_name ? { message: errors.city_name[0] } : null}
/>
<Textfield
    label="Código"
    bind:value={city_code}
    errors={errors?.city_code ? { message: errors.city_code[0] } : null}
/>
<div class="mb-4 flex items-center">
    <span class="mr-2">Departamento</span>
    <select
        id="account_pid"
        class="select select-bordered w-full max-w-xs"
        bind:value={state_selected}
    >
        {#each states as state}
            <option value={state}>
                {state.state_name}
            </option>
        {/each}
    </select>
    {#if errors != null && errors.state_id}
        <span class="text-red-500 text-sm">{errors.state_id[0]}</span>
    {/if}
</div>
<button
    class="btn btn-primary"
    on:click={edit == true ? handleUpdateObject() : handleCreateObject()}
    >Guardar</button
>
<button class="btn btn-secondary" on:click={close}>Cancelar</button>
<!-- </form> -->
