
<script>
    import axios from 'axios';
    import { onMount } from 'svelte';
    import { createEventDispatcher } from 'svelte';
    import {Textfield} from '@components/FormComponents';
    export let edit;
    export let item;
    export let token = '';
    const dispatch = createEventDispatcher();
    
    let id = 0;
    let errors = null;
    let config = {
        headers: {},
    };
    
    // Variables dinámicas para cada campo
    let cont_type_desc = '';

    function close() {
        dispatch('close');
    }

    function OpenAlertMessage(event) {
        dispatch('message', event.detail);
    }

    onMount(() => {
        if (edit == true) {
            id = item.id;
            cont_type_desc = item.cont_type_desc;

        }
    });

    async function handleCreateObject() {
        try {
            const res = await axios.post(`/contact-types`, { cont_type_desc }, config);

            let detail = {
                detail: {
                    type: 'success',
                    message: res.data.message
                }
            };
            OpenAlertMessage(detail);
            close();
        } catch (err) {
            errors = err.response.data.details ? err.response.data.details : null;
            let detail = {
                detail: {
                    type: 'error',
                    message: err.response.data.message
                }
            };
            OpenAlertMessage(detail);
        }
    }

    async function handleUpdateObject() {
        try {
            const res = await axios.put(`/contact-types/${id}`, { cont_type_desc }, config);

            let detail = {
                detail: {
                    type: 'success',
                    message: res.data.message
                }
            };
            OpenAlertMessage(detail);
            close();
        } catch (err) {
            errors = err.response.data.details ? err.response.data.details : null;
            let detail = {
                detail: {
                    type: 'error',
                    message: err.response.data.message
                }
            };
            OpenAlertMessage(detail);
        }
    }
</script>

<h3 class="mb-4 text-center text-2xl">{#if edit == true}Actualizar Tipo de contacto{:else}Crear Tipo de contacto{/if}</h3>
<form on:submit|preventDefault={edit == true ? handleUpdateObject() : handleCreateObject()}>

    <Textfield
        label="Descripción"
        required={true}
        bind:value={cont_type_desc}
        errors={errors?.cont_type_desc ? {message:errors.cont_type_desc[0]} : null}
    />

    <button class="btn btn-primary" type="submit">Guardar</button>
    <button class="btn btn-secondary" on:click={close}>Cancelar</button>
</form>
