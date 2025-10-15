# Issues de GitHub - Contalink Security & Migration

## Issue #1: 🚨 [CRÍTICO] Implementar Autenticación en Rutas API

**Labels:** `security`, `critical`, `api`, `authentication`

### Descripción
Las rutas API están completamente desprotegidas, permitiendo acceso no autorizado a datos sensibles del sistema.

### Problema
```php
// Rutas vulnerables en routes/api.php
Route::get('/users', [UsersController::class, 'index']); // Sin protección
Route::delete('/users/{id}', [UsersController::class, 'destroy']); // Sin protección
Route::get('/sales', [SalesController::class, 'index']); // Sin protección
```

### Solución Requerida
Aplicar middleware de autenticación a todas las rutas API:

```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('users', UsersController::class);
    Route::apiResource('sales', SalesController::class);
    // ... resto de rutas
});
```

### Criterios de Aceptación
- [ ] Todas las rutas API protegidas con `auth:sanctum`
- [ ] Rate limiting implementado (`throttle:60,1`)
- [ ] Tests de autenticación funcionando
- [ ] Documentación API actualizada

### Prioridad
🔴 **CRÍTICA** - Debe resolverse antes de cualquier despliegue

---

## Issue #2: 🛡️ [CRÍTICO] Corregir Vulnerabilidades de Mass Assignment

**Labels:** `security`, `critical`, `mass-assignment`, `validation`

### Descripción
Uso extensivo de `$request->all()` en controladores permite modificación no autorizada de campos.

### Archivos Afectados
- `app/Http/Controllers/Users/UsersController.php`
- `app/Http/Controllers/Products/ProductsController.php`
- `app/Http/Controllers/Sales/SalesController.php`
- Y muchos más...

### Código Vulnerable
```php
// Ejemplo en UsersController
$user = User::create($request->all()); // VULNERABLE
$categories->update($request->all()); // VULNERABLE
```

### Solución Requerida
```php
// Usar validación específica
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]);
$user = User::create($validated);
```

### Tareas
- [ ] Auditar todos los controladores
- [ ] Reemplazar `$request->all()` con validaciones específicas
- [ ] Verificar propiedades `$fillable` en modelos
- [ ] Crear tests de validación
- [ ] Documentar campos permitidos por endpoint

### Prioridad
🔴 **CRÍTICA**

---

## Issue #3: 🔐 [ALTO] Migrar de API Calls a Inertia Pre-loaded Data

**Labels:** `enhancement`, `security`, `inertia`, `frontend`, `migration`

### Descripción
Migrar llamadas AJAX a datos pre-cargados con Inertia.js para mejorar seguridad y performance.

### Problema Actual
```javascript
// En form.svelte - Llamadas AJAX inseguras
function getPaymentTypes() {
    axios.get(`/api/paymenttypes`) // API sin protección
        .then((response) => {
            paymentTypes = response.data.data;
        });
}
```

### Solución Propuesta
```php
// En controlador web
Route::get('/create-sales', function () {
    return Inertia::render('Sales/form', [
        'paymentTypes' => PaymentTypes::all(),
        'clients' => Persons::where('p_type_id', 2)->get(),
        'user' => auth()->user()
    ]);
});
```

### Fases de Migración

#### Fase 1: Datos Estáticos
- [ ] Migrar tipos de pago
- [ ] Migrar categorías y marcas
- [ ] Migrar tipos de persona
- [ ] Migrar países, estados, ciudades

#### Fase 2: Formularios Principales
- [ ] Formulario de ventas (`Sales/form.svelte`)
- [ ] Formulario de compras (`Purchases/form.svelte`)
- [ ] Formulario de productos (`Products/form.svelte`)

#### Fase 3: APIs Dinámicas Protegidas
- [ ] Búsqueda de clientes
- [ ] Búsqueda de productos
- [ ] Operaciones de caja

### Criterios de Aceptación
- [ ] Datos estáticos pre-cargados con Inertia
- [ ] APIs dinámicas protegidas con Sanctum
- [ ] Performance mejorada (menos HTTP requests)
- [ ] Funcionalidad existente mantenida

### Prioridad
🟡 **ALTA**

---

## Issue #4: ⚡ [ALTO] Implementar Rate Limiting y Protección contra Ataques

**Labels:** `security`, `rate-limiting`, `ddos-protection`

### Descripción
Implementar rate limiting para prevenir ataques de fuerza bruta y DoS.

### Endpoints Críticos
- `/login` - Intentos de login
- `/register` - Registro de usuarios
- `/api/*` - Todas las APIs

