# Guía: Migración de Llamadas API a Inertia.js en Laravel

## Problema Identificado

Actualmente el proyecto tiene:
- **Rutas Web**: Protegidas con middleware `auth` y `permission`
- **Rutas API**: Sin protección porque se usan desde el frontend Svelte/Inertia
- **Frontend**: Hace llamadas AJAX a endpoints API desde componentes Svelte

Esto crea una vulnerabilidad de seguridad donde las APIs están expuestas sin autenticación.

## Solución Recomendada

Migrar de llamadas API AJAX a **datos pre-cargados con Inertia.js**, manteniendo la seguridad de las rutas web.

## Estrategias de Migración

### Estrategia 1: Datos Pre-cargados en el Controlador (Recomendada)

#### Antes (Inseguro):
```javascript
// En form.svelte
function getPaymentTypes() {
    axios.get(`/api/paymenttypes`)
        .then((response) => {
            paymentTypes = response.data.data;
        });
}
```

#### Después (Seguro):
```php
// En el controlador web
Route::get('/create-sales', function () {
    return Inertia::render('Sales/form', [
        'paymentTypes' => PaymentTypes::all(),
        'clients' => Persons::where('p_type_id', 2)->get(),
        'tills' => Tills::where('person_id', auth()->user()->person_id)->get(),
        'user' => auth()->user()
    ]);
})->middleware('permission:sales.create');
```

```javascript
// En form.svelte
export let paymentTypes = [];
export let clients = [];
export let tills = [];
export let user;

// Ya no necesitas hacer llamadas AJAX
onMount(() => {
    // Los datos ya están disponibles como props
    paymentTypesProcessed = paymentTypes.map((x) => ({
        label: x.paymentTypeDesc,
        value: x.id,
        proof_payments: x.proof_payments,
    }));
});
```

### Estrategia 2: Endpoints API Protegidos con Sanctum

#### Configuración de Sanctum para SPA:

```php
// En config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000'
)),
```

```php
// En routes/api.php - Proteger rutas API
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('paymenttypes', PaymentTypesController::class);
    Route::apiResource('persons', PersonsController::class);
    Route::apiResource('tills', TillsController::class);
    // ... resto de rutas
});
```

```javascript
// En app.js - Configurar Axios con CSRF
import axios from 'axios';

// Configurar Axios para SPA
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Obtener CSRF token
await axios.get('/sanctum/csrf-cookie');
```

### Estrategia 3: Híbrida (Recomendada para tu caso)

Combinar datos pre-cargados para datos estáticos y APIs protegidas para datos dinámicos.

## Implementación Paso a Paso

### Paso 1: Identificar Tipos de Datos

**Datos Estáticos** (cargar con Inertia):
- Tipos de pago
- Tipos de persona
- Países, estados, ciudades
- Categorías
- Marcas

**Datos Dinámicos** (APIs protegidas):
- Búsquedas de clientes
- Operaciones CRUD
- Datos que cambian frecuentemente

### Paso 2: Modificar Controladores Web

```php
// app/Http/Controllers/Sales/SalesWebController.php
<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\PaymentTypes;
use App\Models\Persons;
use App\Models\Tills;

class SalesWebController extends Controller
{
    public function create()
    {
        return Inertia::render('Sales/form', [
            // Datos estáticos pre-cargados
            'paymentTypes' => PaymentTypes::with('proofPayments')->get(),
            'clients' => Persons::where('p_type_id', 2)
                ->select('id', 'person_name', 'person_lastname')
                ->get(),
            'tills' => Tills::where('person_id', auth()->user()->person_id)
                ->with('type')
                ->get(),
            'user' => auth()->user()->load('person'),
            
            // Configuración
            'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
        ]);
    }
    
    public function store(Request $request)
    {
        // Lógica de guardado
        $validated = $request->validate([
            'person_id' => 'required|exists:persons,id',
            'sale_date' => 'required|date',
            'sale_details' => 'required|array',
            // ... más validaciones
        ]);
        
        // Crear venta
        $sale = Sales::create($validated);
        
        return redirect()->route('sales.index')
            ->with('success', 'Venta creada exitosamente');
    }
}
```

### Paso 3: Actualizar Rutas Web

```php
// routes/web.php
Route::group(['middleware' => ['auth']], function() {
    
    // Rutas de ventas
    Route::get('/sales', [SalesController::class, 'index'])
        ->middleware('permission:sales.index');
    
    Route::get('/create-sales', [SalesWebController::class, 'create'])
        ->middleware('permission:sales.create');
    
    Route::post('/sales', [SalesWebController::class, 'store'])
        ->middleware('permission:sales.create');
    
    // APIs protegidas para búsquedas dinámicas
    Route::get('/api/search/clients', [PersonsController::class, 'search'])
        ->middleware('permission:clients.index');
    
    Route::get('/api/search/products', [ProductsController::class, 'search'])
        ->middleware('permission:products.index');
});
```

### Paso 4: Actualizar Componente Svelte

