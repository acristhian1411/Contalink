<script>
	import { onMount } from 'svelte';
	import { useForm } from '@inertiajs/svelte';
	import {Textfield, Autocomplete} from '@components/FormComponents';
	import {formatNumber, unformatNumber} from '@components/utilities/NumberFormat.js';

	// Props from Inertia pre-loading
	export let staticData;
	export let userContext = null;
	export let formConfig = null;
	export let mode = 'create';
	export let edit = mode === 'edit';
	export let item = null;

	// Destructure static data from props
	const { categories, brands, ivaTypes, measurementUnits } = staticData;

	// Form state management with Inertia
	let form = useForm({
		product_name: item?.product_name || '',
		product_desc: item?.product_desc || '',
		product_cost_price: item?.product_cost_price || '',
		product_quantity: item?.product_quantity || '',
		product_selling_price: item?.product_selling_price || '',
		product_barcode: item?.product_barcode || '',
		product_image: item?.product_image || '',
		category_id: item?.category_id || '',
		iva_type_id: item?.iva_type_id || '',
		brand_id: item?.brand_id || '',
		measurement_unit_id: item?.measurement_unit_id || ''
	});

	// UI state variables
	let product_profit_percent = '';
	let iva_type_selected = item ? { value: item.iva_type_id, label: item.iva_type?.iva_type_desc } : null;
	let brand_selected = item ? { value: item.brand_id, label: item.brand?.brand_name } : null;
	let measurement_selected = item ? { value: item.measurement_unit_id, label: item.measurement_unit?.unit_name } : null;
	let category_selected = item ? { value: item.category_id, label: item.category?.cat_desc } : null;
	// Form submission with Inertia
	function handleSubmit() {
		// Update form data with selected values
		form.category_id = category_selected?.value || null;
		form.iva_type_id = iva_type_selected?.value || null;
		form.measurement_unit_id = measurement_selected?.value || null;
		form.brand_id = brand_selected?.value || null;

		if (edit) {
			form.put(`/products/${item.id}`, {
				onSuccess: () => {
					history.back();
				},
				onError: (errors) => {
					console.error('Validation errors:', errors);
				}
			});
		} else {
			form.post('/products', {
				onSuccess: () => {
					history.back();
				},
				onError: (errors) => {
					console.error('Validation errors:', errors);
				}
			});
		}
	}
	// Helper functions for autocomplete options
	function Categories(){
		return categories.map(
			category => ({
				label: category.cat_desc,
				value: category.id
			})
		)
	}

	function Measurements(){
		return measurementUnits.map(
			measurement => ({
				label: measurement.unit_name,
				value: measurement.id
			})
		)
	}

	function Brands(){
		return brands.map(
			brand => ({
				label: brand.brand_name,
				value: brand.id
			})
		)
	}
	
	function IvaTypes(){
		return ivaTypes.map(
			iva_type => ({
				label: iva_type.iva_type_desc,
				value: iva_type.id
			})
		)
	}
	
	function handleInput(value) {
		if(value == null || value == undefined || value == ''){
			product_profit_percent = 0;
			return;
		}
		product_profit_percent = value;
		// Calcula el precio de venta basado en el precio de costo y el porcentaje de ganancias
		let porcentaje = parseInt(parseFloat(form.product_cost_price) + (parseFloat(form.product_cost_price) * parseFloat(product_profit_percent) / 100))
		form.product_selling_price = porcentaje.toString();
	}
</script>

<svelte:head>
	<title>{edit ? "Actualizar Producto" : "Crear Producto"}</title>
</svelte:head>

<h3 class="mb-4 text-center text-2xl">
	{edit ? "Actualizar Producto" : "Crear Producto"}
</h3>

<form on:submit|preventDefault={handleSubmit}>
	<div class="grid grid-cols-2 gap-4">
		<Textfield 
			label="Nombre" 
			required={true}
			bind:value={form.product_name} 
			errors={form.errors?.product_name ? {message: form.errors.product_name} : null} 
		/>
		<Textfield 
			label="Descripcion" 
			bind:value={form.product_desc} 
			errors={form.errors?.product_desc ? {message: form.errors.product_desc} : null} 
		/>
		<Textfield 
			label="Precio de costo" 
			type="number"
			step="0.01"
			bind:value={form.product_cost_price} 
			min="0"
			errors={form.errors?.product_cost_price ? {message: form.errors.product_cost_price} : null} 
		/>
		<Textfield
			label="Porcentaje de ganancias" 
			type="number"
			step="0.01"
			customFN={handleInput}
			errors={form.errors?.product_profit_percent ? {message: form.errors.product_profit_percent} : null} 
		/>
		<Textfield 
			label="Precio de venta" 
			type="number"
			step="0.01"
			bind:value={form.product_selling_price} 
			errors={form.errors?.product_selling_price ? {message: form.errors.product_selling_price} : null} 
		/>
		<Textfield 
			label="Cantidad" 
			type="number"
			step="0.001"
			min="0"
			bind:value={form.product_quantity} 
			errors={form.errors?.product_quantity ? {message: form.errors.product_quantity} : null} 
		/>
		<Textfield 
			label="Código de barras" 
			bind:value={form.product_barcode} 
			errors={form.errors?.product_barcode ? {message: form.errors.product_barcode} : null} 
		/>
		<Textfield
			label="Imagen"
			bind:value={form.product_image}
			errors={form.errors?.product_image ? {message: form.errors.product_image} : null}
		/>
		<Autocomplete
			errors={form.errors?.category_id ? {message: form.errors.category_id} : null}
			label="Categoría"
			bind:item_selected={category_selected}
			items={Categories()}
			searchTerm=""
			showDropdown={false}
			loading={false}
			filterdItem={Categories()}
		/>
		<Autocomplete
			errors={form.errors?.measurement_unit_id ? {message: form.errors.measurement_unit_id} : null}
			label="Unidad de medida"
			bind:item_selected={measurement_selected}
			items={Measurements()}
			searchTerm=""
			showDropdown={false}
			loading={false}
			filterdItem={Measurements()}
		/>
		<Autocomplete
			errors={form.errors?.brand_id ? {message: form.errors.brand_id} : null}
			label="Marca"
			bind:item_selected={brand_selected}
			items={Brands()}
			searchTerm=""
			showDropdown={false}
			loading={false}
			filterdItem={Brands()}
		/>
		<Autocomplete
			errors={form.errors?.iva_type_id ? {message: form.errors.iva_type_id} : null}
			label="Tipo IVA"
			bind:item_selected={iva_type_selected}
			items={IvaTypes()}
			searchTerm=""
			showDropdown={false}
			loading={false}
			filterdItem={IvaTypes()}
		/>
	</div>
	
	<div class="flex gap-2 mt-4">
		<button
			type="submit"
			class="btn btn-primary"
			disabled={form.processing}>
			{form.processing ? 'Guardando...' : 'Guardar'}
		</button>
		<button class="btn btn-secondary" type="button" on:click={() => history.back()}>
			Cancelar
		</button>
	</div>
</form>