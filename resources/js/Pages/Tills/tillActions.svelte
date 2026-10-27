<script>
    import { router } from '@inertiajs/svelte';
    import { createEventDispatcher } from 'svelte';
    import { Textfield } from '@components/FormComponents';
    import TillCloseReport from './tillCloseReport.svelte';

    const dispatch = createEventDispatcher();
    export let type;
    export let tillId = 0;
    export let person_id = 0;
    export let amount = 0;
    export let closeReport = null;
    let errors = null;

    function updateData() {
        dispatch('updateData');
    }

    function close() {
        dispatch('close');
    }

    function handleSubmit(event) {
        event.preventDefault();

        router.post(`/tills/${tillId}/${type}`, {
            till_id: tillId,
            td_amount: amount,
            person_id: person_id,
        }, {
            onSuccess: () => {
                dispatch('showAlert', { type: 'success', message: type == 'open' ? 'Caja abierta con éxito' : 'Ingreso realizado con éxito' });
                updateData();
                close();
            },
            onError: (err) => {
                errors = Object.values(err).flat();
            },
        });
    }
</script>

<h1 class="text-xl font-bold mt-4 text-center">
    {#if type == 'open'}
        Apertura caja
    {:else if type == 'close'}
        Cierre de caja
    {:else if type == 'deposit'}
        Ingresar dinero
    {/if}
</h1>

{#if type == 'open'}
    <form on:submit|preventDefault={handleSubmit}>
        <div class="grid grid-cols-12 gap-4 mt-4 mb-4">
            <div class="col-span-4">
                <Textfield label="Monto" required={true} type="number" bind:value={amount} />
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Abrir caja</button>
    </form>
{:else if type == 'close'}
    <div class="gap-4 mt-4 mb-4">
        <TillCloseReport
            {tillId}
            {amount}
            {person_id}
            cierreCaja={closeReport}
            on:updateData={() => updateData()}
            on:close={() => close()}
            on:showAlert={(e) => dispatch('showAlert', e.detail)}
        />
    </div>
{:else if type == 'deposit'}
    <form on:submit|preventDefault={handleSubmit}>
        <div class="grid grid-cols-12 gap-4 mt-4 mb-4">
            <div class="col-span-6">
                <Textfield label="Monto" required={true} type="number" bind:value={amount} />
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Ingresar dinero</button>
    </form>
{/if}

{#if errors}
    <div class="mt-4">
        <ul class="list-disc list-inside text-red-500">
            {#each errors as error}
                <li>{error}</li>
            {/each}
        </ul>
    </div>
{/if}
