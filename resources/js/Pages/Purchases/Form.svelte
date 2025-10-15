<script>
    import axios from "axios";
    import { onMount, onDestroy } from "svelte";
    import {
        formatNumber,
        unformatNumber,
    } from "@components/utilities/NumberFormat.js";
    import { SearchIcon } from "@components/Icons/";
    import { createEventDispatcher } from "svelte";
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

    // cosas que se optienen por props
    export let user;
    export let edit;
    export let item;
    export let token = "";

    const dispatch = createEventDispatcher();

    let tillsSelected = [];
    let tills = [];
    let tillsSearchTerm = "";
    let providers = [];
    let paymentTypes = [];
    let paymentTypesProcessed = [];
    let paymentTypesSelected;
    let searchTermPaymentTypes = "";
    let showDropdownPaymentTypes = false;
    let loadingPaymentTypes = false;
    let proofPaymentTypes = [];
    let proofPaymentTypesSelected;
    let providersSelected = [];
    let searchTermProviders = "";
    let showDropdownProviders = false;
    let showPersonSearchForm = false;
    let loading = false;
    let purchaseDetails = [];
    let id = 0;
    let errors = null;
    let config = {
        headers: {
            authorization: `token: ${token}`,
        },
    };
    let showProviderForm;
    let modal = false;
    let openAlert = false;
    let alertMessage = "";
    let alertType = "";
    $: purchaseDetails;
    // $: paymentTypesSelected, filterProofPaymentTypes();
    let date = new Date();

    // Variables dinámicas para cada campo
    let person_id = "";
    let purchase_date = date.toISOString().slice(0, 10);
    let purchase_status = "";
    let purchase_number = "";

    // region Cajas
    /**
     * Función para obtener las cajas por usuario
     * @param {void}
     * @returns {void}
     */
    function getTillsByUser() {
        axios
            .get(`/api/tills/${user.person_id}/byPerson`)
            .then((response) => {
                tills = response.data.data;
                if (tills.length === 1) {
                    tillsSelected = {
                        value: tills[0].id,
                        label: tills[0].till_name,
                    };
                    tillsSearchTerm = tills[0].till_name;
                }
            })
            .catch((err) => {
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response.data.message,
                    },
                };
                OpenAlertMessage(detail);
            });
    }
    // end region

    // region Tipos de pago
    /**
     * Función para obtener los tipos de pago
     * @param {void}
     * @returns {void}
     */
    function getPaymentTypes() {
        axios
            .get(`/api/paymenttypes`)
            .then((response) => {
                paymentTypes = response.data.data;
                paymentTypesProcessed = paymentTypes.map((x) => ({
                    label: x.paymentTypeDesc,
                    value: x.id,
                    proof_payments: x.proof_payments,
                }));
            })
            .catch((err) => {
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response.data.message,
                    },
                };
            });
    }

    function handleChangePaymentType(event) {
        paymentTypesSelected = event.detail;
        filterProofPaymentTypes();
    }
    // end region

    // region Proveedores
    /**
     * Función para obtener los proveedores
     * @param {void}
     * @returns {void}
     */
    function getProviders() {
        axios
            .get(`/api/persons?p_type_id=1`)
            .then((response) => {
                providers = response.data.data;
            })
            .catch((err) => {
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response.data.message,
                    },
                };
            });
    }

    /**
     * Función para buscar los proveedores por nombre
     * @param {event} event
     * @returns {void}
     */
    function searchProvividers(event) {
        let search = event.detail;
        axios
            .get(`/api/persons-search-by-type/1?search=${search}`)
            .then((response) => {
                providers = response.data.data.map((x) => ({
                    label: x.person_fname + " " + x.person_lastname,
                    value: x.id,
                }));
            })
            .catch((err) => {
                let detail = {
                    detail: {
                        type: "delete",
                        message: err.response.data.message,
                    },
                };
            });
    }

    /**
     * Función para seleccionar el proveedor
     * @param {event} item
     * @returns {void}
     */
    function selectProvider(item) {
        providersSelected = item.detail;
        searchTermProviders = item.detail.label;
    }
    // end region

    // region Alertas
    function close() {
        dispatch("close");
    }

    function closeAlert() {
        openAlert = false;
    }

    /**
     * Función para seleccionar el Cliente
     * @param {event} item
     * @returns {void}
     */
    function selectPerson(item) {
        providersSelected = item.detail.person.id;
        searchTermProviders = item.detail.label;
    }

    function OpenPersonSearchForm() {
        showPersonSearchForm = true;
    }

    function ClosePersonSearchForm() {
        showPersonSearchForm = false;
    }

    function openAlerts(message, type) {
        openAlert = true;
        alertType = type;
        alertMessage = message;
    }
    // end region

    function OpenAlertMessage(event) {
        dispatch("message", event.detail);
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
        getPaymentTypes();
        getProviders();
        getTillsByUser();
        if (edit == true) {
            id = item.id;
            person_id = item.person_id;
            purchase_date = item.purchase_date;
            purchase_status = item.purchase_status;
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

    function filterProofPaymentTypes(event) {
        if (paymentTypesSelected != null) {
            proofPaymentTypes = paymentTypes
                .filter((x) =>
                    paymentTypesSelected.some((pt) => pt.value === x.id),
                )
                .flatMap((x) =>
                    x.proofPayments.map((p) => ({
                        label: p.proof_payment_desc,
                        paymentTypeDesc: x.paymentTypeDesc,
                        value: p.id,
                        amount: 0,
                        td_pr_desc: "",
                    })),
                );
        }
    }

    async function handleCreateObject() {
        try {
            const res = await axios.post(`/api/storePurchase`, {
                user_id: user.id,
                till_id: tillsSelected.value,
                person_id: providersSelected,
                purchase_date,
                purchase_number,
                purchase_details: purchaseDetails.map((x) => ({
                    product_id: x.id,
                    pd_qty: x.quantity,
                    pd_amount: x.product_selling_price,
                })),
                proofPayments: proofPaymentTypes,
            });
            openAlerts(res.data.message, "success");
        } catch (err) {
            errors = err.response.data.details
                ? err.response.data.details
                : null;

            openAlerts(err.response.data.message, "delete");
        }
    }

    async function handleUpdateObject() {
        try {
            const res = await axios.put(
                `/api/purchases/${id}`,
                { person_id, purchase_date, purchase_status },
                config,
            );
            let detail = {
                detail: {
                    type: "success",
                    message: res.data.message,
                },
            };
            OpenAlertMessage(detail);
            close();
        } catch (err) {
            errors = err.response.data.details
                ? err.response.data.details
                : null;
            let detail = {
                detail: {
                    type: "error",
                    message: err.response.data.message,
                },
            };
            OpenAlertMessage(detail);
        }
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
<form
    on:submit|preventDefault={edit == true
        ? handleUpdateObject()
        : handleCreateObject()}
>
    <div class="grid grid-cols-12">
        <div class="col-span-3 pr-2">
            <Textfield
                errors={errors?.provider ? errors.provider[0] : []}
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
                bind:value={purchase_number}
                errors={errors?.purchase_number
                    ? { message: errors.purchase_number[0] }
                    : null}
            />
        </div>
        <div class="col-span-4 gap-0">
            <Textfield
                label="Fecha"
                required={true}
                type="date"
                bind:value={purchase_date}
                errors={errors?.purchase_date
                    ? { message: errors.purchase_date[0] }
                    : null}
            />
        </div>
    </div>
    <div class="grid grid-cols-12 mt-4 gap-4">
        <div class="col-span-4">
            <Autocomplete
                {errors}
                label="Caja"
                bind:item_selected={tillsSelected}
                items={tills.map((x) => ({ label: x.till_name, value: x.id }))}
                searchTerm={tillsSearchTerm}
                showDropdown={showDropdownPaymentTypes}
                loading={loadingPaymentTypes}
                filterdItem={tills}
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
                {#if errors?.proofPayments}
                    <span class="mt-2 text-base text-red-500 block"
                        >{errors.proofPayments[0]}</span
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
                        errors={errors?.td_pr_desc
                            ? { message: errors.td_pr_desc[0] }
                            : null}
                    />
                    {#if item.paymentTypeDesc !== "Efectivo"}
                        <Textfield
                            label={item.label}
                            bind:value={item.td_pr_desc}
                            errors={errors?.td_pr_desc
                                ? { message: errors.td_pr_desc[0] }
                                : null}
                        />
                    {/if}
                </div>
            {/each}
        {/if}
        <div class="col-span-12 flex justify-end gap-2 mt-4 mb-2">
            <button class="btn btn-primary" type="submit"> Guardar </button>
            <button class="btn btn-secondary" on:click={close}>
                Cancelar
            </button>
        </div>
    </div>
    <hr class="my-4" />
    {#if errors?.purchase_details}
        <span class="mt-2 text-base text-red-500 block"
            >{errors.purchase_details[0]}</span
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
                            errors={errors?.quantity
                                ? { message: errors.quantity[0] }
                                : null}
                        />
                    </td>
                    <td class="text-center">{item.product_name}</td>
                    <td class="text-center">{item.iva_type_percent}</td>
                    <td class="text-center">
                        <Textfield
                            label=""
                            type="number"
                            bind:value={item.product_selling_price}
                            errors={errors?.product_selling_price
                                ? { message: errors.product_selling_price[0] }
                                : null}
                        />
                    </td>
                    <td class="text-center"
                        >{formatNumber(
                            parseInt(item.product_selling_price) *
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
                    <td class="text-center">
                        <span>
                            {formatNumber(
                                purchaseDetails
                                    .reduce(
                                        (acc, curr) =>
                                            acc +
                                            curr.product_selling_price *
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