```javascript
// resources/js/Pages/Sales/form.svelte
<script>
    import { Inertia } from '@inertiajs/inertia';
    import { useForm } from '@inertiajs/inertia-svelte';
    
    // Props pre-cargadas desde el servidor
    export let paymentTypes = [];
    export let clients = [];
    export let tills = [];
    export let user;
    export let permissions = [];
    
    // Usar Inertia Form Helper
    let form = useForm({
        person_id: '',
        sale_date: new Date().toISOString().slice(0, 10),
        sale_number: '',
        sale_details: [],
        proof_payments: []
    });
    
    // Datos procesados
    let paymentTypesProcessed = [];
    let tillsProcessed = [];
    
    onMount(() => {
        // Procesar datos pre-cargados
        paymentTypesProcessed = paymentTypes.map(x => ({
            label: x.payment_type_desc,
            value: x.id,
            proof_payments: x.proof_payments
        }));
        
        tillsProcessed = tills.map(x => ({
            label: x.till_name,
            value: x.id
        }));
        
        // Auto-seleccionar si solo hay una caja
        if (tills.length === 1) {
            form.till_id = tills[0].id;
        }
    });
    
    // Búsqueda dinámica de clientes (API protegida)
    async function searchClients(searchTerm) {
        if (searchTerm.length < 3) return [];
        
        try {
            const response = await axios.get('/api/search/clients', {
                params: { q: searchTerm }
            });
            return response.data.data;
        } catch (error) {
            console.error('Error searching clients:', error);
            return [];
        }
    }
    
    // Envío del formulario con Inertia
    function handleSubmit() {
        form.post('/sales', {
            onSuccess: () => {
                // Redirigir o mostrar mensaje de éxito
                alert('Venta creada exitosamente');
            },
            onError: (errors) => {
                // Manejar errores de validación
                console.error('Validation errors:', errors);
            }
        });
    }
</script>

<form on:submit|preventDefault={handleSubmit}>
    <!-- Campos del formulario usando datos pre-cargados -->
    <select bind:value={form.till_id}>
        <option value="">Seleccionar caja</option>
        {#each tillsProcessed as till}
            <option value={till.value}>{till.label}</option>
        {/each}
    </select>
    
    <!-- Componente de búsqueda de clientes -->
    <SearchClients 
        {searchClients}
        bind:selected={form.person_id}
    />
    
    <button type="submit" disabled={form.processing}>
        {form.processing ? 'Guardando...' : 'Guardar Venta'}
    </button>
</form>
```

### Paso 5: Proteger APIs Restantes

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    
    // APIs de búsqueda
    Route::get('/search/clients', [PersonsController::class, 'search']);
    Route::get('/search/products', [ProductsController::class, 'search']);
    
    // APIs para operaciones específicas
    Route::post('/sales/validate', [SalesController::class, 'validate']);
    Route::get('/tills/{id}/amount', [TillsController::class, 'getAmount']);
});

// Remover rutas API no protegidas
// Route::get('paymenttypes', [PaymentTypesController::class, 'index']); // ELIMINAR
```

## Ventajas de esta Migración

### Seguridad
- ✅ Todas las rutas protegidas con autenticación
- ✅ Control de permisos granular
- ✅ Protección CSRF automática
- ✅ Rate limiting en APIs

### Performance
- ✅ Menos llamadas HTTP
- ✅ Datos pre-cargados en primera carga
- ✅ Mejor experiencia de usuario

### Mantenibilidad
- ✅ Código más limpio
- ✅ Validaciones centralizadas
- ✅ Mejor manejo de errores

## Migración Gradual

### Fase 1: Datos Estáticos
1. Migrar tipos de pago, categorías, marcas
2. Pre-cargar en controladores web
3. Actualizar componentes Svelte

### Fase 2: Proteger APIs Dinámicas
1. Aplicar middleware `auth:sanctum`
2. Configurar CSRF para SPA
3. Actualizar llamadas AJAX

### Fase 3: Optimización
1. Implementar caché para datos estáticos
2. Lazy loading para datos grandes
3. Paginación en APIs

## Ejemplo Completo: Formulario de Ventas

### Controlador Web:
```php
// app/Http/Controllers/Web/SalesController.php
public function create()
{
    return Inertia::render('Sales/Form', [
        'initialData' => [
            'paymentTypes' => PaymentTypes::with('proofPayments')->get(),
            'userTills' => auth()->user()->person->tills ?? [],
            'saleNumber' => $this->generateSaleNumber(),
        ],
        'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
        'user' => auth()->user()->load('person')
    ]);
}

private function generateSaleNumber()
{
    $lastSale = Sales::latest()->first();
    return $lastSale ? $lastSale->sale_number + 1 : '001-001-0000001';
}
```

### Componente Svelte Actualizado:
```javascript
<script>
    export let initialData;
    export let user;
    export let permissions;
    
    let { paymentTypes, userTills, saleNumber } = initialData;
    
    // Formulario reactivo
    let formData = {
        person_id: '',
        sale_date: new Date().toISOString().slice(0, 10),
        sale_number: saleNumber,
        till_id: userTills.length === 1 ? userTills[0].id : '',
        sale_details: [],
        proof_payments: []
    };
    
    // No más llamadas AJAX para datos estáticos
    onMount(() => {
        console.log('Datos pre-cargados:', { paymentTypes, userTills });
    });
</script>
```

## Conclusión

Esta migración elimina las vulnerabilidades de seguridad manteniendo la funcionalidad existente. Los datos estáticos se pre-cargan con Inertia.js y las operaciones dinámicas usan APIs protegidas con Sanctum.

**Resultado:**
- 🔒 **Seguridad**: Todas las rutas protegidas
- ⚡ **Performance**: Menos llamadas HTTP
- 🧹 **Código limpio**: Mejor organización
- 🛡️ **Mantenible**: Fácil de actualizar y debuggear

La implementación puede hacerse gradualmente, empezando por los formularios más críticos como ventas y compras.