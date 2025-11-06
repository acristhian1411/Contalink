export async function fetchPermissionsForRole(roleId) {
    const res = await fetch(`/api/permissions/${roleId}/roles?per_page=100&wantsJson=true`);
    const data = await res.json();
  
    return {
      assigned: data.data,
    };
  }

  export async function fetchPermissionsNotContainRole(roleId) {
    const res = await fetch(`/api/permissions/not-in-role/${roleId}?per_page=100&wantsJson=true`);
    const data = await res.json();
  
    return {
      unassigned: data.data,
    };
  }
  
  export async function assignPermissionsToRole(roleId, permissions) {
    const res = await fetch(`/api/users/${roleId}/roles?wantsJson=true`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        permissions: permissions,
      }),
    });
  
    return await res.json();
  }
  
  export async function removePermissionsFromRole(roleId, permissions) {
    const res = await fetch(`/api/roles/${roleId}/permissions?wantsJson=true`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        permissions: permissions,
      }),
    });
  
    return await res.json();
  }
  