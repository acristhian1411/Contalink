<script>
	// @ts-nocheck
	import axios from 'axios';
	// import {getToken} from '../../services/authservice'
	import { onMount } from 'svelte';
	import { createEventDispatcher } from 'svelte';
	import {Textfield, Autocomplete} from '@components/FormComponents';

	const dispatch = createEventDispatcher();
	let id = 0;
	let person_fname = '';
	let person_lastname = '';
	let person_corpname = '';
	let person_idnumber = '';
	let person_ruc = '';
	let person_birtdate = '';
	let person_photo = '';
	let person_address = '';
	let p_type_id = 1;
	let country_id = 0;
	let countries = [];
	let country_selected;
	let searchTermCountry = '';
	let loading = false;
	let showDropdown = false;
	let city_id = 0;
	let cities = [];
	let city_selected;
	let searchTermCity = '';
	export let edit;
	export let item;
	export let from;
	let errors = null;
	let token = '';
	let config = {
		headers: {},
	}
	function close() {
		dispatch('close');
	}
	function selectProvider(item) {
		dispatch('providerSelected', item);
	}

	function OpenAlertMessage(event) {
		dispatch('message', event.detail);
	}
	function getCountries() {
		axios.get(`/api/countries`).then((response) => {
			countries = response.data.data;
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}

	function Countries(){
		return countries.map(
			country => ({
				label: country.country_name,
				value: country.id
			})
		)
	}

	function getCities() {
		axios.get(`/api/cities`).then((response) => {
			cities = response.data.data;
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}

	function Cities(){
		return cities.map(
			city => ({
				label: city.city_name,
				value: city.id
			})
		)
	}

	onMount(() => {
		getCountries();
		getCities();
		if (edit == true) {
			id = item.id;
			person_fname = item.person_fname;
			person_lastname = item.person_lastname;
			person_corpname = item.person_corpname;
			person_idnumber = item.person_idnumber;
			person_ruc = item.person_ruc;
			person_birtdate = item.person_birtdate;
			person_address = item.person_address;
			p_type_id = item.p_type_id;
			country_selected = {label: item.country_name, value: item.country_id};
			searchTermCountry = item.country_name;
			searchTermCity = item.city_name;
			city_selected = {label: item.city_name, value: item.city_id};
		}
	});
	// http://127.0.0.1:5173/till-types
	function handleCreateObject(event) {
		event.preventDefault();
		axios
			.post(`/api/persons`, {
				person_fname,
				person_corpname,
				person_idnumber:person_ruc,
				person_ruc,
				person_birtdate:'2022-01-01',
				person_address,
				person_lastname,
				country_id: country_selected?.value? country_selected.value : null,
				city_id: city_selected?.value? city_selected.value : null,
				p_type_id: p_type_id
			},config)
			.then((res) => {
				if(from == 'purchases'){
					selectProvider({label: res.data.data.person_fname + ' ' + res.data.data.person_lastname, value: res.data.data.id});
				}
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
	function handleUpdateObject(event) {
		event.preventDefault();
		axios
			.put(`/api/persons/${id}`, {
				person_fname,
				person_corpname,
				person_idnumber:person_ruc,
				person_ruc,
				person_birtdate:'2022-01-01',
				person_address,
				person_lastname,
				country_id: country_selected?.value? country_selected.value : null,
				city_id: city_selected?.value? city_selected.value : null,
				p_type_id: p_type_id
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
	<h3 class="mb-4 text-center text-2xl">Actualizar Proveedor</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Proveedor</h3>
{/if}
<form on:submit={edit == true ? handleUpdateObject : handleCreateObject}> 
	<div class="grid grid-cols-2 gap-4">
	<Textfield 
		label="Nombre" 
		bind:value={person_fname} 
		errors={errors?.person_fname ? {message:errors.person_fname[0]} : null} 
	/>
	<Textfield 
		label="Apellido"
		bind:value={person_lastname} 
		errors={errors?.person_lastname ? {message:errors.person_lastname[0]} : null} 
	/>
	<Textfield
		label="Razón Social"
		bind:value={person_corpname} 
		required={true}
		errors={errors?.person_corpname ? {message:errors.person_corpname[0]} : null}
	/>
	<Textfield 
		label="Código RUC"
		bind:value={person_ruc} 
		required={true}
		errors={errors?.person_ruc ? {message:errors.person_ruc[0]} : null} 
	/>
	<Textfield
		label="Dirección"
		bind:value={person_address} 
		errors={errors?.person_address ? {message:errors.person_address[0]} : null}
	/>

	<Autocomplete
		errors={errors}
		label="País"
		bind:item_selected={country_selected}
		items={countries.map(x => ({label: x.country_name, value: x.id}))}
		searchTerm={searchTermCountry}
		showDropdown={showDropdown}
		loading={loading}
		filterdItem={Countries()}
	/>

	<Autocomplete
		errors={errors}
		label="Ciudad"
		bind:item_selected={city_selected}
		items={cities.map(x => ({label: x.city_name, value: x.id}))}
		searchTerm={searchTermCity}
		showDropdown={showDropdown}
		loading={loading}
		filterdItem={Cities()}
	/>
</div>
	<button
		class="btn btn-primary"
		type="submit"
	>
		Guardar
	</button
	>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
</form>
