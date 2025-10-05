<script>
    import { onMount, onDestroy } from "svelte";
    export let options = []; // Lista de opciones
    export let placeholder = "Seleccionar...";
    export let selected; // Valores seleccionados

    let open = false;
    let container;

    function toggleOption(option) {
        let exists = selected.find((o) => o.value === option.value);
        if (exists) {
            selected = selected.filter((o) => o.value !== option.value);
        } else {
            selected = [...selected, option];
        }
    }
    function toggleDropdown() {
        open = !open;
    }

    function handleClickOutside(e) {
        if (container && !container.contains(e.target)) {
            open = false;
        }
    }

    const isSelected = (option) => selected.includes(option);

    onMount(() => {
        // Inicializar selected si es necesario
        if (!Array.isArray(selected)) {
            selected = [];
        }
        document.addEventListener("click", handleClickOutside);
    });
    onDestroy(() => {
        document.removeEventListener("click", handleClickOutside);
    });
</script>

<div class="relative w-full" bind:this={container}>
    <!-- Selector principal -->
    <button
        type="button"
        class="peer p-4 block w-full border border-gray-200 rounded-lg text-sm placeholder:text-transparent
            focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none
            bg-white text-gray-900 focus:pt-6 focus:pb-2
            [&:not(:placeholder-shown)]:pt-3
            [&:not(:placeholder-shown)]:pb-2"
        on:click={toggleDropdown}
    >
        <span class="truncate text-left block">
            {#if selected.length > 0}
                {selected.map((val) => val.label).join(", ")}
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
    div:hover {
        display: block;
    }
</style>
