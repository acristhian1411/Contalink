<script>
    import { onMount, onDestroy } from "svelte";
    import { useForm } from '@inertiajs/svelte';
    import axios from "axios";
    import {
        formatNumber,
        unformatNumber,
    } from "@components/utilities/NumberFormat.js";
    import { SearchIcon } from "@components/Icons/";
    import {
        Textfield,
        Autocomplete,
        SearchPersons,
        SelectMultiple,
    } from "@components/FormComponents";
    import { Modal } from "@components/utilities";
    import { Alert } from "@components/Alerts/";
    import DetailsTable from "./DetailsTable.svelte";
    import { Grid, GridItem } from "@components/utilities";
    import Form from "@pages/Providers/form.svelte";

    // Props from Inertia pre-loading
    export let staticData;
    export let userContext;
    export let formConfig;
    export let mode = 'create';
    export let edit = mode === 'edit';
    export let item = null;

    // Destructure static data from props
    const { paymentTypes, ivaTypes, measurementUnits, categories, brands, providers } = staticData;
    const { user, permissions, userTills } = userContext;
    const { purchaseNumber, defaultDate, validationRules, businessRules, tillAmounts } = formConfig;

    // Form state management with Inertia
    let form = useForm({
        person_id: item?.person_id || '',
        purchase_date: item?.purchase_date || defaultDate,
        purchase_number: item?.purchase_number || purchaseNumber,
        till_id: userTills.length === 1 ? userTills[0].id : (item?.till_id || ''),
        purchase_details: item?.purchase_details || [],
        proofPayments: item?.proofPayments || []
    });

    // UI state variables
    let tillsSelected = userTills.length === 1 ? 
        { value: userTills[0].id, label: userTills[0].till_name } : 
        (item ? { value: item.till_id, label: item.till?.till_name } : []);
    let tillsSearchTerm = userTills.length === 1 ? userTills[0].till_name : 
        (item?.till?.till_name || "");
    let paymentTypesProcessed = paymentTypes.map(x => ({
        label: x.paymentTypeDesc,
        value: x.id,
        proof_payments: x.proof_payments,
    }));
    let paymentTypesSelected = [];
    let proofPaymentTypes = [];
    let providersSelected = item?.person_id || '';
    let searchTermProviders = item?.person?.person_fname ? 
        `${item.person.person_fname} ${item.person.person_lastname}` : '';
    let showPersonSearchForm = false;
    let showProviderForm = false;
    let modal = false;
    let openAlert = false;
    let alertMessage = "";
    let alertType = "";
    let purchaseDetails = item?.purchase_details || [];

    $: purchaseDetails;

    // Dynamic data functions (using protected APIs)
    async function searchProviders(searchTerm) {
        if (searchTerm.length < 3) return [];
        
        try {
            const response = await axios.get('/api/search/providers', {
                params: { q: searchTerm },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return response.data.data;
        } catch (error) {
            handleApiError(error);
            return [];
        }
    }

    function handleChangePaymentType(event) {
        paymentTypesSelected = event.detail;
        filterProofPaymentTypes();
    }

    /**
     * Función para seleccionar el proveedor
     * @param {event} item
     * @returns {void}
     */
    function selectProvider(item) {
        providersSelected = item.detail.person.id;
        searchTermProviders = item.detail.label;
        form.person_id = item.detail.person.id;
    }

    // Alert and error handling
    function closeAlert() {
        openAlert = false;
    }
    
    function openAlerts(message, type) {
        openAlert = true;
        alertType = type;
        alertMessage = message;
    }

    function handleApiError(error) {
        console.error('API Error:', error);
        if (error.response?.data?.message) {
            openAlerts(error.response.data.message, 'error');
        } else {
            openAlerts('Error de conexión', 'error');
        }
    }

    function handleValidationErrors(errors) {
        const firstError = Object.values(errors)[0];
        if (Array.isArray(firstError)) {
            openAlerts(firstError[0], 'error');
        } else {
            openAlerts('Error de validación', 'error');
        }
    }

    /**
     * Función para seleccionar el proveedor desde búsqueda
     * @param {event} item
     * @returns {void}
     */
    function selectPerson(item) {
        providersSelected = item.detail.person.id;
        searchTermProviders = item.detail.label;
        form.person_id = item.detail.person.id;
    }

    function OpenPersonSearchForm() {
        showPersonSearchForm = true;
    }

    function ClosePersonSearchForm() {
        showPersonSearchForm = false;
    }

    function OpenProviderForm() {
        showProviderForm = true;
    }
    function CloseProviderForm() {
        showProviderForm = false;
    }

    function handleKeydown(e) {
        if (e.altKey && e.key === "g") {
            e.preventDefault();
            console.log("Guardando...");
            document.querySelector("button[type='submit']")?.click();
        }
    }
    onMount(() => {
        window.addEventListener("keydown", handleKeydown);
        
        // Initialize form with pre-loaded data
        if (edit && item) {
            purchaseDetails = item.purchase_details || [];
            if (item.proofPayments) {
                // Reconstruct payment types and proof payments from existing data
                paymentTypesSelected = item.proofPayments.map(pp => ({
                    value: pp.payment_type_id,
                    label: pp.payment_type?.paymentTypeDesc || 'Unknown'
                }));
                filterProofPaymentTypes();
            }
        }
    });
    onDestroy(() => {
        window.removeEventListener("keydown", handleKeydown);
    });

    function OpenModal() {
        modal = true;
    }

    function CloseModal() {
        modal = false;
    }

    function filterProofPaymentTypes() {
        if (paymentTypesSelected != null && paymentTypesSelected.length > 0) {
            proofPaymentTypes = paymentTypes
                .filter((x) =>
                    paymentTypesSelected.some((pt) => pt.value === x.id),
                )
                .flatMap((x) =>
                    x.proof_payments.map((p) => ({
                        label: p.proof_payment_desc,
                        paymentTypeDesc: x.paymentTypeDesc,
                        value: p.id,
                        amount: 0,
                        td_pr_desc: "",
                    })),
                );
        } else {
            proofPaymentTypes = [];
        }
    }

    // Form submission with Inertia
    function handleSubmit() {
        // Validate till amount before submission
        if (tillAmounts && tillsSelected.value) {
            const totalAmount = purchaseDetails.reduce(
                (acc, curr) => acc + (curr.product_cost_price * curr.quantity), 0
            );
            const availableAmount = tillAmounts[tillsSelected.value] || 0;
            
            if (totalAmount > availableAmount) {
                openAlerts(`La caja no tiene fondos suficientes. Disponible: $${availableAmount.toFixed(2)}, Requerido: $${totalAmount.toFixed(2)}`, 'error');
                return;
            }
        }

        // Update form data with current values
        form.person_id = providersSelected;
        form.till_id = tillsSelected.value;
        form.purchase_details = purchaseDetails.map((x) => ({
            product_id: x.id,
            pd_qty: x.quantity,
            pd_amount: x.product_cost_price,
        }));
        form.proofPayments = proofPaymentTypes;

        if (edit) {
            form.put(`/purchases/${item.id}`, {
                onSuccess: (page) => {
                    openAlerts('Compra actualizada exitosamente', 'success');
                },
                onError: (errors) => {
                    handleValidationErrors(errors);
                }
            });
        } else {
            form.post('/purchases', {
                onSuccess: (page) => {
                    openAlerts('Compra creada exitosamente', 'success');
                    resetForm();
                },
                onError: (errors) => {
                    handleValidationErrors(errors);
                }
            });
        }
    }

    function resetForm() {
        form.reset();
        purchaseDetails = [];
        proofPaymentTypes = [];
        paymentTypesSelected = [];
        tillsSelected = userTills.length === 1 ? 
            { value: userTills[0].id, label: userTills[0].till_name } : [];
        providersSelected = '';
        searchTermProviders = "";
        tillsSearchTerm = userTills.length === 1 ? userTills[0].till_name : "";
        form.purchase_number = formConfig.purchaseNumber;
    }

    function addDetail(item) {
        let details = purchaseDetails;
        if (details.filter((x) => x.id === item.id).length > 0) {
            let itemIdx = details.findIndex((x) => x.id === item.id);
            if (details[itemIdx].quantity == item.quantity) {
                details[itemIdx].quantity =
                    parseInt(details[itemIdx].quantity) + 1;
            } else {
                details[itemIdx].quantity = item.quantity;
            }
        } else {
            details.push(item);
        }
        purchaseDetails = details;
    }

    function removeDetail(item) {
        let newItem = purchaseDetails;
        let itemIdx = newItem.findIndex((x) => x.id === item.id);
        newItem.splice(itemIdx, 1);
        purchaseDetails = newItem;
    }
</script>

{#if showPersonSearchForm == true}
    <Modal on:close={() => ClosePersonSearchForm()}>
        <SearchPersons
            label="Proveedor"
            type="1"
            on:selectPerson={selectPerson}
            on:close={() => ClosePersonSearchForm()}
        />
    </Modal>
{/if}
{#if showProviderForm == true}
    <Modal on:close={() => CloseProviderForm()}>
        <Form
            on:providerSelected={selectProvider}
            from={"purchases"}
            edit={false}
            on:message={OpenAlertMessage}
            on:close={() => CloseProviderForm()}
        />
    </Modal>
{/if}
{#if modal == true}
    <Modal on:close={() => CloseModal()}>
        <DetailsTable
            {edit}
            on:close={() => CloseModal()}
            on:checked={(event) => addDetail(event.detail)}
            on:remove={(event) => removeDetail(event.detail)}
        />
    </Modal>
{/if}
{#if openAlert}
    <Alert {alertMessage} {alertType} on:close={closeAlert} />
{/if}
<svelte:head>
    <title>{edit == true ? "Actualizar Compra" : "Nueva Compra"}</title>
</svelte:head>
<h3 class="mb-4 text-center text-2xl">
    {#if edit == true}Actualizar Compra{:else}Nueva Compra{/if}
</h3>
<form on:submit|preventDefault={handleSubmit}>
    <div class="grid grid-cols-12">
        <div class="col-span-3 pr-2">
            <Textfield
                errors={form.errors?.person_id ? { message: form.errors.person_id } : null}
                label="Proveedor"
                type="text"
                bind:value={searchTermProviders}
                required={true}
            />
        </div>
        <div class="col-span-2 flex gap-3 items-center mb-2">
            <div class="tooltip" data-tip="Buscar proveedor">
                <button
                    class="btn btn-primary"
                    type="button"
                    on:click={OpenPersonSearchForm}
                >
                    <SearchIcon />
                </button>
            </div>
            <div class="tooltip" data-tip="Agregar proveedor">
                <button
                    class="btn btn-primary"
                    type="button"
                    on:click={OpenProviderForm}
                >
                    +
                </button>
            </div>
        </div>
        <div class="col-span-3 mr-4">
            <Textfield
                label="Num. Factura"
                required={true}
                type="text"
                mask="999-999-9999999"
                bind:value={form.purchase_number}
                errors={form.errors?.purchase_number
                    ? { message: form.errors.purchase_number }
                    : null}
            />
        </div>
        <div class="col-span-4 gap-0">
            <Textfield
                label="Fecha"
                required={true}
                type="date"
                bind:value={form.purchase_date}
                errors={form.errors?.purchase_date
                    ? { message: form.errors.purchase_date }
                    : null}
            />
        </div>
    </div>
    <div class="grid grid-cols-12 mt-4 gap-4">
        <div class="col-span-4">
            <Autocomplete
                errors={form.errors?.till_id ? { message: form.errors.till_id } : null}
                label="Caja"
                bind:item_selected={tillsSelected}
                items={userTills.map((x) => ({ 
                    label: `${x.till_name} (Disponible: $${tillAmounts?.[x.id]?.toFixed(2) || '0.00'})`, 
                    value: x.id 
                }))}
                searchTerm={tillsSearchTerm}
                showDropdown={false}
                loading={false}
                filterdItem={userTills}
            />
        </div>
        <div class="col-span-4">
            <SelectMultiple
                name="paymentTypes"
                options={paymentTypesProcessed}
                placeholder="Seleccione los tipos de pago"
                on:change={handleChangePaymentType}
            />
            <label for="paymentTypes">
                {#if form.errors?.proofPayments}
                    <span class="mt-2 text-base text-red-500 block"
                        >{form.errors.proofPayments}</span
                    >
                {/if}
            </label>
        </div>
        {#if paymentTypesSelected != null && paymentTypesSelected.length > 0}
            {#each proofPaymentTypes as item}
                <div class="col-span-4">
                    <Textfield
                        label={"Monto " + item.paymentTypeDesc}
                        bind:value={item.amount}
                        type="number"
                        errors={form.errors?.amount
                            ? { message: form.errors.amount }
                            : null}
                    />
                    {#if item.paymentTypeDesc !== "Efectivo"}
                        <Textfield
                            label={item.label}
                            bind:value={item.td_pr_desc}
                            errors={form.errors?.td_pr_desc
                                ? { message: form.errors.td_pr_desc }
                                : null}
                        />
                    {/if}
                </div>
            {/each}
        {/if}
        <div class="col-span-12 flex justify-end gap-2 mt-4 mb-2">
            <button class="btn btn-primary" type="submit" disabled={form.processing}> 
                {form.processing ? 'Guardando...' : 'Guardar'} 
            </button>
            <button class="btn btn-secondary" type="button" on:click={() => history.back()}>
                Cancelar
            </button>
        </div>
    </div>
    <hr class="my-4" />
    {#if form.errors?.purchase_details}
        <span class="mt-2 text-base text-red-500 block"
            >{form.errors.purchase_details}</span
        >
    {/if}
    <table class="table w-full">
        <thead>
            <tr>
                <th class="text-center text-lg">
                    <div class="flex items-center justify-center">Cant</div>
                </th>
                <th class="text-center text-lg">
                    <div class="flex items-center justify-center">Producto</div>
                </th>
                <th class="text-center text-lg">
                    <div class="flex items-center justify-center">Iva</div>
                </th>
                <th class="text-center text-lg">
                    <div class="flex items-center justify-center">
                        P. Unitario
                    </div>
                </th>
                <th class="text-center text-lg">
                    <div class="flex items-center justify-center">Total</div>
                </th>
                <th class="text-center text-lg">
                    <div class="flex items-center justify-center">
                        <div
                            class="tooltip"
                            data-tip="Agregar productos al carrito"
                        >
                            <button
                                type="button"
                                class="btn btn-primary"
                                on:click={() => OpenModal()}>Agregar</button
                            >
                        </div>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            {#each purchaseDetails as item, i (item.id)}
                <tr class="hover">
                    <td>
                        <Textfield
                            label=""
                            type="number"
                            bind:value={item.quantity}
                            errors={form.errors?.quantity
                                ? { message: form.errors.quantity }
                                : null}
                        />
                    </td>
                    <td class="text-center">{item.product_name}</td>
                    <td class="text-center">{item.iva_type_percent}</td>
                    <td class="text-center">
                        <Textfield
                            label=""
                            type="number"
                            bind:value={item.product_cost_price}
                            errors={form.errors?.product_cost_price
                                ? { message: form.errors.product_cost_price }
                                : null}
                        />
                    </td>
                    <td class="text-center"
                        >{formatNumber(
                            parseInt(item.product_cost_price) *
                                item.quantity,
                        )}</td
                    >
                </tr>
            {/each}
            {#if purchaseDetails.length > 0}
                <tr>
                    <td colspan="3">Total</td>
                    <td class="text-center">
                        {#if proofPaymentTypes.length > 0}
                            <span
                                class={parseInt(
                                    proofPaymentTypes.reduce(
                                        (acc, curr) =>
                                            acc + parseInt(curr.amount),
                                        0,
                                    ),
                                ) <
                                parseInt(
                                    purchaseDetails.reduce(
                                        (acc, curr) =>
                                            acc +
                                            curr.product_cost_price *
                                                curr.quantity,
                                        0,
                                    ),
                                )
                                    ? "text-red-500 text-base font-bold"
                                    : "text-green-500 text-base font-bold"}
                            >
                                Recibido: {formatNumber(
                                    proofPaymentTypes.reduce(
                                        (acc, curr) =>
                                            acc + parseInt(curr.amount),
                                        0,
                                    ),
                                )}
                            </span>
                        {/if}
                    </td>
                    <td class="text-center">
                        <span>
                            {formatNumber(
                                purchaseDetails
                                    .reduce(
                                        (acc, curr) =>
                                            acc +
                                            curr.product_cost_price *
                                                curr.quantity,
                                        0,
                                    )
                                    .toFixed(2),
                            )}
                        </span>
                    </td>
                </tr>
            {/if}
        </tbody>
    </table>
</form>
