<script>
    import { router } from '@inertiajs/svelte';
    import { createEventDispatcher } from 'svelte';
    import { formatNumber } from '@components/utilities/NumberFormat.js';
    import { Textfield, Autocomplete } from '@components/FormComponents';

    const dispatch = createEventDispatcher();

    export let till_id;
    export let origin;
    export let person_id;
    export let origin_amount;
    export let openTills = [];

    let og_amount_to_transfer = 0;
    let transfer_obs = '';
    let tillSelected;
    let searchTill = '';
    let errors = null;

    function handleSubmit(event) {
        event.preventDefault();

        router.post(`/tills/${till_id}/transfer`, {
            person_id: person_id,
            destiny_id: tillSelected?.value,
            t_transfer_amount: og_amount_to_transfer,
            t_transfer_obs: transfer_obs,
        }, {
            onSuccess: () => {
                dispatch('updateData');
                dispatch('close');
            },
            onError: (err) => {
                errors = Object.values(err).flat();
            },
        });
    }
</script>

<h1 align="center" class="text-xl font-bold mt-4 mb-4">Transferencias</h1>
<form on:submit|preventDefault={handleSubmit}>
    <div class="grid grid-cols-2 gap-3 mb-2">
        <div>
            <div class="flex flex-col items-start">
                <div class="text-md mt-1">
                    <p>Caja de origen: {origin.till_name}</p>
                </div>
                <div class="text-md mt-1">
                    <p>Monto Actual: {formatNumber(origin_amount)}</p>
                </div>
            </div>
        </div>
        <div>
            <Autocomplete
                errors={errors}
                label="Caja destino"
                bind:item_selected={tillSelected}
                items={openTills.map((x) => ({ label: x.till_name, value: x.id }))}
                searchTerm={searchTill}
                showDropdown={false}
                loading={false}
            />
        </div>
    </div>
    <div class="text-md mt-1">
        <Textfield
            label="Monto a transferir"
            type="number"
            bind:value={og_amount_to_transfer}
            errors={errors?.amount ? { message: errors.amount[0] } : null}
        />
    </div>
    <div class="text-md mt-1">
        <Textfield
            label="Observaciones"
            type="text"
            bind:value={transfer_obs}
            errors={errors?.transfer_obs ? { message: errors.transfer_obs[0] } : null}
        />
    </div>
    {#if errors}
        <div class="mt-4">
            <ul class="list-disc list-inside text-red-500">
                {#each errors as error}
                    <li>{error}</li>
                {/each}
            </ul>
        </div>
    {/if}
    <button class="btn btn-primary mt-4" type="submit">Transferir</button>
</form>
