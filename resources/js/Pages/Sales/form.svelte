<script>
    import { onDestroy, onMount } from "svelte";
    import { useForm } from "@inertiajs/svelte";
    import axios from "axios";
    import { SearchIcon } from "@components/Icons/";
    import { formatNumber } from "@components/utilities/NumberFormat.js";
    import { SearchPersons, SelectMultiple } from "@components/FormComponents";
    import { Modal } from "@components/utilities";
    import { Alert } from "@components/Alerts/";
    import ClientForm from "@pages/Clients/form.svelte";

    export let staticData;
    export let userContext;
    export let formConfig;
    export let mode = "create";
    export let edit = mode === "edit";
    export let item = null;

    const { paymentTypes } = staticData;
    const { userTills } = userContext;
    const { saleNumber, defaultDate } = formConfig;

    let form = useForm({
        person_id: item?.person_id || "",
        sale_date: item?.sale_date || defaultDate,
        sale_number: item?.sale_number || saleNumber,
        till_id: userTills.length === 1 ? userTills[0].id : (item?.till_id || ""),
        sale_details: item?.sale_details || [],
        proofPayments: item?.proofPayments || [],
    });

    let saleDetails = (item?.sale_details || []).map(normalizeDetail);
    let paymentTypesProcessed = paymentTypes.map((type) => ({
        label: type.paymentTypeDesc,
        value: type.id,
        proof_payments: type.proof_payments,
    }));
    let paymentTypesSelected = [];
    let proofPaymentTypes = [];
    let paymentSelectorKey = 0;
    let clientId = item?.person_id || "";
    let clientLabel = getClientLabel(item?.person);
    let clientSearch = clientLabel;
    let clientResults = [];
    let clientsLoading = false;
    let showClientResults = false;
    let productSearch = "";
    let productResults = [];
    let productsLoading = false;
    let showProductResults = false;
    let selectedProductIndex = 0;
    let showPersonSearchForm = false;
    let showClientForm = false;
    let openAlert = false;
    let alertMessage = "";
    let alertType = "";
    let productInput;
    let formElement;
    let clientSearchTimer;
    let productSearchTimer;
    let clientSearchSequence = 0;
    let productSearchSequence = 0;
    let totals = { exenta: 0, five: 0, ten: 0, vat: 0, total: 0 };
    let receivedAmount = 0;

    $: totals = calculateTotals(saleDetails);
    $: receivedAmount = proofPaymentTypes.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0);

    function normalizeDetail(detail) {
        const product = detail.product || detail;
        const ivaType = product.iva_type ?? product.ivaType;
        const measurementUnit = product.measurement_unit ?? product.measurementUnit;
        const percent = detail.iva_type_percent ?? product.iva_type_percent ?? ivaType?.iva_type_percent ?? 0;
        const allowsDecimals = detail.allows_decimals ?? product.allows_decimals ?? measurementUnit?.allows_decimals ?? false;
        return {
            ...product,
            ...detail,
            id: detail.product_id ?? product.id ?? detail.id,
            product_name: detail.product_name ?? product.product_name ?? product.product_desc ?? "Producto",
            product_selling_price: Number(detail.product_selling_price ?? detail.sd_amount ?? product.product_selling_price ?? 0),
            quantity: Number(detail.quantity ?? detail.sd_qty ?? 1),
            iva_type_percent: Number(percent) || 0,
            iva_type_desc: detail.iva_type_desc ?? product.iva_type_desc ?? ivaType?.iva_type_desc ?? "",
            product_quantity: detail.product_quantity ?? product.product_quantity,
            unit_name: detail.unit_name ?? product.unit_name ?? measurementUnit?.unit_name,
            unit_abbreviation: detail.unit_abbreviation ?? product.unit_abbreviation ?? measurementUnit?.unit_abbreviation,
            allows_decimals: [true, 1, "1", "t", "true"].includes(allowsDecimals),
        };
    }

    function getClientLabel(person) {
        if (!person) return "";
        return person.person_corpname || [person.person_fname, person.person_lastname].filter(Boolean).join(" ");
    }

    function calculateTotals(details) {
        const subtotals = { exenta: 0, five: 0, ten: 0 };
        details.forEach((detail) => {
            const amount = (Number(detail.product_selling_price) || 0) * (Number(detail.quantity) || 0);
            const percent = Number(detail.iva_type_percent) || 0;
            if (percent === 5) subtotals.five += amount;
            else if (percent === 10) subtotals.ten += amount;
            else subtotals.exenta += amount;
        });

        const vat = subtotals.five / 21 + subtotals.ten / 11;
        return {
            ...subtotals,
            vat,
            total: subtotals.exenta + subtotals.five + subtotals.ten,
        };
    }

    function money(value) {
        return formatNumber(Math.round(Number(value) || 0));
    }

    async function searchClients(term) {
        const requestId = ++clientSearchSequence;
        if (term.trim().length < 2) {
            clientResults = [];
            clientsLoading = false;
            return;
        }

        clientsLoading = true;
        try {
            const response = await axios.get("/api/search/clients", {
                params: { q: term.trim(), type: 2, limit: 8 },
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (requestId === clientSearchSequence) clientResults = response.data.data || [];
        } catch (error) {
            if (requestId === clientSearchSequence) handleApiError(error);
        } finally {
            if (requestId === clientSearchSequence) clientsLoading = false;
        }
    }

    function handleClientInput() {
        clientSearchSequence++;
        if (clientSearch !== clientLabel) {
            clientId = "";
            form.person_id = "";
        }
        showClientResults = true;
        clearTimeout(clientSearchTimer);
        const term = clientSearch;
        if (term.trim().length < 2) {
            clientSearchSequence++;
            clientResults = [];
            clientsLoading = false;
            return;
        }
        clientResults = [];
        clientsLoading = true;
        clientSearchTimer = setTimeout(() => searchClients(term), 200);
    }

    function chooseClient(person, label = getClientLabel(person)) {
        clientId = person.id;
        form.person_id = person.id;
        clientLabel = label;
        clientSearch = label;
        clientResults = [];
        showClientResults = false;
    }

    function selectClient(event) {
        const selected = event.detail;
        chooseClient(selected.person, selected.label);
    }

    async function loadProducts(term) {
        const requestId = ++productSearchSequence;
        if (term.trim().length < 2) {
            productResults = [];
            productsLoading = false;
            return [];
        }

        productsLoading = true;
        showProductResults = true;
        try {
            const response = await axios.get("/api/products", {
                params: { product_name: term.trim(), product_barcode: term.trim() },
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            const results = (response.data.data || []).map(normalizeDetail);
            if (requestId === productSearchSequence) {
                productResults = results;
                selectedProductIndex = 0;
            }
            return requestId === productSearchSequence ? results : [];
        } catch (error) {
            if (requestId === productSearchSequence) handleApiError(error);
            return [];
        } finally {
            if (requestId === productSearchSequence) productsLoading = false;
        }
    }

    function handleProductInput() {
        productSearchSequence++;
        showProductResults = true;
        clearTimeout(productSearchTimer);
        const term = productSearch;
        if (term.trim().length < 2) {
            productSearchSequence++;
            productResults = [];
            productsLoading = false;
            return;
        }
        productResults = [];
        selectedProductIndex = 0;
        productsLoading = true;
        productSearchTimer = setTimeout(() => loadProducts(term), 180);
    }

    function handleProductKeydown(event) {
        if (event.key === "ArrowDown" && productResults.length) {
            event.preventDefault();
            selectedProductIndex = Math.min(selectedProductIndex + 1, productResults.length - 1);
        } else if (event.key === "ArrowUp" && productResults.length) {
            event.preventDefault();
            selectedProductIndex = Math.max(selectedProductIndex - 1, 0);
        } else if (event.key === "Enter") {
            event.preventDefault();
            if (productResults[selectedProductIndex]) {
                addDetail(productResults[selectedProductIndex]);
            } else if (productSearch.trim().length >= 2) {
                clearTimeout(productSearchTimer);
                loadProducts(productSearch).then((results) => {
                    if (results.length) addDetail(results[0]);
                });
            }
        } else if (event.key === "Escape") {
            showProductResults = false;
        }
    }

    function handleChangePaymentType(event) {
        paymentTypesSelected = event.detail || [];
        filterProofPaymentTypes();
    }

    function filterProofPaymentTypes() {
        const existing = new Map(proofPaymentTypes.map((payment) => [payment.value, payment]));
        proofPaymentTypes = paymentTypes
            .filter((type) => paymentTypesSelected.some((selected) => selected.value === type.id))
            .flatMap((type) => type.proof_payments.map((proof) => ({
                ...existing.get(proof.id),
                label: proof.proof_payment_desc,
                paymentTypeDesc: type.paymentTypeDesc,
                value: proof.id,
                amount: existing.get(proof.id)?.amount ?? 0,
                td_pr_desc: existing.get(proof.id)?.td_pr_desc ?? "",
            })));
    }

    function updatePayment(payment, field, value) {
        proofPaymentTypes = proofPaymentTypes.map((current) => current.value === payment.value
            ? { ...current, [field]: value }
            : current);
    }

    function updateDetail(detail, field, value) {
        const nextValue = value === "" ? "" : (Number(value) || 0);
        saleDetails = saleDetails.map((current) => current.id === detail.id
            ? { ...current, [field]: nextValue }
            : current);
    }

    function addDetail(rawProduct) {
        const product = normalizeDetail(rawProduct);
        const index = saleDetails.findIndex((detail) => String(detail.id) === String(product.id));
        if (index >= 0) {
            const updated = [...saleDetails];
            const current = updated[index];
            const step = current.allows_decimals ? 0.1 : 1;
            updated[index] = { ...current, quantity: (Number(current.quantity) || 0) + step };
            saleDetails = updated;
        } else {
            saleDetails = [...saleDetails, { ...product, quantity: 1 }];
        }
        productSearch = "";
        productResults = [];
        showProductResults = false;
        productInput?.focus();
    }

    function removeDetail(id) {
        saleDetails = saleDetails.filter((detail) => detail.id !== id);
    }

    function openAlerts(message, type) {
        openAlert = true;
        alertType = type;
        alertMessage = message;
    }

    function closeAlert() {
        openAlert = false;
    }

    function handleApiError(error) {
        console.error("API Error:", error);
        openAlerts(error.response?.data?.message || "Error de conexión", "error");
    }

    function handleValidationErrors(errors) {
        const firstError = Object.values(errors)[0];
        openAlerts(Array.isArray(firstError) ? firstError[0] : "Error de validación", "error");
    }

    function resetForm() {
        form.reset();
        form.person_id = "";
        form.sale_date = defaultDate;
        form.sale_number = saleNumber;
        form.till_id = userTills.length === 1 ? userTills[0].id : "";
        saleDetails = [];
        proofPaymentTypes = [];
        paymentTypesSelected = [];
        paymentSelectorKey++;
        clientId = "";
        clientLabel = "";
        clientSearch = "";
        productSearch = "";
    }

    function OpenClientForm() {
        showClientForm = true;
    }

    function cancelSale() {
        if ((saleDetails.length || proofPaymentTypes.length) && !window.confirm("Se perderán los datos de la venta. ¿Cancelar?")) return;
        window.history.back();
    }

    function handleSubmit() {
        form.person_id = clientId;
        form.sale_details = saleDetails.map((detail) => ({
            product_id: detail.id,
            sd_qty: detail.quantity,
            sd_amount: detail.product_selling_price,
        }));
        form.proofPayments = proofPaymentTypes;

        const options = {
            onSuccess: () => {
                openAlerts(edit ? "Venta actualizada exitosamente" : "Venta creada exitosamente", "success");
                if (!edit) resetForm();
            },
            onError: handleValidationErrors,
        };
        if (edit) form.put(`/sales/${item.id}`, options);
        else form.post("/sales", options);
    }

    function handleKeydown(event) {
        if (event.altKey && event.key.toLowerCase() === "g") {
            event.preventDefault();
            formElement?.requestSubmit();
        } else if (event.key === "F2") {
            event.preventDefault();
            productInput?.focus();
        } else if (event.key === "F9" && canSave) {
            event.preventDefault();
            formElement?.requestSubmit();
        }
    }

    function closeDropdowns(event) {
        if (!event.target.closest("[data-client-combo]")) showClientResults = false;
        if (!event.target.closest("[data-product-combo]")) showProductResults = false;
    }

    $: canSave = !form.processing && saleDetails.length > 0 && !!clientId && !!form.till_id && proofPaymentTypes.length > 0;

    onMount(() => {
        window.addEventListener("keydown", handleKeydown);
        document.addEventListener("click", closeDropdowns);
        if (edit && item?.proofPayments) {
            paymentTypesSelected = item.proofPayments.map((payment) => ({
                value: payment.payment_type_id,
                label: payment.payment_type?.paymentTypeDesc || "",
            }));
            filterProofPaymentTypes();
        }
        productInput?.focus();
    });

    onDestroy(() => {
        window.removeEventListener("keydown", handleKeydown);
        document.removeEventListener("click", closeDropdowns);
        clearTimeout(clientSearchTimer);
        clearTimeout(productSearchTimer);
    });
</script>

<svelte:head>
    <title>{edit ? "Actualizar Venta" : "Nueva Venta"}</title>
</svelte:head>

{#if showPersonSearchForm}
    <Modal on:close={() => showPersonSearchForm = false}>
        <SearchPersons
            label="Cliente"
            type="2"
            on:selectPerson={selectClient}
            on:close={() => showPersonSearchForm = false}
        />
    </Modal>
{/if}
{#if showClientForm}
    <Modal on:close={() => showClientForm = false}>
        <ClientForm
            on:ClientSelected={selectClient}
            from="sales"
            edit={false}
            on:close={() => showClientForm = false}
        />
    </Modal>
{/if}
{#if openAlert}
    <Alert {alertMessage} {alertType} on:close={closeAlert} />
{/if}

<form bind:this={formElement} on:submit|preventDefault={handleSubmit}>
    <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm opacity-60">Administración › Ventas › {edit ? "Editar" : "Nueva"}</p>
            <h1 class="text-2xl font-semibold">{edit ? "Actualizar venta" : "Nueva venta"}</h1>
        </div>
        <button class="btn btn-ghost" type="button" on:click={cancelSale}>Cancelar venta</button>
    </header>

    <section class="rounded-xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-6" aria-label="Datos de la venta">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="relative sm:col-span-2" data-client-combo>
                <label class="mb-1 block text-sm font-medium" for="sale-client">Cliente <span class="text-error">*</span></label>
                <div class="flex gap-2">
                    <input
                        id="sale-client"
                        class="input input-bordered w-full"
                        class:input-error={form.errors?.person_id}
                        type="text"
                        autocomplete="off"
                        placeholder="Buscar por nombre o RUC"
                        bind:value={clientSearch}
                        on:input={handleClientInput}
                        on:focus={() => { if (clientSearch.trim().length >= 2) showClientResults = true; }}
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded={showClientResults}
                        aria-controls="sale-client-results"
                        required
                    />
                    <button class="btn btn-square btn-primary" type="button" aria-label="Buscar cliente" on:click={() => showPersonSearchForm = true}>
                        <SearchIcon />
                    </button>
                    <button class="btn btn-square btn-outline" type="button" aria-label="Crear cliente" on:click={OpenClientForm}>+</button>
                </div>
                {#if form.errors?.person_id}<p class="mt-1 text-sm text-error">{form.errors.person_id}</p>{/if}
                {#if showClientResults}
                    <div class="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-box border border-base-300 bg-base-100 shadow-xl">
                        <div id="sale-client-results" role="listbox" aria-label="Resultados de clientes">
                            {#if clientsLoading}
                                <p class="p-3 text-sm opacity-70">Buscando clientes…</p>
                            {:else if clientResults.length}
                                {#each clientResults as person (person.id)}
                                    <button class="flex w-full items-center justify-between gap-3 p-3 text-left hover:bg-base-200" type="button" role="option" aria-selected="false" on:click={() => chooseClient(person)}>
                                        <span>{getClientLabel(person)}</span>
                                        <span class="text-sm opacity-60">{person.person_ruc || person.person_idnumber || ""}</span>
                                    </button>
                                {/each}
                            {:else if clientSearch.trim().length >= 2}
                                <p class="p-3 text-sm opacity-70">No se encontraron clientes.</p>
                            {/if}
                        </div>
                        <button class="w-full border-t border-base-300 p-3 text-left font-medium text-primary hover:bg-base-200" type="button" on:click={OpenClientForm}>+ Nuevo cliente</button>
                    </div>
                {/if}
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium" for="sale-number">Factura Nº</label>
                <input id="sale-number" class="input input-bordered w-full" value={form.sale_number} readonly />
                <p class="mt-1 text-xs opacity-60">Se asigna al guardar</p>
                {#if form.errors?.sale_number}<p class="mt-1 text-sm text-error">{form.errors.sale_number}</p>{/if}
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium" for="sale-date">Fecha <span class="text-error">*</span></label>
                <input id="sale-date" class="input input-bordered w-full" type="date" bind:value={form.sale_date} required />
                {#if form.errors?.sale_date}<p class="mt-1 text-sm text-error">{form.errors.sale_date}</p>{/if}
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium" for="sale-till">Caja <span class="text-error">*</span></label>
                <select id="sale-till" class="select select-bordered w-full" class:select-error={form.errors?.till_id} bind:value={form.till_id} required>
                    <option value="" disabled>Seleccione una caja</option>
                    {#each userTills as till (till.id)}
                        <option value={till.id}>{till.till_name}</option>
                    {/each}
                </select>
                {#if form.errors?.till_id}<p class="mt-1 text-sm text-error">{form.errors.till_id}</p>{/if}
            </div>
        </div>
    </section>

    <section class="mt-6" aria-label="Productos">
        <h2 class="mb-2 text-lg font-semibold">Productos</h2>
        <div class="relative" data-product-combo>
            <label class="sr-only" for="sale-product">Buscar o escanear producto</label>
            <input
                bind:this={productInput}
                id="sale-product"
                class="input input-bordered input-lg w-full"
                type="search"
                autocomplete="off"
                placeholder="Buscar por nombre o escanear código de barras · Enter para agregar"
                bind:value={productSearch}
                on:input={handleProductInput}
                on:keydown={handleProductKeydown}
                on:focus={() => { if (productSearch.trim().length >= 2) showProductResults = true; }}
                role="combobox"
                aria-autocomplete="list"
                aria-expanded={showProductResults}
                aria-controls="sale-product-results"
            />
            {#if showProductResults}
                <div id="sale-product-results" class="absolute z-30 mt-1 max-h-72 w-full overflow-auto rounded-box border border-base-300 bg-base-100 shadow-xl" role="listbox" aria-label="Resultados de productos">
                    {#if productsLoading}
                        <p class="p-3 text-sm opacity-70">Buscando productos…</p>
                    {:else if productResults.length}
                        {#each productResults as product, index (product.id)}
                            <button
                                class="flex w-full items-center justify-between gap-4 p-3 text-left hover:bg-base-200"
                                class:bg-base-200={selectedProductIndex === index}
                                type="button"
                                role="option"
                                aria-selected={selectedProductIndex === index}
                                on:mouseenter={() => selectedProductIndex = index}
                                on:click={() => addDetail(product)}
                            >
                                <span>
                                    <span class="block font-medium">{product.product_name}</span>
                                    <span class="text-xs opacity-60">Stock: {product.product_quantity ?? 0} {product.unit_abbreviation || "u"}</span>
                                </span>
                                <span class="whitespace-nowrap text-sm opacity-70">Gs. {money(product.product_selling_price)} · IVA {product.iva_type_percent || "Exenta"}{Number(product.iva_type_percent) ? "%" : ""}</span>
                            </button>
                        {/each}
                    {:else if productSearch.trim().length >= 2}
                        <p class="p-3 text-sm opacity-70">Sin resultados para esa búsqueda.</p>
                    {/if}
                </div>
            {/if}
        </div>

        {#if form.errors?.sale_details}<p class="mt-2 text-sm text-error">{form.errors.sale_details}</p>{/if}
        <div class="mt-3 overflow-x-auto rounded-xl border border-base-300 bg-base-100">
            <table class="table min-w-[680px]">
                <thead>
                    <tr>
                        <th class="w-28">Cant.</th>
                        <th>Producto</th>
                        <th class="w-24">IVA</th>
                        <th class="w-36 text-right">P. unitario</th>
                        <th class="w-36 text-right">Total</th>
                        <th class="w-24 text-right"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    {#each saleDetails as detail (detail.id)}
                        <tr>
                            <td>
                                <input
                                    class="input input-bordered input-sm w-24 text-center"
                                    type="number"
                                    min={detail.allows_decimals ? "0.1" : "1"}
                                    step={detail.allows_decimals ? "0.1" : "1"}
                                    aria-label={`Cantidad de ${detail.product_name}`}
                                    value={detail.quantity}
                                    on:input={(event) => updateDetail(detail, "quantity", event.currentTarget.value)}
                                />
                            </td>
                            <td>
                                <span class="block font-medium">{detail.product_name}</span>
                                {#if detail.unit_name}<span class="text-xs opacity-60">{detail.unit_name}</span>{/if}
                            </td>
                            <td><span class="badge badge-ghost">{Number(detail.iva_type_percent) ? `${detail.iva_type_percent}%` : "Exenta"}</span></td>
                            <td class="text-right">
                                <input
                                    class="input input-bordered input-sm w-32 text-right"
                                    type="number"
                                    min="0"
                                    step="1"
                                    aria-label={`Precio unitario de ${detail.product_name}`}
                                    value={detail.product_selling_price}
                                    on:input={(event) => updateDetail(detail, "product_selling_price", event.currentTarget.value)}
                                />
                            </td>
                            <td class="text-right font-semibold">{money(Number(detail.product_selling_price) * Number(detail.quantity))}</td>
                            <td class="text-right">
                                <button class="btn btn-ghost btn-sm text-error" type="button" aria-label={`Quitar ${detail.product_name}`} on:click={() => removeDetail(detail.id)}>Quitar</button>
                            </td>
                        </tr>
                    {:else}
                        <tr>
                            <td colspan="6" class="py-10 text-center text-sm opacity-60">
                                <strong class="mb-1 block text-base-content">Todavía no hay productos</strong>
                                Busca por nombre o escanea un código para agregar el primero.
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <div class="card mt-4 ml-auto max-w-md border border-base-300 bg-base-100 shadow-sm" aria-live="polite">
            <div class="card-body gap-1 p-4">
                <div class="flex justify-between text-sm opacity-70"><span>Subtotal exentas</span><span>{money(totals.exenta)}</span></div>
                <div class="flex justify-between text-sm opacity-70"><span>Subtotal 5%</span><span>{money(totals.five)}</span></div>
                <div class="flex justify-between text-sm opacity-70"><span>Subtotal 10%</span><span>{money(totals.ten)}</span></div>
                <div class="flex justify-between text-sm opacity-70"><span>Liquidación IVA (5% + 10%)</span><span>{money(totals.vat)}</span></div>
                <div class="mt-2 flex justify-between border-t border-base-300 pt-3 text-xl font-bold"><span>Total</span><span>Gs. {money(totals.total)}</span></div>
            </div>
        </div>
    </section>

    <footer class="sticky bottom-0 z-20 -mx-4 mt-6 border-t border-base-300 bg-base-100/95 p-4 shadow-lg backdrop-blur sm:-mx-6 sm:px-6">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
            <fieldset class="w-full xl:max-w-xs">
                <legend class="mb-1 text-sm font-medium">Tipo de pago <span class="text-error">*</span></legend>
                {#key paymentSelectorKey}
                    <SelectMultiple
                        name="paymentTypes"
                        options={paymentTypesProcessed}
                        placeholder="Seleccione los tipos de pago"
                        on:change={handleChangePaymentType}
                    />
                {/key}
                {#if form.errors?.proofPayments}<p class="mt-1 text-sm text-error">{form.errors.proofPayments}</p>{/if}
            </fieldset>

            {#if proofPaymentTypes.length}
                <div class="flex max-h-32 flex-1 flex-wrap gap-3 overflow-y-auto">
                    {#each proofPaymentTypes as payment (payment.value)}
                        <div class="min-w-36 flex-1">
                            <label class="mb-1 block text-xs opacity-70" for={`payment-${payment.value}`}>Monto {payment.paymentTypeDesc}</label>
                            <input id={`payment-${payment.value}`} class="input input-bordered input-sm w-full" type="number" min="0" step="1" value={payment.amount} on:input={(event) => updatePayment(payment, "amount", event.currentTarget.value)} />
                            {#if payment.paymentTypeDesc !== "Efectivo"}
                                <input class="input input-bordered input-sm mt-1 w-full" type="text" aria-label={payment.label} placeholder={payment.label} value={payment.td_pr_desc} on:input={(event) => updatePayment(payment, "td_pr_desc", event.currentTarget.value)} />
                            {/if}
                        </div>
                    {/each}
                </div>
            {/if}

            <div class="flex items-end justify-between gap-4 xl:ml-auto xl:justify-end">
                {#if proofPaymentTypes.length}
                    <div class="text-right text-xs opacity-70">
                        <span class="block">Recibido: Gs. {money(receivedAmount)}</span>
                        {#if receivedAmount < totals.total}<span class="text-error">Pendiente: Gs. {money(totals.total - receivedAmount)}</span>{/if}
                    </div>
                {/if}
                <div class="whitespace-nowrap text-right">
                    <small class="block text-xs opacity-60">Total a cobrar (Gs.)</small>
                    <strong class="text-xl tabular-nums">{money(totals.total)}</strong>
                </div>
                <button class="btn btn-primary min-w-36" type="submit" disabled={!canSave}>
                    {form.processing ? "Guardando…" : "Guardar venta"}
                </button>
            </div>
        </div>
        {#if !proofPaymentTypes.length}<p class="mt-2 text-xs opacity-60">Selecciona un tipo de pago para guardar la venta. Atajos: F2 buscar producto · F9 guardar.</p>{/if}
    </footer>
</form>
