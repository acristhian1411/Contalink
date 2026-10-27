<script>
    import { blur } from 'svelte/transition';
    import { router } from '@inertiajs/svelte';

    import { Tabs, Modal } from '@components/utilities';
    import { formatNumber } from '@components/utilities/NumberFormat.js';

    import Details from './details.svelte';
    import TillsHistory from './TillsHistory.svelte';
    import Transfers from '@pages/Transfers/transfers.svelte';
    import TillActions from './tillActions.svelte';
    import { Alert } from '@components/Alerts';

    export let user;
    export let till;
    export let audits = [];
    export let tillAmount = null;
    export let closeReport = null;
    export let transfers = [];
    export let openTills = [];
    export let history;

    let tabs = [
        'Acciones',
        'Historial',
        'Transferencias',
        'Detalles',
    ];
    let active = 'Acciones';
    let tillActions = false;
    let acctionType = 'open';
    let alertMessage = '';
    let alertType = '';
    let openAlert = false;

    function handleActiveTab(tab) {
        active = tab.detail;
    }

    function OpenAlertMessage(event) {
        alertMessage = event.detail?.message || '';
        alertType = event.detail?.type || 'success';
        openAlert = true;
    }

    function closeAlert() {
        openAlert = false;
        alertMessage = '';
        alertType = '';
    }

    function reload() {
        router.reload();
    }

    function closeModal() {
        tillActions = false;
        acctionType = '';
    }

    function openModal(type) {
        acctionType = type;
        tillActions = true;
    }

    function goTo(route) {
        router.visit(route);
    }
</script>

{#if openAlert}
    <Alert {alertMessage} {alertType} on:close={closeAlert} />
{/if}
<div class="breadcrumbs text-md mb-4">
	<ul>
		<!-- svelte-ignore a11y-click-events-have-key-events -->
        <li>
            <span class="cursor-pointer" on:click={() => goTo('/')}>Inicio</span>
        </li>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/tills')}>Cajas</span></li>
	</ul>
</div>

<Tabs
    {tabs}
    on:active={handleActiveTab}
    {active}
/>

{#if tillActions == true}
    <Modal on:close={() => closeModal()}>
        <TillActions
            type={acctionType}
            amount={acctionType == 'close' ? (tillAmount ?? 0) : 0}
            person_id={user.person_id}
            tillId={till.id}
            {closeReport}
            on:updateData={() => reload()}
            on:close={() => closeModal()}
            on:showAlert={OpenAlertMessage}
        />
    </Modal>
{/if}

{#if active == 'Detalles'}
    <Details {till} {audits} />
{:else if active == 'Acciones'}
    <div transition:blur>
        <h1 class="text-xl font-bold mt-4">Caja: {till.till_name}</h1>
        <h2 class="text-xl font-bold mt-4">Estado: {till.till_status ? 'Abierto' : 'Cerrado'}</h2>
        {#if till.till_status === true}
            <h2 class="text-xl font-bold mt-4">Monto en caja: {formatNumber(tillAmount)}</h2>
        {/if}
        {#if till.till_status != true}
            <button class="btn btn-primary mt-4" on:click={() => openModal('open')}>Abrir caja</button>
        {:else}
            <button class="btn btn-primary mt-4" on:click={() => openModal('deposit')}>Ingresar dinero</button>
            <button class="btn btn-secondary mt-4" on:click={() => openModal('close')}>Cerrar caja</button>
        {/if}
    </div>
{:else if active == 'Historial'}
    <TillsHistory till_id={till.id} {history} />
{:else if active == 'Transferencias'}
    <h1 align="center" class="text-xl font-bold mt-4">Transferencias</h1>
    <Transfers
        till_id={till.id}
        origin={till}
        origin_amount={tillAmount}
        person_id={user.person_id}
        {transfers}
        {openTills}
        on:updateData={() => reload()}
    />
{/if}
