<script>
    import axios from "axios";
    import { onMount, onDestroy } from "svelte";
    import { createEventDispatcher } from "svelte";
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
    import { get } from "svelte/store";

    // cosas que se optienen por props
    export let user;
    export let edit;
    export let item;
    export let token = "";

    const dispatch = createEventDispatcher();
    let tillsSelected = [];
    let tills = [];
    let tillsSearchTerm = "";
    let Clients = [];
    let paymentTypes = [];
    let paymentTypesProcessed = [];
    let paymentTypesSelected = [];
    let searchTermPaymentTypes = "";
    let showDropdownPaymentTypes = false;
    let loadingPaymentTypes = false;
    let proofPaymentTypes = [];
    let proofPaymentTypesSelected;
    let ClientsSelected = [];
    let searchTermClients = "";
    let showDropdownClients = false;
    let showPersonSearchForm = false;
    let loading = false;
    let saleDetails = [];
    let id = 0;
    let errors = null;
    let config = {
        headers: {
            authorization: `token: ${token}`,
        },
    };
    let showClientForm;
    let modal = false;
    let openAlert = false;
    let alertMessage = "";
    let alertType = "";
    $: saleDetails, getAmountTotal();
    $: proofPaymentTypes, getAmountReceived();
    // $: paymentTypesSelected, filterProofPaymentTypes();
    let date = new Date();

    // Variables dinámicas para cada campo
    let person_id = "";
    let sale_date = date.toISOString().slice(0, 10);
    let sale_status = "";
    let sale_number = "";

    // region Cajas
    /**
     * Función para obtener las cajas por usuario
     * @param {void}
     * @returns {void}
     */
    function getTillsByUser() {
        axios
            .get(`/api/tills/${user.person_id}/byPerson?wantsJson=true`)
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

    // region Clientes
    /**
     * Función para obtener los Clientes
     * @param {void}
     * @returns {void}
     */
    function getClients() {
        axios
            .get(`/api/persons?p_type_id=2`)
            .then((response) => {
                Clients = response.data.data;
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
     * Función para seleccionar el Cliente
     * @param {event} item
     * @returns {void}
     */
    function selectClient(item) {
        ClientsSelected = item.detail.person.id;
        searchTermClients = item.detail.label;
    }
    // end region

    // region Alertas
    function close() {
        dispatch("close");
    }

    function closeAlert() {
        openAlert = false;
    }
    function openAlerts(message, type) {
        openAlert = true;
        alertType = type;
        alertMessage = message;
    }
    function OpenAlertMessage(event) {
        dispatch("message", event.detail);
    }
    // end region

    function resetForm() {
        person_id = "";
        sale_date = date.toISOString().slice(0, 10);
        sale_status = "";
        sale_number = "";
        saleDetails = [];
        proofPaymentTypes = [];
        paymentTypesSelected = [];
        tillsSelected = [];
        ClientsSelected = [];
        searchTermClients = "";
        searchTermPaymentTypes = "";
        tillsSearchTerm = "";
        errors = null;
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
        getPaymentTypes();
        getClients();
        getTillsByUser();
        if (edit == true) {
            id = item.id;
            person_id = item.person_id;
            sale_date = item.sale_date;
            sale_status = item.sale_status;
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
        console.log("Creating object...");
        try {
            const res = await axios.post(`/api/storesale`, {
                user_id: user.id,
                till_id: tillsSelected.value,
                person_id: ClientsSelected,
                sale_date,
                sale_number,
                sale_details: saleDetails.map((x) => ({
                    product_id: x.id,
                    sd_qty: x.quantity,
                    sd_amount: x.product_selling_price,
                })),
                proofPayments: proofPaymentTypes,
            });
            openAlerts(res.data.message, "success");
            resetForm();
        } catch (err) {
            err.response?.data?.errors
                ? ((errors = err.response.data.errors),
                  openAlerts("Datos enviados no son correctos", "error"))
                : ((errors = err.response.data?.error
                      ? err.response.data.error
                      : null),
                  openAlerts(err.response.data.message, "error"));
        }
    }

    async function handleUpdateObject() {
        try {
            const res = await axios.put(
                `/api/sales/${id}`,
                { person_id, sale_date, sale_status },
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
            on:message={OpenAlertMessage}
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
<form
    on:submit|preventDefault={edit == true
        ? handleUpdateObject()
        : handleCreateObject()}
>
    <div class="grid grid-cols-12">
        <div class="col-span-3 pr-2">
            <Textfield
                errors={errors?.client ? errors.client[0] : []}
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
                bind:value={sale_number}
                errors={errors?.sale_number
                    ? { message: errors.sale_number[0] }
                    : null}
            />
        </div>
        <div class="col-span-4 gap-0">
            <Textfield
                label="Fecha"
                required={true}
                type="date"
                bind:value={sale_date}
                errors={errors?.sale_date
                    ? { message: errors.sale_date[0] }
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
    {#if errors?.sale_details}
        <span class="mt-2 text-base text-red-500 block"
            >{errors.sale_details[0]}</span
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
                            class="peer p-4 block border-gray-200 rounded-lg text-base placeholder:text-transparent focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none
                            focus:pt-6
                            focus:pb-2
                            [&:not(:placeholder-shown)]:pt-6
                            [&:not(:placeholder-shown)]:pb-2
                            autofill:pt-6
                            autofill:pb-2"
                            min="1"
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
                            errors={errors?.product_selling_price
                                ? { message: errors.product_selling_price[0] }
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
