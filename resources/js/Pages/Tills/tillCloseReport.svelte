<script>
    import { router } from '@inertiajs/svelte';
    import { createEventDispatcher } from 'svelte';
    import { formatNumber } from '@components/utilities/NumberFormat.js';

    const dispatch = createEventDispatcher();
    let errors = null;
    export let tillId;
    export let amount;
    export let person_id;
    export let cierreCaja = null;

    function close() {
        dispatch('close');
    }

    function updateData() {
        dispatch('updateData');
    }

    function showAlert(payload) {
        dispatch('showAlert', payload);
    }

    function handleSubmit(event) {
        event.preventDefault();
        router.post(`/tills/${tillId}/close`, {
            till_id: tillId,
            td_amount: amount,
            person_id: person_id,
        }, {
            onSuccess: () => {
                showAlert({ type: 'success', message: 'Caja cerrada con éxito' });
                updateData();
                close();
            },
            onError: (err) => {
                errors = Object.values(err).flat();
            },
        });
    }

    function openReport() {
        open(`${window.location.origin}/tills/${tillId}/close-detailed`);
    }
</script>

{#if errors}
    <p>
        {#each errors as error}
            {error}
        {/each}
    </p>
{/if}

<div class="p-6 bg-base-200 rounded-lg shadow-md">
    <div class="space-y-4">
        {#if cierreCaja && cierreCaja.incomes.length > 0}
            {#each cierreCaja.incomes as income}
                <div class="flex justify-between">
                    <span class="font-semibold">Ingresos {income.payment_type_desc}:</span>
                    <span class="text-green-500">Gs. {formatNumber(income.total_amount)}</span>
                </div>
            {/each}
        {/if}

        {#if cierreCaja && cierreCaja.expenses.length > 0}
            {#each cierreCaja.expenses as expense}
                <div class="flex justify-between">
                    <span class="font-semibold">Gastos {expense.payment_type_desc}:</span>
                    <span class="text-red-500">Gs. {formatNumber(expense.total_amount)}</span>
                </div>
            {/each}
        {/if}

        <div class="flex justify-between">
            <span class="font-semibold">Total ingresos:</span>
            {#if cierreCaja}
                {#if cierreCaja.incomes.length > 0}
                    <span class="text-green-500">Gs. {formatNumber(cierreCaja.incomes.reduce((acc, curr) => acc + curr.total_amount, 0))}</span>
                {:else}
                    <span class="text-green-500">Gs. {formatNumber(0)}</span>
                {/if}
            {/if}
        </div>

        <div class="flex justify-between">
            <span class="font-semibold">Total gastos:</span>
            {#if cierreCaja}
                {#if cierreCaja.expenses.length > 0}
                    <span class="text-red-500">Gs. {formatNumber(cierreCaja.expenses.reduce((acc, curr) => acc + curr.total_amount, 0))}</span>
                {:else}
                    <span class="text-red-500">Gs. {formatNumber(0)}</span>
                {/if}
            {/if}
        </div>

        <hr class="my-4 border-t" />

        <div class="flex justify-between">
            <span class="font-semibold">Caja actual</span>
            {#if cierreCaja}
                <span>Gs. {formatNumber(
                    cierreCaja.incomes.reduce((acc, curr) => acc + curr.total_amount, 0) -
                    cierreCaja.expenses.reduce((acc, curr) => acc + curr.total_amount, 0)
                )}</span>
            {/if}
        </div>
    </div>
</div>
<button class="btn btn-primary mt-4" on:click={handleSubmit}>Cerrar caja</button>
<button class="btn btn-info mt-4" on:click={openReport}>Mostrar detalles</button>
<button class="btn btn-secondary mt-4" on:click={close}>Cancelar</button>
