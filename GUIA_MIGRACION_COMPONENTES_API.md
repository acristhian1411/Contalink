# Guía de Migración de Componentes a Nuevas Rutas API

## Introducción

Esta guía te ayudará a migrar los componentes Svelte existentes para usar las nuevas rutas API protegidas implementadas en el sistema. El objetivo es reemplazar las llamadas AJAX directas con las nuevas rutas que incluyen autenticación, autorización y validación de seguridad.

## Estructura de las Nuevas Rutas API

### Rutas de Búsqueda (`/api/search/`)
- `GET /api/search/clients` - Búsqueda de clientes
- `GET /api/search/products` - Búsqueda de productos  
- `GET /api/search/persons-by-type/{type}` - Búsqueda de personas por tipo

### Rutas de Tiempo Real (`/api/real-time/`)
- `GET /api/real-time/tills/{id}/amount` - Monto de caja
- `GET /api/real-time/tills/by-person/{id}` - Cajas por usuario
- `GET /api/real-time/cities/by-state/{id}` - Ciudades por estado
- `GET /api/real-time/cities/by-country/{id}` - Ciudades por país
- `GET /api/real-time/states/by-country/{id}` - Estados por país

### Rutas de Recursos Principales
- `GET /api/sales/` - Listado de ventas
- `GET /api/purchases/` - Listado de compras
- `GET /api/tills/` - Listado de cajas
- `GET /api/persons/` - Listado de personas
- `GET /api/products/` - Listado de productos

## Patrón de Migración

### 1. Configuración de Sesión y Autenticación

**IMPORTANTE:** El proyecto ya tiene configurado axios para manejar automáticamente la sesión y CSRF tokens en `resources/js/bootstrap.js`. Esta configuración incluye:

- `withCredentials: true` - Para compartir cookies de sesión
- Manejo automático de CSRF tokens
- Interceptores para renovar tokens expirados
- Redirección automática en caso de errores de autenticación

**No necesitas configurar manualmente la sesión**, pero es importante entender cómo funciona:

```javascript
// ✅ La configuración global ya está aplicada en bootstrap.js
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ✅ CSRF token se maneja automáticamente
// ✅ Interceptores manejan renovación de tokens
```

### 2. Headers Adicionales para APIs

Para las llamadas API específicas, solo necesitas agregar headers adicionales si es necesario:

```javascript
const apiHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
    // 'X-Requested-With' y CSRF ya están configurados globalmente
};
```

### 3. Manejo de Errores Estandarizado

Implementa una función centralizada para manejar errores de API:

```javascript
function handleApiError(error) {
    console.error('API Error:', error);
    
    // Los errores 419 (CSRF) se manejan automáticamente por los interceptores
    // Los errores 401 también pueden ser manejados automáticamente
    
    if (error.response?.status === 401) {
        // El interceptor ya maneja esto, pero puedes agregar lógica adicional
        console.warn('Usuario no autenticado');
        return;
    }
    
    if (error.response?.status === 403) {
        openAlerts('No tienes permisos para realizar esta acción', 'error');
        return;
    }
    
    if (error.response?.status === 419) {
        // CSRF token mismatch - el interceptor ya lo maneja
        console.warn('CSRF token renovado automáticamente');
        return;
    }
    
    if (error.response?.data?.message) {
        openAlerts(error.response.data.message, 'error');
    } else {
        openAlerts('Error de conexión', 'error');
    }
}
```

### 4. Funciones de Búsqueda

