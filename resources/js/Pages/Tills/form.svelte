<script>
	// @ts-nocheck
	import { useForm } from '@inertiajs/svelte';
	import { createEventDispatcher } from 'svelte';
	import { Textfield, Autocomplete } from '@components/FormComponents';

	const dispatch = createEventDispatcher();

	export let edit = false;
	export let item = null;
	export let tillTypes = [];

	let searchTerm = '';
	let showDropdown = false;
	let loading = false;
	let till_type_selected = edit && item
		? { label: item.till_type_desc, value: item.t_type_id }
		: null;

	let form = useForm({
		till_name: item?.till_name || '',
		till_account_number: item?.till_account_number || '',
		t_type_id: item?.t_type_id || null,
	});

	function close() {
		dispatch('close');
	}

	function TillTypes() {
		return tillTypes.map((till_type) => ({
			label: till_type.till_type_desc,
			value: till_type.id,
		}));
	}

	function handleSubmit() {
		form.t_type_id = till_type_selected?.value ?? null;

		const options = {
			onSuccess: () => close(),
			onError: (errors) => console.error('Validation errors:', errors),
		};

		if (edit) {
			form.put(`/tills/${item.id}`, options);
		} else {
			form.post('/tills', options);
		}
	}
</script>

{#if edit == true}
	<h3 class="mb-4 text-center text-2xl">Actualizar Caja</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Caja</h3>
{/if}
<form on:submit|preventDefault={handleSubmit}>
	<Textfield
		errors={form.errors.till_name ? { message: form.errors.till_name } : null}
		bind:value={form.till_name}
		label="Descripción"
	/>
	<Textfield
		errors={form.errors.till_account_number ? { message: form.errors.till_account_number } : null}
		bind:value={form.till_account_number}
		label="Número de Cuenta"
	/>
	<Autocomplete
		errors={form.errors.t_type_id ? { message: form.errors.t_type_id } : null}
		label="Tipo de Caja"
		bind:item_selected={till_type_selected}
		items={tillTypes.map((x) => ({ label: x.till_type_desc, value: x.id }))}
		searchTerm={searchTerm}
		showDropdown={showDropdown}
		loading={loading}
		filterdItem={TillTypes()}
	/>
	<button class="btn btn-primary" type="submit" disabled={form.processing}>
		{form.processing ? 'Guardando...' : 'Guardar'}
	</button>
	<button class="btn btn-secondary" type="button" on:click={close}>Cancelar</button>
</form>
