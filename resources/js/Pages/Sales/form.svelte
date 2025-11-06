<script>
    import { onMount, onDestroy } from "svelte";
    import { useForm } from '@inertiajs/svelte';
    import axios from "axios";
    import { SearchIcon } from "@components/Icons/";
    import {
        formatNumber,
        unformatNumber,
    } from "@components/utilities/NumberFormat.js";
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
    import Form from "@pages/Clients/form.svelte";

    // Props from Inertia pre-loading
    export let staticData;
    export let userContext;
    export let formConfig;
    export let mode = 'create';
    export let edit = mode === 'edit';
    export let item = null;

    // Destructure static data from props
    const { paymentTypes, ivaTypes, measurementUnits, categories, brands } = staticData;
    const { user, permissions, userTills } = userContext;
    const { saleNumber, defaultDate, validationRules, businessRules } = formConfig;

    // Form state management with Inertia
    let form = useForm({
        person_id: item?.person_id || '',
        sale_date: item?.sale_date || defaultDate,
        sale_number: item?.sale_number || saleNumber,
        till_id: userTills.length === 1 ? userTills[0].id : (item?.till_id || ''),
        sale_details: item?.sale_details || [],
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
    let ClientsSelected = item?.person_id || '';
    let searchTermClients = item?.person?.person_fname ? 
        `${item.person.person_fname} ${item.person.person_lastname}` : '';
    let showPersonSearchForm = false;
    let showClientForm = false;
    let modal = false;
    let openAlert = false;
    let alertMessage = "";
    let alertType = "";
    let saleDetails = item?.sale_details || [];

    $: saleDetails, getAmountTotal();
    $: proofPaymentTypes, getAmountReceived();

    // Dynamic data functions (using protected APIs)
    async function searchClients(searchTerm) {
        if (searchTerm.length < 3) return [];
        
        try {
            const response = await axios.get('/api/search/clients', {
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
     * Función para seleccionar el Cliente
     * @param {event} item
     * @returns {void}
     */
    function selectClient(item) {
        ClientsSelected = item.detail.person.id;
        searchTermClients = item.detail.label;
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

    function resetForm() {
        form.reset();
        saleDetails = [];
        proofPaymentTypes = [];
        paymentTypesSelected = [];
        tillsSelected = userTills.length === 1 ? 
            { value: userTills[0].id, label: userTills[0].till_name } : [];
        ClientsSelected = '';
        searchTermClients = "";
        tillsSearchTerm = userTills.length === 1 ? userTills[0].till_name : "";
        form.sale_number = formConfig.saleNumber;
    }

    function OpenClientForm() {
        showClientForm = true;
    }
    function CloseClientForm() {
        showClientForm = false;
    }

    function getAmountTotal() {
        if (saleDetails.length == 0) {
            return 0;
        }
        console.log("Calculating total...", saleDetails);

        return saleDetails
            .reduce(
                (acc, curr) => acc + curr.product_selling_price * curr.quantity,
                0,
            )
            .toFixed(2);
    }

    function getAmountReceived() {
        if (proofPaymentTypes.length == 0) {
            return 0;
        }
        return proofPaymentTypes
            .reduce((acc, curr) => acc + parseInt(curr.amount), 0)
            .toFixed(2);
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
            saleDetails = item.sale_details || [];
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

    function OpenPersonSearchForm() {
        showPersonSearchForm = true;
    }

    function ClosePersonSearchForm() {
        showPersonSearchForm = false;
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
        // Update form data with current values
        form.person_id = ClientsSelected;
        form.till_id = tillsSelected.value;
        form.sale_details = saleDetails.map((x) => ({
            product_id: x.id,
            sd_qty: x.quantity,
            sd_amount: x.product_selling_price,
        }));
        form.proofPayments = proofPaymentTypes;

        if (edit) {
            form.put(`/sales/${item.id}`, {
                onSuccess: (page) => {
                    openAlerts('Venta actualizada exitosamente', 'success');
                },
                onError: (errors) => {
                    handleValidationErrors(errors);
                }
            });
        } else {
            form.post('/sales', {
                onSuccess: (page) => {
                    openAlerts('Venta creada exitosamente', 'success');
                    resetForm();
                },
                onError: (errors) => {
                    handleValidationErrors(errors);
                }
            });
        }
    }

    function addDetail(item) {
        let details = saleDetails;
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
        saleDetails = details;
    }

    function removeDetail(item) {
        let newItem = saleDetails;
        let itemIdx = newItem.findIndex((x) => x.id === item.id);
        newItem.splice(itemIdx, 1);
        saleDetails = newItem;
    }
</script>

<svelte:head>
    <title>{edit == true ? "Actualizar Venta" : "Nueva Venta"}</title>
</svelte:head>
{#if showPersonSearchForm == true}
    <Modal on:close={() => ClosePersonSearchForm()}>
        <SearchPersons
            label="Cliente"
            type="2"
            on:selectPerson={selectClient}
            on:close={() => ClosePersonSearchForm()}
        />
    </Modal>
{/if}
{#if showClientForm == true}
    <Modal on:close={() => CloseClientForm()}>
        <Form
            on:ClientSelected={selectClient}
            from={"sales"}
            edit={false}
            on:close={() => CloseClientForm()}
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
<h3 class="mb-4 text-center text-2xl">
    {#if edit == true}Actualizar Venta{:else}Nueva Venta{/if}
</h3>
<form on:submit|preventDefault={handleSubmit}>
    <div class="grid grid-cols-12">
        <div class="col-span-3 pr-2">
            <Textfield
                errors={form.errors?.person_id ? { message: form.errors.person_id } : null}
                label="Cliente"
                type="text"
                bind:value={searchTermClients}
                required={true}
            />
        </div>
        <div class="col-span-2 flex gap-3 items-center mb-2">
            <div class="tooltip" data-tip="Buscar cliente">
                <button
                    class="btn btn-primary"
                    type="button"
                    on:click={OpenPersonSearchForm}
                >
                    <SearchIcon />
                </button>
            </div>
            <div class="tooltip" data-tip="Agregar cliente">
                <button
                    class="btn btn-primary"
                    type="button"
                    on:click={OpenClientForm}
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
                bind:value={form.sale_number}
                errors={form.errors?.sale_number
                    ? { message: form.errors.sale_number }
                    : null}
            />
        </div>
        <div class="col-span-4 gap-0">
            <Textfield
                label="Fecha"
                required={true}
                type="date"
                bind:value={form.sale_date}
                errors={form.errors?.sale_date
                    ? { message: form.errors.sale_date }
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
                items={userTills.map((x) => ({ label: x.till_name, value: x.id }))}
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
    {#if form.errors?.sale_details}
        <span class="mt-2 text-base text-red-500 block"
            >{form.errors.sale_details}</span
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
                                on:click={() => OpenModal()}
                                >Agregar Producto</button
                            >
                        </div>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            {#each saleDetails as item, i (item.id)}
                <tr class="hover">
                    <td class="text-center">
                        <input
                            type="number"
                            step="0.1"
                            class="peer p-4 block border-gray-200 rounded-lg text-base placeholder:text-transparent focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none
                            focus:pt-6
                            focus:pb-2
                            [&:not(:placeholder-shown)]:pt-6
                            [&:not(:placeholder-shown)]:pb-2
                            autofill:pt-6
                            autofill:pb-2"
                            min="0.1"
                            bind:value={item.quantity}
                        />
                        <!-- <Textfield
                            label=""
                            type="number"
                            bind:value={item.quantity}
                            errors={errors?.quantity
                                ? { message: errors.quantity[0] }
                                : null}
                        /> -->
                    </td>
                    <td class="text-center text-xl font-bold"
                        >{item.product_name}</td
                    >
                    <td class="text-center">{item.iva_type_percent}</td>
                    <td class="text-center">
                        <Textfield
                            label=""
                            type="number"
                            bind:value={item.product_selling_price}
                            errors={form.errors?.product_selling_price
                                ? { message: form.errors.product_selling_price }
                                : null}
                        />
                    </td>
                    <td class="text-center text-base font-bold"
                        >{formatNumber(
                            parseInt(item.product_selling_price) *
                                item.quantity,
                        )}</td
                    >
                </tr>
            {/each}
            {#if saleDetails.length > 0}
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
                                    saleDetails.reduce(
                                        (acc, curr) =>
                                            acc +
                                            curr.product_selling_price *
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
                    <td class="text-center text-base font-bold">
                        <span>
                            {formatNumber(
                                saleDetails.reduce(
                                    (acc, curr) =>
                                        acc +
                                        curr.product_selling_price *
                                            curr.quantity,
                                    0,
                                ),
                            )}
                        </span>
                    </td>
                </tr>
            {/if}
        </tbody>
    </table>
</form>
