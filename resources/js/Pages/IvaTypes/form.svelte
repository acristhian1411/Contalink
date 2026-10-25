<script>
	// @ts-nocheck
	import axios from 'axios';
	// import {getToken} from '../../services/authservice'
	import { onMount } from 'svelte';
	import { createEventDispatcher } from 'svelte';
	import {Textfield} from '@components/FormComponents';

	const dispatch = createEventDispatcher();
	let id = 0;
	let iva_type_desc = '';
	let iva_type_percent = '';
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
			iva_type_desc = item.iva_type_desc;
			iva_type_percent = item.iva_type_percent;
		}
	});
	// http://127.0.0.1:5173/till-types
	function handleCreateObject() {
		
		axios
			.post(`/api/iva-types`, {
				iva_type_desc,
				iva_type_percent
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
						type: 'delete',
						message: err.response.data.message
					}
				};
				OpenAlertMessage(detail);
			});
	}
	function handleUpdateObject() {
		axios
			.put(`/iva-types/${id}`, {
				iva_type_desc,
				iva_type_percent
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
						type: 'delete',
						message: err.response.data.message
					}
				};
				OpenAlertMessage(detail);
			});
	}
</script>

{#if edit == true}
	<h3 class="mb-4 text-center text-2xl">Actualizar Tipos de IVA</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Tipos de IVA</h3>
{/if}
<!-- <form> -->
	<Textfield 
		label="Descripción" 
		bind:value={iva_type_desc} 
		errors={errors?.iva_type_desc ? {message:errors.iva_type_desc[0]} : null} 
	/>
	<Textfield 
		label="Porcentaje" 
		bind:value={iva_type_percent} 
		errors={errors?.iva_type_percent ? {message:errors.iva_type_percent[0]} : null} 
	/>
	
	<button
		class="btn btn-primary"
		on:click={edit == true ? handleUpdateObject() : handleCreateObject()}>Guardar</button
	>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
<!-- </form> -->
