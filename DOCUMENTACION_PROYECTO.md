# Documentación del Proyecto - Contalink

## Descripción General

**Contalink** es una API REST desarrollada con Laravel 11 para el registro y gestión de ingresos y gastos orientado a pequeñas y medianas empresas (PyMEs). El sistema proporciona una solución completa para la gestión contable, incluyendo ventas, compras, inventario, clientes, proveedores y cajas.

## Información Técnica

### Stack Tecnológico

**Backend:**
- **Framework:** Laravel 11
- **PHP:** 8.2+
- **Base de datos:** PostgreSQL
- **Autenticación:** Laravel Sanctum
- **Documentación API:** Swagger/OpenAPI

**Frontend:**
- **Framework:** Svelte 3
- **Integración:** Inertia.js
- **CSS Framework:** Tailwind CSS + DaisyUI
- **Build Tool:** Vite

**Herramientas de Desarrollo:**
- **Monitoreo:** Laravel Telescope
- **Auditoría:** Laravel Auditing
- **Permisos:** Spatie Laravel Permission
- **Logs:** Log Viewer
- **PDF:** DomPDF
- **Testing:** PHPUnit

### Arquitectura del Sistema

El proyecto sigue el patrón MVC de Laravel con las siguientes capas:

```
app/
├── Http/Controllers/    # Controladores de la API
├── Models/              # Modelos Eloquent
├── Providers/           # Service Providers
├── Rules/               # Reglas de validación personalizadas
├── Traits/              # Traits reutilizables
└── Templates/           # Plantillas
```

## Módulos Principales

### 1. Gestión de Usuarios y Permisos
- **Usuarios:** Registro, autenticación y gestión de usuarios
- **Roles:** Sistema de roles con permisos granulares
- **Permisos:** Control de acceso basado en permisos

### 2. Configuración Maestra
- **Países, Estados y Ciudades:** Gestión geográfica
- **Tipos de Persona:** Clientes, proveedores, empleados
- **Tipos de IVA:** Configuración de impuestos
- **Tipos de Pago:** Efectivo, tarjeta, transferencia, etc.
- **Categorías:** Clasificación de productos
- **Marcas:** Gestión de marcas de productos

### 3. Gestión de Personas
- **Clientes:** Registro y gestión de clientes
- **Proveedores:** Gestión de proveedores
- **Empleados:** Administración de personal
- **Tipos de Contacto:** Clasificación de contactos

### 4. Inventario y Productos
- **Productos:** Gestión completa de inventario
- **Marcas:** Asociación de productos con marcas
- **Categorías:** Clasificación de productos
- **Proveedores de Productos:** Relación productos-proveedores

### 5. Gestión de Cajas
- **Tipos de Caja:** Efectivo, banco, etc.
- **Cajas:** Gestión de cajas registradoras
- **Movimientos de Caja:** Apertura, cierre, depósitos
- **Transferencias:** Movimientos entre cajas
- **Reportes de Caja:** Informes detallados y resumidos

### 6. Ventas
- **Registro de Ventas:** Creación y gestión de ventas
- **Detalles de Venta:** Items vendidos
- **Comprobantes de Pago:** Gestión de pagos
- **Reportes de Ventas:** Informes y estadísticas
- **Devoluciones:** Gestión de refunds

### 7. Compras
- **Registro de Compras:** Gestión de compras a proveedores
- **Detalles de Compra:** Items comprados
- **Reportes de Compras:** Informes de compras

### 8. Plan Contable
- **Cuentas Contables:** Estructura del plan de cuentas

## Estructura de Base de Datos

### Tablas Principales

#### Configuración
- `countries` - Países
- `states` - Estados/Provincias
- `cities` - Ciudades
- `person_types` - Tipos de persona
- `iva_types` - Tipos de IVA
- `payment_types` - Tipos de pago
- `contact_types` - Tipos de contacto
- `categories` - Categorías de productos
- `brands` - Marcas

#### Personas y Usuarios
- `users` - Usuarios del sistema
- `persons` - Personas (clientes, proveedores, empleados)
- `roles` - Roles del sistema
- `permissions` - Permisos
- `model_has_permissions` - Asignación de permisos
- `model_has_roles` - Asignación de roles

#### Productos e Inventario
- `products` - Productos
- `products_providers` - Relación productos-proveedores

#### Cajas y Movimientos
- `till_types` - Tipos de caja
- `tills` - Cajas
- `till_details` - Movimientos de caja
- `tills_transfers` - Transferencias entre cajas
- `proof_payments` - Comprobantes de pago
- `till_detail_proof_payments` - Relación movimientos-comprobantes

#### Ventas
- `sales` - Ventas
- `sales_details` - Detalles de venta
- `refunds` - Devoluciones
- `refund_details` - Detalles de devoluciones

#### Compras
- `purchases` - Compras
- `purchases_details` - Detalles de compra

#### Contabilidad
- `account_plans` - Plan de cuentas

#### Auditoría y Sistema
- `audits` - Auditoría de cambios
- `telescope_entries` - Monitoreo de aplicación
- `personal_access_tokens` - Tokens de API

## API Endpoints

### Autenticación
```
POST /api/register - Registro de usuario
POST /login - Inicio de sesión
POST /logout - Cierre de sesión
```

### Gestión de Usuarios
```
GET    /api/users - Listar usuarios
GET    /api/users/{id} - Ver usuario
POST   /api/users - Crear usuario
PUT    /api/users/{id} - Actualizar usuario
DELETE /api/users/{id} - Eliminar usuario
```

