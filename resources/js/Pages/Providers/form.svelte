<script>
	import { useForm } from '@inertiajs/svelte';
	import { createEventDispatcher } from 'svelte';
	import { onMount } from 'svelte';
	import axios from 'axios';
	import {Textfield, Autocomplete} from '@components/FormComponents';

	const dispatch = createEventDispatcher();

	export let edit = false;
	export let item = null;
	export let from = '';
	export let staticData = null;

	let countries = staticData?.countries ?? [];
	let cities = staticData?.cities ?? [];
	let country_selected = item ? { value: item.country_id, label: item.country_name } : null;
	let city_selected = item ? { value: item.city_id, label: item.city_name } : null;

	onMount(async () => {
		if (!staticData) {
			try { const r = await axios.get('/api/countries'); countries = r.data.data; } catch (e) {}
			try { const r = await axios.get('/api/cities'); cities = r.data.data; } catch (e) {}
		}
	});

	let form = useForm({
		person_fname: item?.person_fname || '',
		person_lastname: item?.person_lastname || '',
		person_corpname: item?.person_corpname || '',
		person_idnumber: item?.person_idnumber || '',
		person_ruc: item?.person_ruc || '',
		person_birtdate: item?.person_birtdate || '2022-01-01',
		person_address: item?.person_address || '',
		p_type_id: 1,
		country_id: item?.country_id || '',
		city_id: item?.city_id || ''
	});

	function close() {
		dispatch('close');
	}

	function Countries() {
		return countries.map(c => ({ label: c.country_name, value: c.id }));
	}

	function Cities() {
		return cities.map(c => ({ label: c.city_name, value: c.id }));
	}

	function handleSubmit() {
		form.country_id = country_selected?.value ?? null;
		form.city_id = city_selected?.value ?? null;
		form.person_idnumber = form.person_ruc;

		const options = {
			onSuccess: () => close(),
			onError: (errors) => console.error('Validation errors:', errors)
		};

		if (edit) {
			form.put(`/persons/providers/${item.id}`, options);
		} else {
			form.post('/persons/providers', options);
		}
	}
</script>

{#if edit == true}
	<h3 class="mb-4 text-center text-2xl">Actualizar Proveedor</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Proveedor</h3>
{/if}
<form on:submit|preventDefault={handleSubmit} class="grid grid-cols-2 gap-4">
	<Textfield
		label="Nombre"
		bind:value={form.person_fname}
		errors={form.errors?.person_fname ? {message:form.errors.person_fname} : null}
	/>
	<Textfield
		label="Apellido"
		bind:value={form.person_lastname}
		errors={form.errors?.person_lastname ? {message:form.errors.person_lastname} : null}
	/>
	<Textfield
		label="Razón Social"
		bind:value={form.person_corpname}
		required={true}
		errors={form.errors?.person_corpname ? {message:form.errors.person_corpname} : null}
	/>
	<Textfield
		label="Código RUC"
		bind:value={form.person_ruc}
		required={true}
		errors={form.errors?.person_ruc ? {message:form.errors.person_ruc} : null}
	/>
	<Textfield
		label="Dirección"
		bind:value={form.person_address}
		errors={form.errors?.person_address ? {message:form.errors.person_address} : null}
	/>

	<Autocomplete
		errors={form.errors?.country_id ? {message:form.errors.country_id} : null}
		label="País"
		bind:item_selected={country_selected}
		items={countries.map(x => ({label: x.country_name, value: x.id}))}
		searchTerm=""
		showDropdown={false}
		loading={false}
		filterdItem={Countries()}
	/>

	<Autocomplete
		errors={form.errors?.city_id ? {message:form.errors.city_id} : null}
		label="Ciudad"
		bind:item_selected={city_selected}
		items={cities.map(x => ({label: x.city_name, value: x.id}))}
		searchTerm=""
		showDropdown={false}
		loading={false}
		filterdItem={Cities()}
	/>
	<div class="col-span-2 flex gap-2 mt-4">
		<button class="btn btn-primary" type="submit" disabled={form.processing}>
			{form.processing ? 'Guardando...' : 'Guardar'}
		</button>
		<button class="btn btn-secondary" type="button" on:click={close}>Cancelar</button>
	</div>
</form>
