<script>
    import { onMount } from 'svelte';
    import { fetchPermissionsForRole, fetchPermissionsNotContainRole, assignPermissionsToRole, removePermissionsFromRole } from './api';
  
    export let roleName;
    export let roleId;
    let assignedPermissions = [];
    let unassignedPermissions = [];
    let selectedUnassigned = [];
    let selectedAssigned = [];
    let assigned;
    let unassigned;
  
    onMount(async () => {
      assigned  = (await fetchPermissionsForRole(roleId)).assigned || [];
      console.log('assigned',assigned);
      unassigned = (await fetchPermissionsNotContainRole(roleId)).unassigned || [];
      console.log('unassigned',unassigned);
      assignedPermissions = assigned;
      unassignedPermissions = unassigned;
    });
  
    const assignPermissions = async () => {
      if (selectedUnassigned.length > 0) {
        await assignPermissionsToRole(roleId, selectedUnassigned.map(p => p.name));
        assignedPermissions = [...assignedPermissions, ...selectedUnassigned];
        unassignedPermissions = unassignedPermissions.filter(p => !selectedUnassigned.includes(p));
        selectedUnassigned = [];
          assigned  = (await fetchPermissionsForRole(roleId)).assigned || [];
          unassigned  = (await fetchPermissionsNotContainRole(roleId)).unassigned || [];
      }
    };
  
    const removePermissions = async () => {
        if (selectedAssigned.length > 0) {
            await removePermissionsFromRole(roleId, selectedAssigned.map(p => p.name));
            unassignedPermissions = [...unassignedPermissions, ...selectedAssigned];
            assignedPermissions = assignedPermissions.filter(p => !selectedAssigned.includes(p));
            selectedAssigned = [];
            assigned = (await fetchPermissionsForRole(roleId)).assigned || [];
            unassigned = (await fetchPermissionsNotContainRole(roleId)).unassigned || [];
        
        }
    };
    const searchUnassigned = async (event) => {
      unassignedPermissions = unassigned.filter(p => p.name.toLowerCase().includes(event.target.value.toLowerCase()));
    }
    const searchAssigned = async (event) => {
      assignedPermissions = assigned.filter(p => p.name.toLowerCase().includes(event.target.value.toLowerCase()));
    }
</script>

  <div class="container mx-auto p-4">
    <h2 class="text-xl font-semibold mb-4">Permisos para el rol: {roleName}</h2>
    
    <div class="flex space-x-4">
      <!-- Permisos no asignados -->
      <div class="w-1/2">
        <h3 class="text-lg font-medium mb-2">Permisos no asignados</h3>
        <input type="text" class="border p-2 w-full mb-4" placeholder="Buscar"  on:input={searchUnassigned} />
  
        <div class="border rounded-md h-96 overflow-y-auto">
          {#each unassignedPermissions as permission}
            <div class="p-2 flex items-center space-x-2">
              <input type="checkbox" bind:group={selectedUnassigned} value={permission} />
              <span>{permission.name}</span>
            </div>
          {/each}
        </div>
      </div>
  
      <div class="flex flex-col justify-center items-center space-y-2">
        <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" on:click={assignPermissions}>
          &rarr;
        </button>
        <button class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600" on:click={removePermissions}>
          &larr;
        </button>
      </div>
  
      <!-- Permisos asignados -->
      <div class="w-1/2">
        <h3 class="text-lg font-medium mb-2">Permisos asignados</h3>
        <input type="text" class="border p-2 w-full mb-4" placeholder="Buscar " on:input={searchAssigned} />
  
        <div class="border rounded-md h-96 overflow-y-auto">
          {#each assignedPermissions as permission}
            <div class="p-2 flex items-center space-x-2">
              <input type="checkbox" bind:group={selectedAssigned} value={permission} />
              <span>{permission.name}</span>
            </div>
          {/each}
        </div>
      </div>
    </div>
  </div>
  
  <style>
    /* Estilos adicionales */
  </style>
  