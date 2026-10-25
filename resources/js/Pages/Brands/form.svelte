<script>
	// @ts-nocheck
	import axios from 'axios';
	// import {getToken} from '../../services/authservice'
	import { onMount } from 'svelte';
	import { createEventDispatcher } from 'svelte';
    import {Textfield} from '@components/FormComponents';

	const dispatch = createEventDispatcher();
	let id = 0;
	let brand_desc = '';
	let brand_name = '';
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
			brand_name = item.brand_name;
			brand_desc = item.brand_desc;
		}
	});
	// http://127.0.0.1:5173/till-types
	function handleCreateObject() {
		
		axios
			.post(`/brands`, {
				brand_desc,
				brand_name
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
			.put(`/brands/${id}`, {
				brand_desc,
				brand_name
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
	<h3 class="mb-4 text-center text-2xl">Actualizar Marca</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Marca</h3>
{/if}
<!-- <form> -->
	<Textfield 
		label="Nombre" 
		bind:value={brand_name} 
		errors={errors?.brand_name ? {message:errors.brand_name[0]} : null} 
	/>
	<Textfield 
		label="Descripción" 
		bind:value={brand_desc} 
		errors={errors?.brand_desc ? {message:errors.brand_desc[0]} : null} 
	/>	
	<button
		class="btn btn-primary"
		on:click={edit == true ? handleUpdateObject() : handleCreateObject()}>Guardar</button
	>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
<!-- </form> -->
