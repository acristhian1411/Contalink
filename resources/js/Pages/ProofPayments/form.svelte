<script>
    import { onMount } from 'svelte';
    import axios from 'axios';
    import {Textfield, Autocomplete} from '@components/FormComponents';
    import { createEventDispatcher } from 'svelte';
    import { DeleteModal } from '@components/utilities';

    const dispatch = createEventDispatcher();
    export let payment_type_id;
    export let proof_payments = [];
    let deleteModal = false;
    let deleteIdx = 0;
    let formRef;
    let errors = null;
    let form  


    function OpenDeleteModal(item) {
        deleteModal = true;
    }

    function CloseDeleteModal() {
        deleteModal = false;
    }

    function OpenAlertMessage(event) {
        dispatch('message', event.detail);
    }

    function editProofPayment(item, index) {
        let details = proof_payments
        details[index].proof_payment_desc = item.proof_payment_desc;
        details[index].created = item.created ? item.created : false;
        details[index].edited = true;
        proof_payments = details;
    }

    function deleteProofPayment() {
        let details = proof_payments
        axios.delete(`/proof-payments/${details[deleteIdx].id}`).then((res) => {
            let detail = {
                detail: {
                    type: 'delete',
                    message: res.data.message
                }
            };
            OpenAlertMessage(detail);
            getProofPayments(payment_type_id);
            CloseDeleteModal();
        }).catch((err) => {
            errors = err.response.data.details ? err.response.data.details : null;
            let detail = {
                detail: {
                    type: 'delete',
                    message: err.response.data.message
                }
            };
            OpenAlertMessage(detail);
        });
    }

    function getProofPayments(id) {
        axios.get(`/proof-payments/${id}`).then((response) => {
            
            response.data.data.length > 0 && (
                proof_payments = response.data.data
            )
        }).catch((err) => {
            let detail = {
                detail: {
                    type: 'delete',
                    message: err.response.data.message
                }
            };
            OpenAlertMessage(detail);
        });
    }

    onMount(() => {
        getProofPayments(payment_type_id);
    });

    function addProofPayment(item) {
        let details = proof_payments
        details.push(item);
        proof_payments = details;
    }

    function removeProofPayment(item) {
        let newItem = proof_payments
        let itemIdx = newItem.findIndex(x => x.id === item.id);
        if(item.created == true){
            deleteIdx = itemIdx;
            OpenDeleteModal();
        }else{
            newItem.splice(itemIdx, 1);
        }
        proof_payments = newItem;
    }


    export function submitForm(){
        if(proof_payments.filter(x => x.created == true && x.edited == true).length > 0){
            handleEdit();
        }
        if(proof_payments.filter(x => x.created == false).length > 0){
            handleSubmit()
        }
    }

    function handleSubmit() {
        if(formRef){
            // formRef.requestSubmit();
            console.log('se supone que hace algo')
        }
        dispatch('submitted');
        axios.post(`/proof-payments/${payment_type_id}/multiple`, {
            payments: proof_payments.filter(x => x.created == false && x.edited == true).map(x => ({proof_payment_desc: x.proof_payment_desc, payment_type_id: payment_type_id}))
        }).then((res) => {
            let detail = {
                detail: {
                    type: 'success',
                    message: res.data.message
                }
            };
            OpenAlertMessage(detail);
            close();
        }).catch((err) => {
            errors = err.response.data.details ? err.response.data.details : null;
            let detail = {
                detail: {
                    type: 'delete',
                    message: err.response.data.message
                }
            };
            OpenAlertMessage(detail);
        });
    }
    function handleEdit() {
        axios.put(`/proof-payments/${payment_type_id}/multiple`, {
            payments: proof_payments.filter(x => x.created == true && x.edited == true).map(x => ({
                id: x.id,
                proof_payment_desc: x.proof_payment_desc, 
                payment_type_id: payment_type_id
            }))
        }).then((res) => {
            let detail = {
                detail: {
                    type: 'success',
                    message: res.data.message
                }
            };
            OpenAlertMessage(detail);
            close();
        }).catch((err) => {
            errors = err.response.data.details ? err.response.data.details : null;
            let detail = {
                detail: {
                    type: 'delete',
                    message: err.response.data.message
                }
            };
            OpenAlertMessage(detail);
        });
    }
</script>

{#if deleteModal == true}
    <dialog class="modal modal-open">
        <DeleteModal on:close={CloseDeleteModal} on:confirm={deleteProofPayment} />
    </dialog>
{/if}

<form on:submit|preventDefault={submitForm} bind:this={formRef}>
    <div class="grid grid-cols-12 gap-4">    
        {#each proof_payments as item, i (item.id)}    
            <div class="col-span-3">
                <Textfield
                    label="Comprobante"
                    required={true}
                    type="text"
                    bind:value={item.proof_payment_desc}
                    onChange={(event) => editProofPayment(item, i)}  
                />

                {#if i == proof_payments.length - 1}
                    <div class="col-span-3">
                        <button class="btn btn-primary" type="button" on:click={() => addProofPayment({
                            id: Math.random(),
                            created: false,
                            edited: true,
                            proof_payment_desc: ''
                        })}>
                            Agregar
                        </button>
                    </div>
                    {/if}
                    <div class="col-span-3">
                        <button class="btn btn-secondary" type="button" on:click={() => removeProofPayment(item)}>
                            Eliminar
                        </button>
                    </div>
            </div>
        {/each}
</form>