### Roles y Permisos
```
GET    /api/roles - Listar roles
POST   /api/roles - Crear rol
PUT    /api/roles/{id} - Actualizar rol
DELETE /api/roles/{id} - Eliminar rol
POST   /api/roles/{roleId}/permissions - Asignar permisos
```

### Configuración Maestra
```
GET    /api/countries - Países
GET    /api/states - Estados
GET    /api/cities - Ciudades
GET    /api/persontypes - Tipos de persona
GET    /api/ivatypes - Tipos de IVA
GET    /api/paymenttypes - Tipos de pago
GET    /api/categories - Categorías
GET    /api/brands - Marcas
```

### Personas
```
GET    /api/persons - Listar personas
POST   /api/persons - Crear persona
PUT    /api/persons/{id} - Actualizar persona
DELETE /api/persons/{id} - Eliminar persona
GET    /api/personsbytype/{id} - Personas por tipo
```

### Productos
```
GET    /api/products - Listar productos
POST   /api/products - Crear producto
PUT    /api/products/{id} - Actualizar producto
DELETE /api/products/{id} - Eliminar producto
```

### Cajas
```
GET    /api/tills - Listar cajas
POST   /api/tills - Crear caja
PUT    /api/tills/{id} - Actualizar caja
DELETE /api/tills/{id} - Eliminar caja
POST   /api/tills/{id}/open - Abrir caja
POST   /api/tills/{id}/close - Cerrar caja
POST   /api/tills/{id}/deposit - Depositar en caja
POST   /api/tills/{id}/transfer - Transferir entre cajas
```

### Ventas
```
GET    /api/sales - Listar ventas
POST   /api/sales - Crear venta
PUT    /api/sales/{id} - Actualizar venta
DELETE /api/sales/{id} - Eliminar venta
GET    /api/sales/report - Reporte de ventas
POST   /api/storesale - Crear venta completa
```

### Compras
```
GET    /api/purchases - Listar compras
POST   /api/purchases - Crear compra
PUT    /api/purchases/{id} - Actualizar compra
DELETE /api/purchases/{id} - Eliminar compra
GET    /api/purchases/report - Reporte de compras
POST   /api/storePurchase - Crear compra completa
```

### Devoluciones
```
GET    /api/refunds - Listar devoluciones
POST   /api/refunds - Crear devolución
PUT    /api/refunds/{id} - Actualizar devolución
DELETE /api/refunds/{id} - Eliminar devolución
```

## Características Especiales

### 1. Sistema de Permisos Granular
- Control de acceso basado en roles y permisos
- Middleware de autorización en todas las rutas
- Permisos específicos por módulo (crear, leer, actualizar, eliminar)

### 2. Auditoría Completa
- Registro automático de todos los cambios
- Trazabilidad de operaciones
- Historial de modificaciones por usuario

### 3. Gestión de Cajas Avanzada
- Apertura y cierre de cajas con validaciones
- Transferencias entre cajas
- Reportes detallados y resumidos
- Control de saldos en tiempo real

### 4. Reportes y Documentación
- Reportes de ventas con filtros
- Reportes de compras
- Generación de PDF
- Documentación automática con Swagger

### 5. Interfaz de Usuario Moderna
- SPA con Svelte e Inertia.js
- Diseño responsivo con Tailwind CSS
- Componentes reutilizables con DaisyUI

## Configuración y Despliegue

### Requisitos del Sistema
- PHP 8.2 o superior
- Composer
- Node.js y npm
- PostgreSQL
- Extensiones PHP: PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON

### Instalación
```bash
# Clonar repositorio
git clone https://github.com/acristhian1411/Contalink.git
cd Contalink

# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=contalink
# DB_USERNAME=usuario
# DB_PASSWORD=contraseña

# Ejecutar migraciones y seeders
php artisan migrate
php artisan db:seed

# Compilar assets
npm run build

# Iniciar servidor
php artisan serve
```

### Variables de Entorno Importantes
```env
APP_NAME=Contalink
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=contalink

SANCTUM_STATEFUL_DOMAINS=localhost:8000
SESSION_DRIVER=database
CACHE_DRIVER=database
```

## Seguridad

### Medidas Implementadas
- Autenticación con Laravel Sanctum
- Validación CSRF en formularios web
- Sanitización de inputs
- Control de acceso basado en roles
- Auditoría de operaciones
- Tokens de API con expiración

### Recomendaciones de Producción
- Usar HTTPS en producción
- Configurar firewall de base de datos
- Implementar rate limiting
- Configurar logs de seguridad
- Realizar backups regulares
- Mantener dependencias actualizadas

## Monitoreo y Mantenimiento

### Herramientas Incluidas
- **Laravel Telescope:** Monitoreo de aplicación en tiempo real
- **Log Viewer:** Visualización de logs del sistema
- **Laravel Auditing:** Registro de cambios en modelos

### Comandos Útiles
```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones
php artisan migrate

# Generar documentación API
php artisan l5-swagger:generate
```

## Documentación API

La documentación completa de la API está disponible en:
- **Desarrollo:** http://localhost:8000/swagger/documentation
- **Producción:** [URL]/swagger/documentation

## Soporte y Contribución

### Estructura de Commits
- `feat:` Nueva funcionalidad
- `fix:` Corrección de errores
- `docs:` Documentación
- `style:` Formato de código
- `refactor:` Refactorización
- `test:` Pruebas
- `chore:` Tareas de mantenimiento

### Contacto
- **Repositorio:** https://github.com/acristhian1411/Contalink
- **Documentación API:** /swagger/documentation

---

*Documentación generada automáticamente - Última actualización: $(date)*