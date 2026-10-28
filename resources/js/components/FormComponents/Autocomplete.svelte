<script>
    import { onMount } from "svelte";
    import { createEventDispatcher } from 'svelte';
    export let errors;
    export let item_selected;
    export let searchTerm;
    export let showDropdown;
    export let loading;
    export let filteredItem = [];
    export let searchFromApi;
    export let items
    export let label
    let dispatch = createEventDispatcher();
    
    $: searchTerm, filterItems();

    $: searchFromApi

    function CustonSearch(event) {
        dispatch('customSearch', event.detail);
    }
    
    function handleInput(event) {
        searchTerm = event.target.value;
        filteredItem = items;
        showDropdown = true; // Muestra el dropdown mientras se busca
    }
    function selectItem(item) {
        item_selected = item;
        searchTerm = item.label;
        showDropdown = false; // Oculta el dropdown después de la selección
    }
    async function filterItems() {
		loading = true; // Inicia el loader 
        if(searchFromApi != null && searchFromApi === true){
            await new Promise((resolve) => setTimeout(resolve, 1000));
            CustonSearch({detail: searchTerm});
        }else{
            await new Promise((resolve) => setTimeout(resolve, 1000)); 
            filteredItem = searchTerm != '' ? items.filter(item =>
                item.label.toLowerCase().includes(searchTerm.toLowerCase())
            ): items;
            // filteredItem = items.filter(item =>
            //     searchTerm != '' ? item.label.toLowerCase().includes(searchTerm.toLowerCase()) : true
            // );
        }
		loading = false; // Desactiva el loader
    }

    const clearInput = () => {
        searchTerm = "";
        filterItems();
    }

    onMount(() => {
        filterItems();
    })

</script>
<!--
@component
Here's some documentation for this component.
It will show up on hover.

- You can use markdown here.
- You can also use code blocks here.
- Usage:
  ```js
  <main name="Autocomplete">
    ```
  -->
<main>
    <div class="max-w-sm space-y-3 mb-2">
        <div class="relative">
            <input
                type="text"
                id="autocomplete" class="peer p-4 block w-full border-gray-200 rounded-lg text-sm placeholder:text-transparent focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 
                disabled:pointer-events-none
                focus:ring-2
                focus:pt-6
                focus:pb-2
                [&:not(:placeholder-shown)]:pt-6
                [&:not(:placeholder-shown)]:pb-2
                autofill:pt-6
                autofill:pb-2
                shadow-sm"
                placeholder="Buscar..."
                bind:value={searchTerm}
                on:input={handleInput}
                on:focus={() => showDropdown = true}
                on:blur={() => setTimeout(() => showDropdown = false, 200)} 
                autocomplete="off"
            />
            <!-- Dropdown de países filtrados -->
            {#if showDropdown == true && (filteredItem ?? []).length > 0}
                <ul class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-md shadow-lg w-full">
                    {#if loading == true}
                        <!-- Loader mientras se buscan los países -->
                        <div class="absolute right-2">
                            <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </div>
                    {:else if filteredItem.length > 0}
                        {#each filteredItem as item}
                        <!-- svelte-ignore a11y-click-events-have-key-events -->
                        <li
                            class="px-4 py-2 cursor-pointer bg-gray-500 hover:bg-blue-100"
                            on:click={() => selectItem(item)}
                        >
                            {item.label}
                        </li>
                        {/each}
                    {:else}
                <!-- Mensaje si no hay resultados -->
                        <li class="p-2 text-center text-gray-500">No se encontro registros</li>
                    {/if}
                </ul>
            {/if}

            <label for="autocomplete" class="absolute top-0 start-0 p-4 h-full text-sm truncate pointer-events-none transition ease-in-out duration-100 border border-transparent  origin-[0_0] peer-disabled:opacity-50 peer-disabled:pointer-events-none
            peer-focus:scale-90
            peer-focus:translate-x-0.5
            peer-focus:-translate-y-1.5
            peer-focus:text-gray-500
            peer-[:not(:placeholder-shown)]:scale-90
            peer-[:not(:placeholder-shown)]:translate-x-0.5
            peer-[:not(:placeholder-shown)]:-translate-y-1.5
            peer-[:not(:placeholder-shown)]:text-gray-500">{label}</label>

            {#if errors != null && errors.message}
                <span class="text-red-500 text-sm">{errors.message}</span>
            {/if}
        </div>

    </div>
</main>
<style>
	@keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>