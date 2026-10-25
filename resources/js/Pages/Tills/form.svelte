<script>
	// @ts-nocheck
	import axios from 'axios';
	// import {getToken} from '../../services/authservice'
	import { onMount } from 'svelte';
	import { createEventDispatcher } from 'svelte';
	import {Textfield, Autocomplete} from '@components/FormComponents';

	const dispatch = createEventDispatcher();
	let id = 0;
	let till_name = '';
	let till_account_number = '';
	let country_id = '';
	export let edit;
	export let item;
	let errors = null;
	let till_types = [];
	let till_type_selected ;
	let searchTerm = '';
	let showDropdown = false;
	let loading = false;
	let token = '';
	let config = {
		headers: {},
	}
	function close() {
		dispatch('close');
	}



	function getTillTypes() {
		axios.get(`/api/till-types`).then((response) => {
			till_types = response.data.data;
			if (edit == true) {
				till_type_selected = till_types.find(till_type => till_type.id === item.till_type_id);
				searchTerm = till_type_selected.till_type_desc;
			}
		}).catch((err) => {
			// errors = err.response.data.details ? err.response.data.details : null;
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}

	function TillTypes(){
		return till_types.map(
			till_type => ({
				label: till_type.till_type_desc,
				value: till_type.id
			})
		)
	}

	function OpenAlertMessage(event) {
		dispatch('message', event.detail);
	}

	onMount(() => {
		getTillTypes();
		if (edit == true) {
			id = item.id;
			till_name = item.till_name;
			till_account_number = item.till_account_number;
			searchTerm = item.till_type_desc

		}
	});
	// http://127.0.0.1:5173/till-types
	function handleCreateObject() {
		axios
			.post(`/api/tills`, {
				till_name,
				t_type_id: till_type_selected?.value? till_type_selected.value : null,
				till_account_number:till_account_number
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
			.put(`/api/tills/${id}`, {
				till_name,
				t_type_id: till_type_selected?.value? till_type_selected.value : null
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
	<h3 class="mb-4 text-center text-2xl">Actualizar Tipo de Caja</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Tipo de Caja</h3>
{/if}
<form on:submit|preventDefault={edit == true ? handleUpdateObject() : handleCreateObject()}>
	<Textfield
		errors={errors?.till_name ? {message:errors.till_name[0]} : null}
		bind:value={till_name} 
		label="Descripción"
	/>
	<Textfield
		errors={errors?.till_account_number ? {message:errors.till_account_number[0]} : null}
		bind:value={till_account_number} 
		label="Número de Cuenta"
	/>
	<Autocomplete
		errors={errors}
		label="Tipo de Caja"
		bind:item_selected={till_type_selected}
		items={till_types.map(x => ({label: x.till_type_desc, value: x.id}))}
		searchTerm={searchTerm}
		showDropdown={showDropdown}
		loading={loading}
		filterdItem={TillTypes()}
	/>
	<button
		class="btn btn-primary"
		type="submit">
		Guardar
	</button>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
</form>
