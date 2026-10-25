<script>
	// @ts-nocheck
	import axios from 'axios';
	// import {getToken} from '../../services/authservice'
	import { onMount } from 'svelte';
	import { createEventDispatcher } from 'svelte';
	import {Textfield} from '@components/FormComponents';
	import { Grid } from '@components/utilities';

	const dispatch = createEventDispatcher();
	let id = 0;
	let country_name = '';
	let country_code = '';
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
			country_name = item.country_name;
			country_code = item.country_code;
		}
	});
	// http://127.0.0.1:5173/till-types
	function handleCreateObject() {
		axios.post('/countries',{
			country_name: country_name,
			country_code: country_code,	
		})
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
		axios.put(`/countries/${id}`, {
			country_name: country_name,
			country_code: country_code,
		})
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
	<h3 class="mb-4 text-center text-2xl">Actualizar Pais</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Pais</h3>
{/if}
<!-- <form> -->
    <Grid columns={2} gap={2}>
		<Textfield 
			label="Descripción" 
			bind:value={country_name} 
			errors={errors?.country_name ? {message:errors.country_name[0]} : null} 
		/>
		<Textfield 
			label="Código" 
			bind:value={country_code} 
			errors={errors?.country_code ? {message:errors.country_code[0]} : null} 
		/>
	</Grid>
	<button
		class="btn btn-primary"
		on:click|preventDefault={edit == true ? handleUpdateObject() : handleCreateObject()}>Guardar</button
	>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
<!-- </form> -->
