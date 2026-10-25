<script>
    import { router } from '@inertiajs/svelte';
    import { Link } from '@inertiajs/svelte';
    export let title = '';
    // export let icon;
    export let items ;
    // Función para la navegación
    function goTo(path) {
      // Aquí puedes implementar la lógica de navegación, como router o redirección.
		router.visit(path);
    }
</script>

<details class="group">
    <summary class="cursor-pointer p-2 text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 rounded-lg flex items-center">
        <!-- {#if icon} -->
            <slot/>
        <!-- {/if} -->
        <span class="ml-3">{title}</span>
    </summary>
    <ul class="pl-5 space-y-2">
        {#each items as item (item.label)}
            {#if item.permission}
            <li>
                <!-- svelte-ignore a11y-click-events-have-key-events -->
                <Link
                    href={item.route}
                    class="group flex items-center cursor-pointer rounded-lg p-2 text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                >
                    {#if item.icon}
                        <svelte:component this={item.icon}/>
                    {/if}
                    <span class="ml-3 flex-1 whitespace-nowrap">{item.label}</span>
                </Link>
            </li>
            {/if}
        {/each}
    </ul>
</details>