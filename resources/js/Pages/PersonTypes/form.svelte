<script>
	// @ts-nocheck
	import axios from 'axios';
	// import {getToken} from '../../services/authservice'
	import { onMount } from 'svelte';
	import { createEventDispatcher } from 'svelte';
	import {Textfield} from '@components/FormComponents';

	// export const appUrl
	const dispatch = createEventDispatcher();
	let id = 0;
	let p_type_desc = '';
	export let edit;
	export let item;
	let errors = null;
	let token = '';
	let config = {
		headers: {},
	}
	function close() {
		dispatch('close');
	}

	function OpenAlertMessage(event) {
		dispatch('message', event.detail);
	}

	onMount(() => {
		if (edit == true) {
			id = item.id;
			p_type_desc = item.p_type_desc;
		}
	});
	// http://127.0.0.1:5173/till-types
	function handleCreateObject() {
		
		axios
			.post(`/person-types`, {
				p_type_desc
			},config)
			.then((res) => {
				let detail = {
					detail: {
						type: 'success',
						message: res.data.message
					}
				};
				OpenAlertMessage(detail);
				close();
			}).catch((err) => {
				errors = err.response.data.details ? err.response.data.details : null;
				let detail = {
					detail: {
						type: 'error',
						message: err.response.data.message
					}
				};
				OpenAlertMessage(detail);
			});
	}
	function handleUpdateObject() {
		axios
			.put(`/person-types/${id}`, {
				p_type_desc
			},config)
			.then((res) => {
				let detail = {
					detail: {
						type: 'success',
						message: res.data.message
					}
				};
				OpenAlertMessage(detail);
				close();
			})
			.catch((err) => {
				errors = err.response.data.details ? err.response.data.details : null;
				let detail = {
					detail: {
						type: 'delete',
						message: err.response.data.message
					}
				};
				OpenAlertMessage(detail);
			});
	}
</script>

{#if edit == true}
	<h3 class="mb-4 text-center text-2xl">Actualizar Tipo de Persona</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Tipo de Persona</h3>
{/if}
<form on:submit|preventDefault={edit == true ? handleUpdateObject() : handleCreateObject()}>
	<Textfield 
		label="Descripción" 
		required={true}
		bind:value={p_type_desc} 
		errors={errors?.p_type_desc ? {message:errors.p_type_desc[0]} : null} 
	/>

	<button
		class="btn btn-primary"
		type="submit"
		>Guardar</button
	>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
</form>
