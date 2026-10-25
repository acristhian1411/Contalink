<?php
function generateFormComponent($modelName, $fields) {
    // Convertir el nombre del modelo a minúsculas para usar en la URL y permisos
    $modelNameLower = strtolower($modelName);
    $formTitle = "{#if edit == true}Actualizar $modelName{:else}Crear $modelName{/if}";

    // Crear el inicio del script con configuración y eventos
    $svelteTemplate = "
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
        headers: {
        },
    };
    
    // Variables dinámicas para cada campo
    ";
    
    // Declaración de las variables para cada campo
    foreach ($fields as $field) {
        $svelteTemplate .= "let $field = '';\n";
    }

    // Definición de la función close
    $svelteTemplate .= "
    function close() {
        dispatch('close');
    }

    function OpenAlertMessage(event) {
        dispatch('message', event.detail);
    }

    onMount(() => {
        if (edit == true) {
            id = item.id;
            ";

    // Asignar valores iniciales a cada campo en caso de edición
    foreach ($fields as $field) {
        $svelteTemplate .= "$field = item.$field;\n";
    }

    $svelteTemplate .= "
        }
    });

    async function handleCreateObject() {
        try {
            const res = await axios.post(`/api/{$modelNameLower}s`, { ";

    // Pasar cada campo como datos para la creación
    foreach ($fields as $field) {
        $svelteTemplate .= "$field, ";
    }

    $svelteTemplate = rtrim($svelteTemplate, ", ") . " }, config);

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
            const res = await axios.put(`/api/{$modelNameLower}s/${id}`, { ";

    // Pasar cada campo como datos para la actualización
    foreach ($fields as $field) {
        $svelteTemplate .= "$field, ";
    }

    $svelteTemplate = rtrim($svelteTemplate, ", ") . " }, config);

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

<h3 class=\"mb-4 text-center text-2xl\">$formTitle</h3>
<form on:submit|preventDefault={edit == true ? handleUpdateObject() : handleCreateObject()}>
";

    // Generar el componente de entrada de texto para cada campo
    foreach ($fields as $field) {
        $svelteTemplate .= "
    <Textfield
        label=\"" . ucfirst($field) . "\"
        required={true}
        bind:value={{$field}}
        errors={errors?.{$field} ? {message:errors.{$field}[0]} : null}
    />
";
    }

    // Agregar los botones de guardar y cancelar
    $svelteTemplate .= "
    <button class=\"btn btn-primary\" type=\"submit\">Guardar</button>
    <button class=\"btn btn-secondary\" on:click={close}>Cancelar</button>
</form>
";

    return $svelteTemplate;
}
