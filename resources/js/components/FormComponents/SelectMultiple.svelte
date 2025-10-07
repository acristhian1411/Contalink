<script>
    import { onMount, onDestroy } from "svelte";
    import { createEventDispatcher } from "svelte";
    export let options = []; // Lista de opciones
    export let placeholder = "Seleccionar...";
    // export let selected; // Valores seleccionados

    let open = false;
    let container;
    let internalSelected = [];

    const dispatch = createEventDispatcher();

    function updateSelected() {
        console.log("Selected updated:", internalSelected);
        dispatch("change", internalSelected);
    }

    function toggleOption(option) {
        const exists = internalSelected.find((o) => o.value === option.value);
        if (exists) {
            internalSelected = internalSelected.filter(
                (o) => o.value !== option.value,
            );
        } else {
            internalSelected = [...internalSelected, option];
        }
        updateSelected(internalSelected);
    }
    function toggleDropdown() {
        open = !open;
    }

    function handleClickOutside(e) {
        if (container && !container.contains(e.target)) {
            open = false;
        }
    }

    const isSelected = (option) =>
        internalSelected.some((o) => o.value === option.value);

    onMount(() => {
        // Inicializar selected si es necesario
        internalSelected = Array.isArray(internalSelected)
            ? [...internalSelected]
            : [];
        document.addEventListener("click", handleClickOutside);
    });
    onDestroy(() => {
        document.removeEventListener("click", handleClickOutside);
    });
</script>

<div class="relative w-full" bind:this={container}>
    <!-- Selector principal -->
    <button type="button" class="select-multiple-btn" on:click={toggleDropdown}>
        <span class="truncate text-left block">
            {#if internalSelected.length > 0}
                {internalSelected.map((val) => val.label).join(", ")}
            {:else}
                <span class="text-gray-400">{placeholder}</span>
            {/if}
        </span>
        <svg
            class="w-4 h-4 text-gray-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
            />
        </svg>
    </button>
    {#if open}
        <!-- Lista de opciones -->
        <div
            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-md max-h-48 overflow-auto"
        >
            {#each options as option}
                <label
                    class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer text-gray-700 hover:bg-gray-100"
                >
                    <input
                        type="checkbox"
                        checked={isSelected(option)}
                        on:change={() => toggleOption(option)}
                        class="rounded text-blue-600 focus:ring-blue-500"
                    />
                    <span>{option.label}</span>
                </label>
            {/each}
        </div>
    {/if}
</div>

<style>
    /* Evita que la lista se cierre al hacer clic (si se usa fuera de un form) */
    /* button: focus + div; */
    /* div:hover {
        display: block;
    } */
    .select-multiple-btn {
        @apply w-full bg-gray-700 border border-gray-200 rounded-lg shadow-sm pl-3 pr-10 py-2 text-left cursor-pointer;
        placeholder: text-transparent;
        focus: outline-none;
        focus: ring-1;
        focus: ring-blue-500;
        focus: border-blue-500;
        sm: text-sm;
    }
</style>
