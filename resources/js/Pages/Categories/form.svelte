<script>
	// @ts-nocheck
	import axios from 'axios';
	// import {getToken} from '../../services/authservice'
	import { onMount } from 'svelte';
	import { createEventDispatcher } from 'svelte';
    import {Textfield} from '@components/FormComponents';

	const dispatch = createEventDispatcher();
	let id = 0;
	let cat_desc = '';
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
			cat_desc = item.cat_desc;
		}
	});
	// http://127.0.0.1:5173/till-types
	function handleCreateObject() {
		
		axios
			.post(`/categories`, {
				cat_desc,
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
			.put(`/categories/${id}`, {
				cat_desc,
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
	<h3 class="mb-4 text-center text-2xl">Actualizar Categoria</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Categoria</h3>
{/if}
<!-- <form> -->
	<Textfield 
		label="Descripción" 
		bind:value={cat_desc} 
		errors={errors?.cat_desc ? {message:errors.cat_desc[0]} : null} 
	/>
	
	<button
		class="btn btn-primary"
		on:click={edit == true ? handleUpdateObject() : handleCreateObject()}>Guardar</button
	>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
<!-- </form> -->
