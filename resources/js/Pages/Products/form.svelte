<script>
	// @ts-nocheck
	import axios from 'axios';
	// import {getToken} from '../../services/authservice'
	import { onMount } from 'svelte';
	import { createEventDispatcher } from 'svelte';
	import {Textfield, Autocomplete} from '@components/FormComponents';
	import {formatNumber, unformatNumber} from '@components/utilities/NumberFormat.js';

	const dispatch = createEventDispatcher();
	let id = 0;
	let product_name = '';
	let product_desc = '';
	let product_cost_price = '';
	let product_quantity = '';
	let product_profit_percent = '';
	let product_selling_price = '';
	let product_barcode = '';
	let product_image = '';
	let category_id = '';
	let iva_type_id = '';
	let brand_id = '';
	export let edit;
	export let item;
	let errors = null;
	let iva_types = [];
	let brands = [];
	let measurements = [];
	let categories = [];
	let iva_type_selected ;
	let brand_selected ;
	let measurement_selected ;
	let category_selected ;
	let searchTerm = '';
	let showDropdown = false;
	let loading = false;
	let filteredIvaTypes = [];
	let filteredBrands = [];
	let filteredCategories = [];
	let token = '';
	let config = {
		headers: {
			authorization: `token: ${token}`,
		},
	}
	function close() {
		dispatch('close');
	}

	function getCategories() {
		axios.get(`/api/categories`).then((response) => {
			categories = response.data.data;
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}

	function getMeasurements(){
		axios.get(`/api/measurement-units`).then((response) => {
			measurements = response.data.data;
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}

	function getBrands() {
		axios.get(`/api/brands`).then((response) => {
			brands = response.data.data;
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}

	function getIvaTypes() {
		axios.get(`/api/ivatypes`).then((response) => {
			iva_types = response.data.data;
		}).catch((err) => {
			let detail = {
				detail: {
					type: 'delete',
					message: err.response.data.message
				}
			};
		});
	}

	function OpenAlertMessage(event) {
		dispatch('message', event.detail);
	}

	onMount(() => {
		getCategories();
		getMeasurements()
		getBrands();
		getIvaTypes();
		if (edit == true) {
			id = item.id;
			product_name = item.product_name;
			product_desc = item.product_desc;
			product_image = item.product_image;
			product_barcode = item.product_barcode;
			product_cost_price = item.product_cost_price;
			product_quantity = item.product_quantity;
			product_selling_price = item.product_selling_price;
			brand_id = item.brand_id;
			iva_type_id = item.iva_type_id;
			category_id = item.category_id;
		}
	});
	function handleCreateObject(event) {
		event.preventDefault();
		console.log(iva_type_selected)
		axios
			.post(`/api/products`, {
				product_name,
				product_desc,
				product_cost_price,
				product_quantity:parseFloat(product_quantity),
				product_image,
				product_barcode,
				product_selling_price,
				category_id: category_selected?.value ?? null,
				iva_type_id: iva_type_selected?.value ?? null,
				measurement_unit_id: measurement_selected?.value ?? null,
				brand_id: brand_selected?.value ?? null
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

	function handleUpdateObject(event) {
		event.preventDefault();
		axios
			.put(`/api/products/${id}`, {
				product_name,
				product_desc,
				product_cost_price,
				product_quantity,
				product_image,
				product_barcode,
				product_selling_price,
				category_id: category_selected?.value? category_selected.value : null,
				iva_type_id: iva_type_selected?.value? iva_type_selected.value : null,
				brand_id: brand_selected?.value? brand_selected.value : null
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
	function Categories(){
		return categories.map(
			category => ({
				label: category.category_name,
				value: category.id
			})
		)
	}

	function Measurements(){
		return measurements.map(
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
		return iva_types.map(
			iva_type => ({
				label: iva_type.iva_type_name,
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
		let porcentaje = parseInt(parseFloat(product_cost_price) + (parseFloat(product_cost_price) * parseFloat(product_profit_percent) / 100))
		product_selling_price = porcentaje.toString();
	}
</script>

{#if edit == true}
	<h3 class="mb-4 text-center text-2xl">Actualizar Producto</h3>
{:else}
	<h3 class="mb-4 text-center text-2xl">Crear Producto</h3>
{/if}
<form on:submit={edit == true ? handleUpdateObject : handleCreateObject}>
	<div class="grid grid-cols-2 gap-4">
    <Textfield 
		label="Nombre" 
		required={true}
		bind:value={product_name} 
		errors={errors?.product_name ? {message:errors.product_name[0]} : null} 
	/>
	<Textfield 
		label="Descripcion" 
		bind:value={product_desc} 
		errors={errors?.product_desc ? {message:errors.product_desc[0]} : null} 
	/>
	<Textfield 
		label="Precio de costo" 
		type="number"
		bind:value={product_cost_price} 
		min="0"
		errors={errors?.product_cost_price ? {message:errors.product_cost_price[0]} : null} 
	/>
	<Textfield
		label="Porcentaje de ganancias" 
		type="number"
		customFN={handleInput}
		errors={errors?.product_profit_percent ? {message:errors.product_profit_percent[0]} : null} 
	/>
	<Textfield 
		label="Precio de venta" 
		type="number"
		bind:value={product_selling_price} 
		errors={errors?.product_selling_price ? {message:errors.product_selling_price[0]} : null} 
	/>
	<Textfield 
		label="Cantidad" 
		type="number"
		min="0"
		bind:value={product_quantity} 
		errors={errors?.product_quantity ? {message:errors.product_quantity[0]} : null} 
	/>
	<Textfield 
		label="Código de barras" 
		bind:value={product_barcode} 
		errors={errors?.product_barcode ? {message:errors.product_barcode[0]} : null} 
	/>
	<Textfield
		label="Imagen"
		bind:value={product_image}
		errors={errors?.product_image ? {message:errors.product_image[0]} : null}
	/>
	<Autocomplete
		errors={errors}
		label="Categoría"
		bind:item_selected={category_selected}
		items={categories.map(x => ({label: x.cat_desc, value: x.id}))}
		searchTerm={searchTerm}
		showDropdown={showDropdown}
		loading={loading}
		filterdItem={Categories()}
	/>
	<Autocomplete
		errors={errors}
		label="Unidad de medida"
		bind:item_selected={measurement_selected}
		items={measurements.map(x => ({label: x.unit_name, value: x.id}))}
		searchTerm={searchTerm}
		showDropdown={showDropdown}
		loading={loading}
		filterdItem={Measurements()}
	/>
	<Autocomplete
		errors={errors}
		label="Marca"
		bind:item_selected={brand_selected}
		items={brands.map(x => ({label: x.brand_name, value: x.id}))}
		searchTerm={searchTerm}
		showDropdown={showDropdown}
		loading={loading}
		filterdItem={Brands()}
	/>
	<Autocomplete
		errors={errors}
		label="Tipo IVA"
		bind:item_selected={iva_type_selected}
		items={iva_types.map(x => ({label: x.iva_type_desc, value: x.id}))}
		searchTerm={searchTerm}
		showDropdown={showDropdown}
		loading={loading}
		filterdItem={IvaTypes()}
	/>
	</div>
	<button
		type="submit"
		class="btn btn-primary"
		>Guardar</button
	>
	<button class="btn btn-secondary" on:click={close}>Cancelar</button>
</form>