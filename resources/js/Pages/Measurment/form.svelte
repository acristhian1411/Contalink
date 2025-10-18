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
	let unit_name = '';
	let unit_abbreviation = '';
	let allows_decimals = false;
	export let edit;
	export let item;
	let errors = null;

	let token = '';
	let config = {
		headers: {
			authorization: `token: ${token}`,
		},
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
			unit_name = item.unit_name;
			unit_abbreviation = item.unit_abbreviation;
		}
	});
	// http://127.0.0.1:5173/tilltypes
	function handleCreateObject() {
		axios.post('/measurments',{
			unit_name: unit_name,
			unit_abbreviation: unit_abbreviation,	
			allows_decimals: Boolean(allows_decimals)
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
		axios.put(`/measurments/${id}`, {
			unit_name: unit_name,
			unit_abbreviation: unit_abbreviation,
			allows_decimals: allows_decimals
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
	<h3 class="mb-4 text-center text-2xl">Actualizar Unidades de Medida</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Unidades de Medida</h3>
{/if}
<!-- <form> -->
    <Grid columns={2} gap={2}>
		<Textfield 
			label="Descripción" 
			bind:value={unit_name} 
			errors={errors?.unit_name ? {message:errors.unit_name[0]} : null} 
		/>
		<Textfield 
			label="Código" 
			bind:value={unit_abbreviation} 
			errors={errors?.unit_abbreviation ? {message:errors.unit_abbreviation[0]} : null} 
		/>
		<label for="allows_decimals"> Permite decimales</label>
		<input
			type="checkbox"
			name="allows_decimals"
			bind:value={allows_decimals}
			class="mb-6"
		/>

	</Grid>
	<button
		class="btn btn-primary"
		on:click|preventDefault={edit == true ? handleUpdateObject() : handleCreateObject()}>Guardar</button
	>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
<!-- </form> -->
