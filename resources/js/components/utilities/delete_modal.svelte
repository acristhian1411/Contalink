<script>
    import { onMount } from "svelte";
    import { createEventDispatcher } from "svelte";
    const dispatch = createEventDispatcher();
    let showAlert = false;
    export let message;
    function confirmDelete() {
        dispatch("confirm");
    }
    function close() {
        dispatch("close");
    }

    onMount(() => {
        // //obtiene el elemento del DOM con el id "my_modal_5"
        // var myModal = document.getElementById('my_modal_5');
        // //obtiene el elemento del DOM con el id "close_modal_5"
        // myModal.showModal();
    });
</script>

<!-- <dialog id="my_modal_5" class="modal modal-bottom sm:modal-middle"> -->
<div class="modal-box">
    <h3 class="text-lg font-bold">🗑</h3>
    <p class="py-4">
        {message
            ? message
            : "¿Estás seguro de que deseas eliminar este registro?"}
    </p>
    <div class="modal-action">
        <form method="dialog">
            <!-- if there is a button in form, it will close the modal -->
            <button on:click={confirmDelete} class="btn btn-error"
                >Eliminar</button
            >
            <button class="btn btn-ghost" on:click={close}>Cancelar</button>
        </form>
    </div>
</div>
<!-- </dialog> -->
