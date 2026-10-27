<script>
	// @ts-nocheck
	import { useForm } from '@inertiajs/svelte';
	import { createEventDispatcher } from 'svelte';
	import { Textfield, Autocomplete } from '@components/FormComponents';

	const dispatch = createEventDispatcher();

	export let edit = false;
	export let item = null;
	export let staticData = null;

	let countries = staticData?.countries ?? [];
	let cities = staticData?.cities ?? [];

	let country_selected = item ? { label: item.country_name, value: item.country_id } : null;
	let city_selected = item ? { label: item.city_name, value: item.city_id } : null;
	let searchTermCountry = item?.country_name ?? '';
	let searchTermCity = item?.city_name ?? '';
	let showDropdown = false;
	let loading = false;

	let step = 0;

	let form = useForm({
		person_fname: item?.person_fname || '',
		person_lastname: item?.person_lastname || '',
		person_corpname: item?.person_corpname || '',
		person_idnumber: item?.person_idnumber || '',
		person_ruc: item?.person_ruc || '',
		person_birtdate: item?.person_birtdate || '',
		person_address: item?.person_address || '',
		p_type_id: 0,
		country_id: item?.country_id || '',
		city_id: item?.city_id || '',
		email: '',
		name: '',
		password: '',
	});

	function close() {
		dispatch('close');
	}

	function handleNextStep() {
		if (!form.email) form.email = `${form.person_lastname}.${form.person_fname}@gmail.com`;
		if (!form.name) form.name = `${form.person_lastname} ${form.person_fname}`;
		if (!form.password) form.password = '123456789';
		step = 1;
	}

	function handlePrevStep() {
		step = 0;
	}

	function handleSubmit() {
		form.country_id = country_selected?.value ?? null;
		form.city_id = city_selected?.value ?? null;

		const options = {
			onSuccess: () => close(),
			onError: () => {},
		};

		if (edit) {
			form.put(`/persons/employees/${item.id}`, options);
		} else {
			form.post('/persons/employees', options);
		}
	}
</script>

{#if edit == true}
	<h3 class="mb-4 text-center text-2xl">Actualizar Empleados</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Empleados</h3>
{/if}
<form on:submit|preventDefault={handleSubmit} class="grid grid-cols-2 gap-4">
	{#if edit != true}
		<div class="mb-4 flex items-center justify-center">
			<ul class="steps cursor-pointer">
				<!-- svelte-ignore a11y-click-events-have-key-events -->
				<!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
				<li class="step {step == 0 ? 'step-primary' : ''}" on:click={() => (step = 0)}>
					Datos personales
				</li>
				<!-- svelte-ignore a11y-click-events-have-key-events -->
				<!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
				<li class="step {step == 1 ? 'step-primary' : ''}" on:click={() => (step = 1)}>Usuario</li>
			</ul>
		</div>
	{/if}
	{#if step == 0}
		<Textfield
			label="Nombre"
			bind:value={form.person_fname}
			errors={form.errors.person_fname ? { message: form.errors.person_fname } : null}
		/>
		<Textfield
			label="Apellido"
			bind:value={form.person_lastname}
			errors={form.errors.person_lastname ? { message: form.errors.person_lastname } : null}
		/>
		<Textfield
			label="Cédula"
			bind:value={form.person_idnumber}
			required={true}
			errors={form.errors.person_idnumber ? { message: form.errors.person_idnumber } : null}
		/>
		<Textfield
			label="Fecha de nacimiento"
			bind:value={form.person_birtdate}
			required={true}
			type="date"
			errors={form.errors.person_birtdate ? { message: form.errors.person_birtdate } : null}
		/>
		<Textfield
			label="Dirección"
			bind:value={form.person_address}
			errors={form.errors.person_address ? { message: form.errors.person_address } : null}
		/>
		<Autocomplete
			errors={form.errors.country_id ? { message: form.errors.country_id } : null}
			label="País"
			bind:item_selected={country_selected}
			items={countries.map((x) => ({ label: x.country_name, value: x.id }))}
			searchTerm={searchTermCountry}
			{showDropdown}
			{loading}
		/>
		<Autocomplete
			errors={form.errors.city_id ? { message: form.errors.city_id } : null}
			label="Ciudad"
			bind:item_selected={city_selected}
			items={cities.map((x) => ({ label: x.city_name, value: x.id }))}
			searchTerm={searchTermCity}
			{showDropdown}
			{loading}
		/>
	{:else if step == 1}
		<Textfield
			label="Correo"
			bind:value={form.email}
			errors={form.errors.email ? { message: form.errors.email } : null}
		/>
		<Textfield
			label="Nombre"
			bind:value={form.name}
			errors={form.errors.name ? { message: form.errors.name } : null}
		/>
		<Textfield
			label="Contraseña"
			type="password"
			bind:value={form.password}
			errors={form.errors.password ? { message: form.errors.password } : null}
		/>
	{/if}
	{#if edit != true}
		<button type="button" class="btn btn-accent" on:click={step == 0 ? handleNextStep : handlePrevStep}>
			{step == 0 ? 'Siguiente' : 'Anterior'}
		</button>
	{/if}
	{#if step == 1 || edit == true}
		<button class="btn btn-primary" type="submit" disabled={form.processing}>
			{form.processing ? 'Guardando...' : 'Guardar'}
		</button>
	{/if}
	<button class="btn btn-secondary" type="button" on:click={close}>Cancelar</button>
</form>