### Implementación Requerida
```php
// En RouteServiceProvider.php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### Tareas
- [ ] Configurar rate limiting para login (5 intentos/minuto)
- [ ] Configurar rate limiting para APIs (60 requests/minuto)
- [ ] Implementar rate limiting para registro
- [ ] Agregar headers informativos de rate limit
- [ ] Crear tests de rate limiting
- [ ] Documentar límites en API docs

### Prioridad
🟡 **ALTA**

---

## Issue #5: 🔧 [MEDIO] Configurar Sesiones y Cookies Seguras

**Labels:** `security`, `configuration`, `sessions`

### Descripción
Configurar sesiones y cookies con parámetros de seguridad apropiados.

### Configuraciones Actuales (Inseguras)
```env
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=null
APP_DEBUG=true
```

### Configuraciones Requeridas
```env
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
APP_DEBUG=false
SANCTUM_EXPIRATION=60
```

### Tareas
- [ ] Actualizar configuración de sesiones
- [ ] Configurar cookies seguras
- [ ] Establecer expiración de tokens Sanctum
- [ ] Deshabilitar debug en producción
- [ ] Configurar HTTPS obligatorio
- [ ] Actualizar documentación de despliegue

### Prioridad
🟠 **MEDIA**

---

## Issue #6: 🧹 [MEDIO] Estandarizar Validaciones y Manejo de Errores

**Labels:** `code-quality`, `validation`, `error-handling`

### Descripción
Estandarizar validaciones inconsistentes y mejorar manejo de errores.

### Problemas Identificados
- Validaciones inconsistentes entre controladores
- Exposición de información sensible en errores
- Falta de validaciones en algunos endpoints

### Solución Propuesta
```php
// Crear Form Requests estandarizados
class StoreSaleRequest extends FormRequest
{
    public function rules()
    {
        return [
            'person_id' => 'required|exists:persons,id',
            'sale_date' => 'required|date',
            'sale_details' => 'required|array|min:1',
        ];
    }
}
```

### Tareas
- [ ] Crear Form Requests para todos los endpoints
- [ ] Estandarizar mensajes de error
- [ ] Implementar manejo de errores centralizado
- [ ] Crear middleware de logging de errores
- [ ] Ocultar información sensible en producción
- [ ] Crear tests de validación

### Prioridad
🟠 **MEDIA**

---

## Issue #7: 🔍 [MEDIO] Revisar y Sanitizar Consultas SQL Raw

**Labels:** `security`, `sql-injection`, `database`

### Descripción
Revisar uso de `DB::raw()` para prevenir inyección SQL.

### Consultas Identificadas
```php
// En TillDetailsController.php
->where('till_details.created_at', '<=', DB::raw('now()'))
->select('pd.purchase_id', DB::raw('SUM(pd.pd_amount) as total'))
```

### Tareas
- [ ] Auditar todas las consultas con `DB::raw()`
- [ ] Reemplazar con métodos seguros de Eloquent
- [ ] Usar parámetros preparados donde sea necesario
- [ ] Crear tests de seguridad SQL
- [ ] Documentar consultas complejas

### Prioridad
🟠 **MEDIA**

---

## Issue #8: 📊 [BAJO] Implementar Headers de Seguridad y Logging

**Labels:** `security`, `headers`, `logging`, `monitoring`

### Descripción
Implementar headers de seguridad HTTP y logging de eventos de seguridad.

### Headers Requeridos
- `Strict-Transport-Security`
- `X-Frame-Options`
- `X-Content-Type-Options`
- `Referrer-Policy`
- `Content-Security-Policy`

### Eventos a Loggear
- Intentos de login fallidos
- Cambios de permisos
- Operaciones financieras críticas
- Accesos no autorizados

### Tareas
- [ ] Configurar headers de seguridad
- [ ] Implementar middleware de headers
- [ ] Crear sistema de logging de seguridad
- [ ] Configurar alertas de seguridad
- [ ] Crear dashboard de monitoreo
- [ ] Documentar eventos loggeados

### Prioridad
🟢 **BAJA**

---

## Issue #9: 🧪 [MEDIO] Crear Suite de Tests de Seguridad

**Labels:** `testing`, `security`, `automation`

### Descripción
Crear tests automatizados para verificar medidas de seguridad.

### Tests Requeridos
- Tests de autenticación API
- Tests de autorización por roles
- Tests de rate limiting
- Tests de validación de entrada
- Tests de inyección SQL
- Tests de XSS

### Estructura Propuesta
```php
// tests/Feature/Security/
- ApiAuthenticationTest.php
- RateLimitingTest.php
- ValidationSecurityTest.php
- SqlInjectionTest.php
```

### Tareas
- [ ] Crear tests de autenticación API
- [ ] Crear tests de rate limiting
- [ ] Crear tests de validación
- [ ] Crear tests de autorización
- [ ] Integrar en CI/CD pipeline
- [ ] Configurar coverage mínimo 80%

### Prioridad
🟠 **MEDIA**

---

## Issue #10: 📚 [BAJO] Actualizar Documentación de Seguridad

**Labels:** `documentation`, `security`, `api-docs`

### Descripción
Actualizar documentación con nuevas medidas de seguridad implementadas.

### Documentación a Actualizar
- README.md con instrucciones de seguridad
- Documentación API con autenticación
- Guía de despliegue seguro
- Guía de desarrollo seguro

### Tareas
- [ ] Actualizar README con configuración segura
- [ ] Documentar endpoints de autenticación
- [ ] Crear guía de despliegue en producción
- [ ] Documentar mejores prácticas de desarrollo
- [ ] Crear changelog de cambios de seguridad

### Prioridad
🟢 **BAJA**

---

## Roadmap de Implementación

### Sprint 1 (Crítico - 1 semana)
- Issue #1: Autenticación API
- Issue #2: Mass Assignment

### Sprint 2 (Alto - 2 semanas)
- Issue #3: Migración Inertia (Fase 1)
- Issue #4: Rate Limiting

### Sprint 3 (Medio - 2 semanas)
- Issue #3: Migración Inertia (Fase 2)
- Issue #5: Configuración Segura
- Issue #6: Validaciones

### Sprint 4 (Finalización - 1 semana)
- Issue #7: SQL Raw
- Issue #8: Headers y Logging
- Issue #9: Tests de Seguridad
- Issue #10: Documentación

## Notas Importantes

⚠️ **CRÍTICO**: Los issues #1 y #2 deben resolverse antes de cualquier despliegue en producción.

🔄 **TESTING**: Cada issue debe incluir tests automatizados correspondientes.

📋 **REVIEW**: Todos los cambios de seguridad requieren code review obligatorio.

🚀 **DEPLOYMENT**: Crear checklist de seguridad para despliegues.