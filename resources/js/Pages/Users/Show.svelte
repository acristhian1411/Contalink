<script>
    export let users;
    export let audits = [];
    
    import Layout from '../../components/Commons/Layout.svelte';
</script>

<Layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Detalles del Usuario</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-semibold mb-4">Información del Usuario</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ID</label>
                            <p class="text-sm text-gray-900">{users.id}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre</label>
                            <p class="text-sm text-gray-900">{users.name}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="text-sm text-gray-900">{users.email}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Persona ID</label>
                            <p class="text-sm text-gray-900">{users.person_id || 'No asignado'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha de Creación</label>
                            <p class="text-sm text-gray-900">{new Date(users.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                </div>
                
                {#if audits && audits.length > 0}
                <div>
                    <h2 class="text-lg font-semibold mb-4">Historial de Auditoría</h2>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        {#each audits as audit}
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-600">
                                {audit.event} - {new Date(audit.created_at).toLocaleString()}
                            </p>
                        </div>
                        {/each}
                    </div>
                </div>
                {/if}
            </div>
            
            <div class="mt-6 flex space-x-4">
                <a href="/users" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Volver a la Lista
                </a>
                <a href="/users/{users.id}/edit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Editar Usuario
                </a>
            </div>
        </div>
    </div>
    <table>
        <tbody class="bg-white divide-y divide-gray-200">
            {#each audits as audit}
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{audit.event}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{new Date(audit.created_at).toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{audit.user_id || 'Sistema'}</td>
                </tr>
            {/each}
        </tbody>
    </table>
    <div class="mt-6 flex space-x-3">
        <button 
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            on:click={() => window.history.back()}
        >
            Volver
        </button>
    </div>
</Layout>                           
            
