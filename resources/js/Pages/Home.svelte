<script>
    export let name = 'Usuario';
    export let metrics = null;
    export let period = 'month';
    export let links = null;
    import Dashboard from "../components/Commons/Dashboard.svelte";
</script>

<main class="mx-auto max-w-7xl p-6">
    <h1 class="mb-4 text-2xl font-semibold">Bienvenido {name}</h1>
    <Dashboard {metrics} {period} {links} />
</main>