#### Búsqueda de Clientes
```javascript
async function searchClients(searchTerm) {
    if (searchTerm.length < 3) return [];
    
    try {
        // axios ya está configurado globalmente con sesión y CSRF
        const response = await axios.get('/api/search/clients', {
            params: { q: searchTerm }
            // No necesitas headers adicionales - ya están configurados globalmente
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

#### Búsqueda de Productos
```javascript
async function searchProducts(searchTerm) {
    if (searchTerm.length < 3) return [];
    
    try {
        const response = await axios.get('/api/search/products', {
            params: { q: searchTerm }
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

#### Búsqueda de Personas por Tipo
```javascript
async function searchPersonsByType(type, searchTerm = '') {
    try {
        const response = await axios.get(`/api/search/persons-by-type/${type}`, {
            params: { q: searchTerm }
        });
        ret;
    } catch (error) {
        handleApiErro);
        return [];
    }
}
```

## Componentes Específicos a Migrar

### 1. Formulario de Ventas (`resources/js/Pages/Sales/form.svelte`)

#### Cambios Requeridos:

**Función de búsqueda de clientes (ya implementada correctamente):**
```javascript
// ✅ Ya está usando la nueva ruta
async function searchClients(searchTerm) {
    if (searchTerm.length < 3) return [];
    
    try {
        const response = await axios.get('/api/search/clients', {
            params: { q: searchTerm },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

**Agregar búsqueda de productos para el modal DetailsTable:**
```javascript
// Agregar esta función al componente
async function searchProducts(searchTerm) {
    if (searchTerm.length < 3) return [];
    
    try {
        const response = await axios.get('/api/search/products', {
            params: { q: searchTerm },
            headers: apiHeaders
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

### 2. Componente DetailsTable (`resources/js/Pages/Sales/DetailsTable.svelte`)

**Migrar búsqueda de productos:**
```javascript
// Reemplazar llamadas directas con:
async function loadProducts(searchTerm = '') {
    try {
        const response = await axios.get('/api/search/products', {
            params: { q: searchTerm },
            headers: apiHeaders
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

### 3. Formularios de Personas/Clientes

**Cargar ciudades por estado:**
```javascript
async function loadCitiesByState(stateId) {
    try {
        const response = await axios.get(`/api/real-time/cities/by-state/${stateId}`, {
            headers: apiHeaders
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

**Cargar estados por país:**
```javascript
async function loadStatesByCountry(countryId) {
    try {
        const response = await axios.get(`/api/real-time/states/by-country/${countryId}`, {
            headers: apiHeaders
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

### 4. Componentes de Cajas/Tills

**Obtener monto de caja:**
```javascript
async function getTillAmount(tillId) {
    try {
        const response = await axios.get(`/api/real-time/tills/${tillId}/amount`, {
            headers: apiHeaders
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return null;
    }
}
```

**Obtener cajas por usuario:**
```javascript
async function getTillsByUser(userId) {
    try {
        const response = await axios.get(`/api/real-time/tills/by-person/${userId}`, {
            headers: apiHeaders
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

## Componentes de Listado

### 1. Listas con Paginación

**Patrón para listas principales:**
```javascript
async function loadData(page = 1, filters = {}) {
    try {
        const params = {
            page,
            ...filters
        };
        
        const response = await axios.get('/api/sales/', {
            params,
            headers: apiHeaders
        });
        
        return {
            data: response.data.data,
            pagination: response.data.meta || response.data.pagination
        };
    } catch (error) {
        handleApiError(error);
        return { data: [], pagination: null };
    }
}
```

### 2. Búsquedas en Listas

**Para búsquedas específicas (ej: ventas por número):**
```javascript
async function searchSalesByNumber(searchTerm) {
    try {
        const response = await axios.get(`/api/sales/search/${searchTerm}`, {
            headers: apiHeaders
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

## Archivos a Modificar

### Componentes Principales:
1. `resources/js/Pages/Sales/form.svelte` ✅ (parcialmente migrado)
2. `resources/js/Pages/Sales/DetailsTable.svelte`
3. `resources/js/Pages/Sales/index.svelte`
4. `resources/js/Pages/Purchases/form.svelte`
5. `resources/js/Pages/Purchases/DetailsTable.svelte`
6. `resources/js/Pages/Purchases/index.svelte`
7. `resources/js/Pages/Products/index.svelte`
8. `resources/js/Pages/Clients/form.svelte`
9. `resources/js/Pages/Providers/form.svelte`
10. `resources/js/Pages/Tills/form.svelte`

### Componentes de Búsqueda:
1. `resources/js/components/FormComponents/SearchPersons.svelte`
2. `resources/js/components/FormComponents/Autocomplete.svelte`

## Pasos de Implementación

### Paso 1: Crear Utilidades Comunes
Crea un archivo `resources/js/utils/apiHelpers.js`:

```javascript
import axios from 'axios';

// No necesitas configurar headers manualmente - ya están en bootstrap.js
// Solo define headers adicionales si son específicos para ciertas llamadas
export const additionalHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
};

export function handleApiError(error, alertCallback) {
    console.error('API Error:', error);
    
    // Los errores 401 y 419 se manejan automáticamente por los interceptores
    if (error.response?.status === 401) {
        console.warn('Usuario no autenticado - interceptor maneja redirección');
        return;
    }
    
    if (error.response?.status === 419) {
        console.info('CSRF token renovado automáticamente');
        return;
    }
    
    if (error.response?.status === 403) {
        alertCallback?.('No tienes permisos para realizar esta acción', 'error');
        return;
    }
    
    if (error.response?.data?.message) {
        alertCallback?.(error.response.data.message, 'error');
    } else {
        alertCallback?.('Error de conexión', 'error');
    }
}

export const apiService = {
    async searchClients(searchTerm) {
        if (searchTerm.length < 3) return [];
        
        // axios ya está configurado con sesión y CSRF automáticamente
        const response = await axios.get('/api/search/clients', {
            params: { q: searchTerm }
        });
        return response.data.data;
    },

    async searchProducts(searchTerm) {
        if (searchTerm.length < 3) return [];
        
        const response = await axios.get('/api/search/products', {
            params: { q: searchTerm }
        });
        return response.data.data;
    },

    async getCitiesByState(stateId) {
        const response = await axios.get(`/api/real-time/cities/by-state/${stateId}`);
        return response.data.data;
    },

    async getStatesByCountry(countryId) {
        const response = await axios.get(`/api/real-time/states/by-country/${countryId}`);
        return response.data.data;
    },

    async getTillAmount(tillId) {
        const response = await axios.get(`/api/real-time/tills/${tillId}/amount`);
        return response.data.data;
    }
};
```

### Paso 2: Migrar Componente por Componente

1. **Importar las utilidades:**
```javascript
import { apiService, handleApiError } from '@/utils/apiHelpers.js';
```

2. **Reemplazar llamadas axios directas:**
```javascript
// Antes:
const response = await axios.get('/some-old-route');

// Después:
try {
    const data = await apiService.searchClients(searchTerm);
    // usar data
} catch (error) {
    handleApiError(error, openAlerts);
}
```

3. **Actualizar funciones de búsqueda:**
```javascript
// Reemplazar funciones existentes con las del apiService
async function searchClients(searchTerm) {
    try {
        return await apiService.searchClients(searchTerm);
    } catch (error) {
        handleApiError(error, openAlerts);
        return [];
    }
}
```

### Paso 3: Validar y Probar

1. **Verificar autenticación:** Asegúrate de que el usuario esté autenticado
2. **Verificar permisos:** Confirma que el usuario tenga los permisos necesarios
3. **Probar manejo de errores:** Simula errores 401, 403, y de red
4. **Validar datos:** Confirma que los datos se reciben en el formato esperado

## Consideraciones de Seguridad

1. **Siempre incluir headers de seguridad**
2. **Manejar errores de autenticación y autorización**
3. **No exponer información sensible en logs del cliente**
4. **Validar datos recibidos antes de usarlos**
5. **Implementar timeouts para evitar requests colgados**

## Notas Importantes

- Las nuevas rutas requieren autenticación con Sanctum
- Todas las rutas tienen middleware de permisos específicos
- Los parámetros de búsqueda deben enviarse como query parameters (`?q=término`)
- Las respuestas siguen el formato estándar: `{ data: [...], meta: {...} }`
- Los errores de validación se devuelven en formato Laravel estándar

## Ejemplo Completo de Migración

Ver el archivo `resources/js/Pages/Sales/form.svelte` como referencia de una migración parcialmente completada. La función `searchClients` ya está migrada correctamente y puede servir como modelo para otras implementaciones.

## Gestión de Sesión y Autenticación

### Configuración Automática de Sesión

El proyecto ya tiene configurado axios para manejar automáticamente la sesión web en `resources/js/bootstrap.js`. Esta configuración incluye:

#### 1. Compartir Cookies de Sesión
```javascript
// ✅ Ya configurado globalmente
window.axios.defaults.withCredentials = true;
```

#### 2. Manejo Automático de CSRF
```javascript
// ✅ Token CSRF se obtiene automáticamente del meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
```

#### 3. Interceptores para Renovación Automática
```javascript
// ✅ Interceptor de request - obtiene cookie CSRF si es necesario
window.axios.interceptors.request.use(async (config) => {
    if (['post', 'put', 'patch', 'delete'].includes(config.method.toLowerCase())) {
        if (!document.cookie.includes('XSRF-TOKEN')) {
            await axios.get('/sanctum/csrf-cookie');
        }
    }
    return config;
});

// ✅ Interceptor de response - maneja errores CSRF automáticamente
window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response?.status === 419) {
            await axios.get('/sanctum/csrf-cookie');
            return window.axios.request(error.config);
        }
        return Promise.reject(error);
    }
);
```

### Qué Significa Esto Para Tu Migración

#### ✅ Lo que NO necesitas hacer:
- Configurar `withCredentials` manualmente
- Manejar CSRF tokens manualmente
- Agregar headers de autenticación
- Configurar interceptores adicionales
- Manejar renovación de sesión

#### ✅ Lo que SÍ necesitas hacer:
- Usar `axios` directamente (ya está configurado globalmente)
- Manejar errores específicos de tu aplicación
- Validar permisos en el frontend si es necesario

### Ejemplos Actualizados

#### Llamada API Simple (Recomendado)
```javascript
async function searchClients(searchTerm) {
    if (searchTerm.length < 3) return [];
    
    try {
        // Sesión y CSRF se manejan automáticamente
        const response = await axios.get('/api/search/clients', {
            params: { q: searchTerm }
        });
        return response.data.data;
    } catch (error) {
        handleApiError(error);
        return [];
    }
}
```

#### Llamada API con Headers Adicionales (Si es necesario)
```javascript
async function uploadFile(formData) {
    try {
        const response = await axios.post('/api/files/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
                // 'X-Requested-With' y CSRF ya están configurados
            }
        });
        return response.data;
    } catch (error) {
        handleApiError(error);
        return null;
    }
}
```

### Verificación de Configuración

Para verificar que la sesión está funcionando correctamente:

#### 1. Verificar en DevTools
```javascript
// En la consola del navegador
console.log('withCredentials:', axios.defaults.withCredentials); // debe ser true
console.log('CSRF Token:', axios.defaults.headers.common['X-CSRF-TOKEN']); // debe tener valor
```

#### 2. Verificar Cookies
- Abre DevTools → Application → Cookies
- Debe existir la cookie de sesión de Laravel
- Debe existir `XSRF-TOKEN` después de la primera llamada API

#### 3. Verificar Headers en Network
- Abre DevTools → Network
- Haz una llamada API
- Verifica que incluya:
  - `X-Requested-With: XMLHttpRequest`
  - `X-CSRF-TOKEN: [token]`
  - Cookie de sesión

### Solución de Problemas Comunes

#### Error 401 (No Autenticado)
```javascript
// El interceptor maneja esto automáticamente, pero puedes agregar:
if (error.response?.status === 401) {
    console.warn('Sesión expirada, redirigiendo...');
    window.location.href = '/login';
}
```

#### Error 419 (CSRF Token Mismatch)
```javascript
// Se maneja automáticamente por el interceptor
// No necesitas código adicional, pero puedes loggear:
if (error.response?.status === 419) {
    console.info('CSRF token renovado automáticamente');
}
```

#### Error 403 (Sin Permisos)
```javascript
// Este sí necesitas manejarlo manualmente:
if (error.response?.status === 403) {
    openAlerts('No tienes permisos para realizar esta acción', 'error');
}
```

### Migración de Código Existente

#### Antes (Configuración Manual)
```javascript
// ❌ No necesario - ya está configurado globalmente
const response = await axios.get('/api/search/clients', {
    params: { q: searchTerm },
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    withCredentials: true
});
```

#### Después (Usando Configuración Global)
```javascript
// ✅ Simplificado - usa configuración global
const response = await axios.get('/api/search/clients', {
    params: { q: searchTerm }
});
```

### Consideraciones Importantes

1. **Todas las llamadas axios automáticamente incluyen la sesión**
2. **Los tokens CSRF se renuevan automáticamente**
3. **Los errores de autenticación se manejan automáticamente**
4. **Solo necesitas manejar errores específicos de tu aplicación**
5. **La configuración funciona tanto para SPA como para aplicaciones tradicionales**

Esta configuración asegura que todas las llamadas API mantengan la misma sesión que la aplicación web, proporcionando una experiencia de usuario fluida y segura.