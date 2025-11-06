<script>
    export let data = [];

    import Layout from "../../components/Commons/Layout.svelte";
    import { Inertia } from "@inertiajs/inertia";

    function viewUser(id) {
        Inertia.visit(`/users/${id}`);
    }

    function editUser(id) {
        Inertia.visit(`/users/${id}/edit`);
    }

    function deleteUser(id) {
        if (confirm("¿Está seguro de que desea eliminar este usuario?")) {
            Inertia.delete(`/users/${id}`);
        }
    }
</script>

<Layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
            </div>

            <div class="p-6">
                <div class="mb-4">
                    <button
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        on:click={() => Inertia.visit("/users/create")}
                    >
                        Nuevo Usuario
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >ID</th
                                >
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >Nombre</th
                                >
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >Email</th
                                >
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >Fecha de Creación</th
                                >
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >Acciones</th
                                >
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            {#each data as user}
                                <tr>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >{user.id}</td
                                    >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >{user.name}</td
                                    >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >{user.email}</td
                                    >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {new Date(
                                            user.created_at,
                                        ).toLocaleDateString()}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                    >
                                        <button
                                            class="text-blue-600 hover:text-blue-900 mr-3"
                                            on:click={() => viewUser(user.id)}
                                        >
                                            Ver
                                        </button>
                                        <button
                                            class="text-green-600 hover:text-green-900 mr-3"
                                            on:click={() => editUser(user.id)}
                                        >
                                            Editar
                                        </button>
                                        <button
                                            class="text-red-600 hover:text-red-900"
                                            on:click={() => deleteUser(user.id)}
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>

                    {#if data.length === 0}
                        <div class="text-center py-8">
                            <p class="text-gray-500">
                                No hay usuarios registrados.
                            </p>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</Layout>
