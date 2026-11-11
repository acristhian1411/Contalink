<script>
    import { router } from '@inertiajs/svelte';

    export let metrics = null;
    export let period = 'month';

    const periods = [
        { value: 'today', label: 'Hoy' },
        { value: 'week', label: 'Esta semana' },
        { value: 'month', label: 'Este mes' },
    ];

    function formatAmount(value) {
        return new Intl.NumberFormat('es-PY', { maximumFractionDigits: 0 }).format(Number(value) || 0);
    }

    function formatQuantity(value) {
        return new Intl.NumberFormat('es-PY', { maximumFractionDigits: 3 }).format(Number(value) || 0);
    }

    function changePeriod(value) {
        router.get('/dashboard', { period: value }, { preserveScroll: true, preserveState: true });
    }
</script>

{#if metrics}
    <section class="space-y-6" aria-label="Resumen del negocio">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">Resumen del negocio</h2>
            <label class="flex items-center gap-2 text-sm">
                <span>Período</span>
                <select
                    class="select select-bordered select-sm"
                    value={period}
                    aria-label="Período del resumen"
                    on:change={(event) => changePeriod(event.currentTarget.value)}
                >
                    {#each periods as option}
                        <option value={option.value}>{option.label}</option>
                    {/each}
                </select>
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {#if metrics.sales}
                <article class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title text-base">Ventas</h3>
                        <p class="text-2xl font-bold">Gs. {formatAmount(metrics.sales.amount)}</p>
                        <p class="text-sm opacity-70">{metrics.sales.count} operaciones en el período</p>
                    </div>
                </article>
            {/if}

            {#if metrics.purchases}
                <article class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title text-base">Compras</h3>
                        <p class="text-2xl font-bold">Gs. {formatAmount(metrics.purchases.amount)}</p>
                        <p class="text-sm opacity-70">{metrics.purchases.count} operaciones en el período</p>
                    </div>
                </article>
            {/if}

            {#if metrics.openTills}
                <article class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title text-base">Cajas abiertas</h3>
                        <p class="text-2xl font-bold">Gs. {formatAmount(metrics.openTills.amount)}</p>
                        {#if metrics.openTills.items.length}
                            <ul class="mt-1 space-y-1 text-sm">
                                {#each metrics.openTills.items as till (till.id)}
                                    <li class="flex justify-between gap-3">
                                        <span>{till.name}</span>
                                        <span class="font-medium">Gs. {formatAmount(till.amount)}</span>
                                    </li>
                                {/each}
                            </ul>
                        {:else}
                            <p class="text-sm opacity-70">No hay cajas abiertas.</p>
                        {/if}
                    </div>
                </article>
            {/if}
        </div>

        {#if metrics.sales}
            <article class="card bg-base-100 shadow">
                <div class="card-body">
                    <h3 class="card-title">Productos más vendidos</h3>
                    {#if metrics.sales.topProducts.length}
                        <ol class="divide-y divide-base-300">
                            {#each metrics.sales.topProducts as product, index (product.id)}
                                <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                                    <span><span class="mr-2 opacity-60">{index + 1}.</span>{product.product_name}</span>
                                    <span class="text-sm">
                                        {formatQuantity(product.quantity)} unidades · Gs. {formatAmount(product.amount)}
                                    </span>
                                </li>
                            {/each}
                        </ol>
                    {:else}
                        <p class="text-sm opacity-70">No hay ventas en este período.</p>
                    {/if}
                </div>
            </article>
        {/if}
    </section>
{/if}
