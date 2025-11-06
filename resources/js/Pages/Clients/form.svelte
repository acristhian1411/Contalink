<script>
	import { onMount } from 'svelte';
	import { useForm } from '@inertiajs/svelte';
	import {Textfield, Autocomplete} from '@components/FormComponents';

	// Props from Inertia pre-loading
	export let staticData;
	export const userContext = null;
	export const formConfig = null;
	export let mode = 'create';
	export let edit = mode === 'edit';
	export let item = null;
	export let personType = 2; // Default to client type

	// Destructure static data from props
	const { countries, cities } = staticData;

	// Form state management with Inertia
	let form = useForm({
		person_fname: item?.person_fname || '',
		person_lastname: item?.person_lastname || '',
		person_corpname: item?.person_corpname || '',
		person_idnumber: item?.person_idnumber || '',
		person_ruc: item?.person_ruc || '',
		person_birtdate: item?.person_birtdate || '',
		person_address: item?.person_address || '',
		p_type_id: personType,
		country_id: item?.country_id || '',
		city_id: item?.city_id || ''
	});

	// UI state variables
	let country_selected = item ? { value: item.country_id, label: item.country?.country_name } : null;
	let city_selected = item ? { value: item.city_id, label: item.city?.city_name } : null;
	// Helper functions for autocomplete options
	function Countries(){
		return countries.map(
			country => ({
				label: country.country_name,
				value: country.id
			})
		)
	}

	function Cities(){
		return cities.map(
			city => ({
				label: city.city_name,
				value: city.id
			})
		)
	}

	// Form submission with Inertia
	function handleSubmit() {
		// Update form data with selected values
		form.country_id = country_selected?.value || null;
		form.city_id = city_selected?.value || null;
		form.person_idnumber = form.person_ruc; // Set ID number same as RUC

		if (edit) {
			form.put(`/persons/${item.id}`, {
				onSuccess: () => {
					history.back();
				},
				onError: (errors) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post('/persons', {
				onSuccess: () => {
					history.back();
				},
				onError: (errors) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	}
</script>

<svelte:head>
	<title>{edit ? "Actualizar Cliente" : "Crear Cliente"}</title>
</svelte:head>

<h3 class="mb-4 text-center text-2xl">
	{edit ? "Actualizar Cliente" : "Crear Cliente"}
</h3>

<form on:submit|preventDefault={handleSubmit} class="grid grid-cols-2 gap-4"> 
	<Textfield 
		label="Nombre" 
		bind:value={form.person_fname} 
		errors={form.errors?.person_fname ? {message: form.errors.person_fname} : null} 
	/>
	<Textfield 
		label="Apellido"
		bind:value={form.person_lastname} 
		errors={form.errors?.person_lastname ? {message: form.errors.person_lastname} : null} 
	/>
	<Textfield
		label="Razón Social"
		bind:value={form.person_corpname} 
		required={true}
		errors={form.errors?.person_corpname ? {message: form.errors.person_corpname} : null}
	/>
	<Textfield 
		label="Código RUC"
		bind:value={form.person_ruc} 
		required={true}
		errors={form.errors?.person_ruc ? {message: form.errors.person_ruc} : null} 
	/>
	<Textfield
		label="Fecha de nacimiento"
		bind:value={form.person_birtdate}
		type="date"
		errors={form.errors?.person_birtdate ? {message: form.errors.person_birtdate} : null}
	/>
	<Textfield
		label="Dirección"
		bind:value={form.person_address} 
		errors={form.errors?.person_address ? {message: form.errors.person_address} : null}
	/>

	<Autocomplete
		errors={form.errors?.country_id ? {message: form.errors.country_id} : null}
		label="País"
		bind:item_selected={country_selected}
		items={Countries()}
		searchTerm=""
		showDropdown={false}
		loading={false}
		filterdItem={Countries()}
	/>

	<Autocomplete
		errors={form.errors?.city_id ? {message: form.errors.city_id} : null}
		label="Ciudad"
		bind:item_selected={city_selected}
		items={Cities()}
		searchTerm=""
		showDropdown={false}
		loading={false}
		filterdItem={Cities()}
	/>
	
	<div class="col-span-2 flex gap-2 mt-4">
		<button
			class="btn btn-primary"
			type="submit"
			disabled={form.processing}>
			{form.processing ? 'Guardando...' : 'Guardar'}
		</button>
		<button class="btn btn-secondary" type="button" on:click={() => history.back()}>
			Cancelar
		</button>
	</div>
</form>
