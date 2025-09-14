<script>
    import { onMount } from 'svelte';
    import {blur} from 'svelte/transition';
    import axios from 'axios';
    import { Inertia } from '@inertiajs/inertia';

    import {Tabs,Modal} from '@components/utilities';
    import {formatNumber, unformatNumber} from '@components/utilities/NumberFormat.js';

    import Details from './details.svelte';
    import TillsHistory from './TillsHistory.svelte';
    import Transfers from '@pages/Transfers/transfers.svelte';
    import TillActions from './tillActions.svelte';
    import TillCloseReport from './tillCloseReport.svelte';
    import { Alert } from '@components/Alerts';

	export let user
    export let appUrl
    export let id = 0;
    let till = {};
    let tabs = [
        'Acciones',
        'Historial',
        'Transferencias',
        'Detalles',
        // 'Cierres de caja'
    ];
    let audits = [];
    let till_amount
    let error = null;
    let url = `${appUrl}/api/tills/`;
    let active = 'Acciones';
    let tillActions = false;
    let acctionType = 'open';
    let alertMessage = '';
    let alertType = '';
    let openAlert = false;
    $: id, getTillAmount(id);
    function handleActiveTab(tab) {
        active = tab.detail;
    }

    async function getTillAmount(tillId) {
        axios.get(`${appUrl}/api/tills/${tillId}/amount`).then((response) => {
            till_amount = response.data.till_amount;
        }).catch((err) => {
            error = err.request.response;
        });
    }

    async function fetchData() {
        axios.get(`${url}${id}`).then((response) => {
            till = response.data.data;
            audits = response.data.audits;
        }).catch((err) => {
            error = err.request.response;
        });
    }


    function OpenAlertMessage(data) {
        alertMessage = data.detail?.message || '';
        alertType = data.detail?.type;
        openAlert = true;
    }

    function closeAlert() {
        openAlert = false;
        alertMessage = '';
        alertType = '';
    }

    function closeModal(){
        tillActions = false;
        acctionType = '';
        getTillAmount(id);
    }
    function openModal(type){
        acctionType = type
        tillActions = true;
    }

    function goTo(route){
        Inertia.visit(route);
    }
    onMount(async () => {
        fetchData();
        getTillAmount(id);
    });
</script>
{#if openAlert}
    <Alert {alertMessage} {alertType} on:close={closeAlert} />
{/if}
<div class="breadcrumbs text-md mb-4">
	<ul>
		<!-- svelte-ignore a11y-click-events-have-key-events -->
        <li>
            <span class="cursor-pointer" on:click={() => goTo('/')}>
                Inicio
            </span>
        </li>
        <!-- svelte-ignore a11y-click-events-have-key-events -->
        <li><span class="cursor-pointer" on:click={() => goTo('/tills')}>Cajas</span></li>
	</ul>
</div>

<Tabs 
    tabs={tabs} 
    on:active={handleActiveTab}
    active={active}
    till={till}
/>

{#if tillActions == true}
    <Modal on:close={() => closeModal()}>
        <TillActions 
            type={acctionType} 
            amount={acctionType == 'close' ? till_amount:0}
            person_id={user.person_id} 
            tillId={till.id} 
            on:updateData={() => fetchData()}
            on:close={() => closeModal()}
            alert={OpenAlertMessage}
            on:showAlert={OpenAlertMessage}
        />
    </Modal>
{/if}

{#if active == 'Detalles'}
    <Details {till} {audits}/>
{:else if active == 'Acciones'}
    <div transition:blur>
        <h1 class="text-xl font-bold mt-4">
            Caja: {till.till_name}
        </h1>
        <h2 class="text-xl font-bold mt-4">
            Estado: {till.till_status ? 'Abierto' : 'Cerrado'}
        </h2>
        {#if till.till_status === true}
            <h2 class="text-xl font-bold mt-4">Monto en caja: {formatNumber(till_amount)}</h2>
        {/if}
        {#if till.till_status != true}
            <button class="btn btn-primary mt-4" on:click={() => openModal('open')}>
                Abrir caja
            </button>
        {:else}
            <button class="btn btn-primary mt-4" on:click={() => openModal('deposit')}>
                Ingresar dinero
            </button>
            <button class="btn btn-secondary mt-4" on:click={() => openModal('close')}>
                Cerrar caja
            </button>
        {/if}
    </div>
{:else if active == 'Historial'}
    <TillsHistory till_id={id}/>
{:else if active == 'Transferencias'}
    <h1 align="center" class="text-xl font-bold mt-4">Transferencias</h1>
    <Transfers 
        till_id={id} 
        origin={till}
        origin_amount={till_amount}
        person_id={user.person_id} 
        />
{:else if active == 'Cierres de caja'}
    <TillCloseReport />
    
{/if}
