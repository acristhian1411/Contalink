<script>
	import { onMount } from 'svelte';
	import { useForm } from '@inertiajs/svelte';
	import {Textfield} from '@components/FormComponents';

	// Props from Inertia pre-loading
	export let userContext = null;
	export let formConfig = null;
	export let mode = 'create';
	export let edit = mode === 'edit';
	export let item = null;

	// Form state management with Inertia
	let form = useForm({
		name: item?.name || '',
		email: item?.email || '',
		person_id: item?.person_id || '',
		password: '',
		password_confirmation: ''
	});
	// Form submission with Inertia
	function handleSubmit() {
		if (edit) {
			form.put(`/users/${item.id}`, {
				onSuccess: () => {
					// Handle success - could redirect or show message
					history.back();
				},
				onError: (errors) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post('/users', {
				onSuccess: () => {
					// Handle success - could redirect or show message
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
	<title>{edit ? "Actualizar Usuario" : "Crear Usuario"}</title>
</svelte:head>

<h3 class="mb-4 text-center text-2xl">
	{edit ? "Actualizar Usuario" : "Crear Usuario"}
</h3>

<form on:submit|preventDefault={handleSubmit}>
	<Textfield 
		label="Nombre" 
		bind:value={form.name} 
		errors={form.errors?.name ? {message: form.errors.name} : null} 
		required={true}
	/>
	<Textfield 
		label="Correo" 
		type="email"
		bind:value={form.email} 
		errors={form.errors?.email ? {message: form.errors.email} : null} 
		required={true}
	/>
	
	{#if !edit}
		<Textfield 
			label="Contraseña" 
			type="password"
			bind:value={form.password} 
			errors={form.errors?.password ? {message: form.errors.password} : null} 
			required={true}
		/>
		<Textfield 
			label="Confirmar Contraseña" 
			type="password"
			bind:value={form.password_confirmation} 
			errors={form.errors?.password_confirmation ? {message: form.errors.password_confirmation} : null} 
			required={true}
		/>
	{/if}

	<div class="flex gap-2 mt-4">
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
